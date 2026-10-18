# SINOS — Sistem Informasi Nomor Surat

Aplikasi persuratan: surat keluar, surat masuk, disposisi, dan laporan.

SINOS adalah **pemilik schema `sinos`** dan **pembaca** data pegawai dari schema
`pegawai_db`. SINOS tidak lagi menyimpan maupun mengubah password pegawai.

> **Arsitektur sejak 2026-10-18:** backend dan frontend berada dalam **satu
> folder, satu repo, satu pipeline**.
> `sinos/` (CodeIgniter 3) hanya melayani **JSON API** di `/api/v1/`.
> Seluruh antarmuka ada di `sinos/frontend/` (Astro SSR + Tailwind 4 + Preline 5).
> Frontend CI3 sudah dihapus; cadangannya ada di
> `sinos/backup/ui-ci3-frontend-2026-10-18/`.
> Source frontend **tidak** disajikan Apache — `sinos/.htaccess` menolak
> `frontend/src`, `frontend/node_modules`, `frontend/.env`, dan berkas build/config.

| Aplikasi      | Folder            | URL                              | Schema       |
| ------------- | ----------------- | -------------------------------- | ------------ |
| SSO Gateway   | `sso/`            | `http://localhost/sso/`          | `sso_db`     |
| Pegawai (CI3) | `pegawai/`        | `http://localhost/pegawai/`      | `pegawai_db` |
| SINOS API     | `sinos/`          | `http://localhost/sinos/api/v1/` | `sinos`      |
| SINOS Web     | `sinos/frontend/` | `http://localhost:4321/`         | — (klien)    |

## Menjalankan

```bash
# Backend (Apache/Laragon) — tidak perlu build
#   http://localhost/sinos/api/v1/

# Frontend (satu repo, satu pipeline)
cd sinos/frontend
npm install
npm run dev        # http://localhost:4321
# produksi:
npm run build && npm run start
```

Wajib diisi sebelum dipakai di luar lokal:

- `sinos/.env` (backend):

  ```
  API_SECRET=<32 byte hex — php -r "echo bin2hex(random_bytes(32));">
  ```

- `sinos/frontend/.env` (frontend) — disalin dari `sinos/frontend/.env.example`:

  | Variabel              | Dev                             | Produksi                            |
  | --------------------- | ------------------------------- | ----------------------------------- |
  | `SINOS_API_BASE`      | `http://localhost/sinos/api/v1` | `https://sinos.domain.go.id/api/v1` |
  | `SINOS_SSO_GATEWAY`   | `http://localhost/sso`          | `https://sso.domain.go.id`          |
  | `SINOS_SSO_APP_ID`    | `sinos`                         | `sinos`                             |
  | `SINOS_PUBLIC_ORIGIN` | _(kosong)_                      | `https://sinos.domain.go.id`        |
  | `SESSION_SECRET`      | 64 hex                          | 64 hex (beda dari dev)              |

Tanpa `API_SECRET`, helper memakai kunci turunan dari `base_url` + `ENVIRONMENT`
dan menulis peringatan ke log. Jangan pakai mode itu di produksi.

### Login SSO di dev dan produksi

`redirect_uri` yang dikirim ke gateway **harus persis sama** dengan salah satu
entri di `sso_db.sso_client.redirect_uris`. Origin ditentukan oleh
[`src/lib/sso.ts`](frontend/src/lib/sso.ts) dengan urutan:

1. `SINOS_PUBLIC_ORIGIN` bila diisi — **wajib di produksi di belakang reverse
   proxy**, karena `Host` yang diterima Astro adalah host internal.
2. `X-Forwarded-Proto` + `X-Forwarded-Host` bila proxy meneruskan keduanya.
3. `url.origin` — dipakai di dev (`http://localhost:4321`).

Daftarkan **semua** origin yang dipakai:

```sql
-- dev
UPDATE sso_client
   SET redirect_uris = CONCAT(redirect_uris, ',http://localhost:4321/api/auth/callback')
 WHERE app_id = 'sinos';

-- produksi
UPDATE sso_client
   SET redirect_uris = CONCAT(redirect_uris, ',https://sinos.domain.go.id/api/auth/callback')
 WHERE app_id = 'sinos';
```

Cookie sesi (`sinos_sess`) diberi flag `Secure` **hanya bila permintaan datang
lewat https** — ditentukan dari protokol, bukan dari `NODE_ENV`. Dengan begitu
build produksi yang diuji di `http://localhost` tetap bisa login, dan produksi
yang sebenarnya (https) tetap aman.

---

## 1. Kepemilikan data

| Schema       | Pemilik    | Isi                                                     |
| ------------ | ---------- | ------------------------------------------------------- |
| `sinos`      | `sinos/`   | `nosur`, `surmas`, `disposisi`, `ref_klasifikasi`       |
| `pegawai_db` | `pegawai/` | `pegawai`, `pegawai_role`, `ref_jabatan`, audit, API    |
| `sso_db`     | `sso/`     | `sso_token`, `sso_nonce`, `sso_audit_log`, `sso_client` |

SINOS **hanya membaca** kolom identitas tertentu:

```sql
GRANT SELECT (id, nip, nama, aktif, is_operator, id_jabatan)
  ON pegawai_db.pegawai TO 'sinos_app'@'localhost';
```

Kolom `pass` **tidak** termasuk. Konsekuensinya:

- `SELECT *` dari `pegawai_db.pegawai` akan gagal — semua query wajib menyebut
  kolom eksplisit (lihat [`M_user`](application/models/M_user.php)).
- Login lokal SINOS tidak dapat memverifikasi password dan sudah dinonaktifkan;
  autentikasi sepenuhnya lewat SSO.
- Fitur **Ganti Password tidak tersedia di SINOS**. Pengelolaan kredensial akan
  dipusatkan di SSO Gateway.
- Menu **Pegawai** juga tidak tersedia di SINOS; pengelolaan pegawai ada di
  aplikasi `pegawai/`.

Tabel legacy `user` dan `jabatan` sudah dihapus; sumber jabatan sekarang
`pegawai_db.ref_jabatan`.

## 2. Struktur

```
sinos/
├── application/
│   ├── config/          # config, database, routes, sso
│   ├── controllers/     # api/ (Auth, Dashboard, Surat_keluar, Surat_masuk,
│   │                    # Disposisi, Master, Laporan), Sso
│   ├── core/            # MY_Controller, MY_Api_Controller, MY_Model
│   ├── helpers/         # sinos_helper.php, api_token_helper.php, api_present_helper.php
│   ├── libraries/       # Pdf.php, Sso_client.php
│   ├── models/          # M_user, M_nosur, M_surmas, M_disposisi, M_klasifikasi
│   └── views/           # (legacy, tidak lagi dilayani)
├── assets/img/KOP.jpg   # satu-satunya aset statis yang masih dilayani
├── backup/              # cadangan frontend CI3 (tidak di-commit ke pipeline)
├── db/migration_extra.sql
├── file/ file/sm/       # berkas PDF (akses langsung ditolak .htaccess)
├── frontend/            # SATU-SATUNYA frontend: Astro SSR + Tailwind 4 + Preline 5
│   ├── src/             # pages/, layouts/, components/, lib/, styles/
│   ├── astro.config.mjs
│   ├── package.json     # nama paket: sinos-frontend
│   └── .env             # SINOS_API_BASE, SINOS_SSO_GATEWAY, SESSION_SECRET
├── plans/               # catatan desain & rencana
├── system/              # core CodeIgniter 3.1.13
├── vendor/              # composer (mPDF)
├── .env.example
├── .htaccess            # menolak application/, file/, dan source frontend/
└── router.php           # router untuk `php -S` (development)
```

## 3. Instalasi

### 3.1 Database

Schema `sinos` hanya berisi tabel persuratan:

```bash
mysql -u root -e "CREATE DATABASE sinos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root sinos < db/migration_extra.sql
```

Tabel `nosur`, `surmas`, `disposisi`, dan `ref_klasifikasi` mengikuti skema
aplikasi. Data pegawai **tidak** dibuat di sini.

### 3.2 User MySQL

```sql
CREATE USER 'sinos_app'@'localhost' IDENTIFIED BY '<acak 32+ karakter>';
GRANT SELECT, INSERT, UPDATE, DELETE ON sinos.* TO 'sinos_app'@'localhost';
GRANT SELECT (id, nip, nama, aktif, is_operator, id_jabatan)
  ON pegawai_db.pegawai TO 'sinos_app'@'localhost';
GRANT SELECT (pegawai_id, role_kode)
  ON pegawai_db.pegawai_role TO 'sinos_app'@'localhost';
GRANT SELECT (id, jabatan, subbag, kode)
  ON pegawai_db.ref_jabatan TO 'sinos_app'@'localhost';
```

### 3.3 Konfigurasi

Salin `.env.example` menjadi `.env`:

```ini
DB_HOSTNAME=localhost
DB_USERNAME=sinos_app
DB_PASSWORD=<acak>
DB_DATABASE=sinos

SSO_ENABLED=1
SSO_FORCE=0
SSO_GATEWAY=http://localhost/sso/
SSO_APP_ID=sinos
SSO_APP_SECRET=<secret dari sso_client>
SSO_CALLBACK=http://localhost/sinos/sso/callback
SSO_TIMEOUT=5
```

Sesuaikan `base_url` di [`application/config/config.php`](application/config/config.php)
(default `http://localhost/sinos/`) dan `pegawai_app_url` bila aplikasi pegawai
berada di host lain.

> **Collation:** kolom `nosur.nip` dan `disposisi.dari`/`nip_tujuan` harus
> `utf8mb4_unicode_ci`, sama dengan `pegawai_db.pegawai.nip`. Collation berbeda
> menyebabkan `Illegal mix of collations` pada join lintas schema.

### 3.4 Dependency & aset

Prasyarat:

- **Node.js ≥ 20** — wajib untuk Tailwind 4 (compiler Oxide; versi Node lebih
  lama gagal saat build).
- **npm** (ikut bersama Node).
- **Composer** untuk mPDF.

```bash
composer install                      # mPDF (backend)
cd frontend && npm install            # frontend (Astro + Tailwind 4 + Preline 5)
```

#### Satu pipeline frontend

Sejak konsolidasi 2026-10-18 hanya ada **satu** pipeline frontend: Astro SSR
dengan Tailwind 4 (`@tailwindcss/vite`) dan Preline 5. Pipeline v3/v4 CI3 lama
(`assets/css/input.css`, `assets/css/app.css`, `assets/js/preline.js`,
`tailwind.config.js`) sudah dihapus dan tidak boleh dibuat ulang.

| Perintah (dari `sinos/frontend/`) | Fungsi                                         |
| --------------------------------- | ---------------------------------------------- |
| `npm run dev`                     | dev server `http://localhost:4321`             |
| `npm run build`                   | build produksi ke `frontend/dist/`             |
| `npm run start`                   | jalankan hasil build (`dist/server/entry.mjs`) |
| `npm run preview`                 | pratinjau hasil build                          |

Sumber gaya ada di [`frontend/src/styles/global.css`](frontend/src/styles/global.css):
`@import "tailwindcss"`, `@import "preline/variants.css"`, lalu `@source` untuk
`node_modules/preline/dist/*.js` dan seluruh `src/**/*.astro|ts`. Build harus
menghasilkan CSS sekitar **56.8 KB**; penurunan drastis berarti glob `@source`
gagal dan kelas Preline ikut terbuang.

#### Preline MCP (opsional, untuk pengembangan UI)

Server MCP Preline bersifat **remote** di `https://mcp.preline.co` dan
memerlukan **bearer token**. Tidak ada paket npm `preline-mcp` maupun
`@preline/mcp` — jangan mencoba `npx` keduanya.

Template konfigurasi disediakan di
[`.vscode/mcp.json.example`](.vscode/mcp.json.example). Untuk mengaktifkannya,
salin menjadi `.vscode/mcp.json`, lalu isi token saat editor meminta (nilai
`preline-token` diminta sebagai password dan tidak disimpan di berkas).
Berkas `mcp.json` yang berisi token asli **tidak** boleh di-commit.

Pastikan folder berikut dapat ditulis web server:
`file/`, `file/sm/`, `application/cache/`, `application/cache/sessions/`, `tmp/`.

### 3.5 Menjalankan

- **Apache/Laragon (backend API)**: `http://localhost/sinos/api/v1/`
- **Astro (frontend, satu-satunya antarmuka)**:

  ```bash
  cd sinos/frontend
  npm run dev                      # http://localhost:4321
  npm run build && npm run start   # produksi
  ```

- **Dev server backend tanpa Apache** (opsional):

  ```bash
  php -S localhost:8899 -t . router.php
  ```

## 4. Login

- Autentikasi lewat gateway SSO (`app_id = sinos`). Frontend Astro memulai alur
  di `/api/auth/login`, menerima tiket di `/api/auth/callback`, lalu menyimpan
  token API di cookie httpOnly `sinos_sess`.
- Login lokal tidak lagi memverifikasi password; backend hanya menerbitkan token
  lewat `POST /api/v1/auth/exchange`.
- Logout melakukan **single logout**: token seluruh grup sesi dihapus di gateway,
  sehingga aplikasi lain juga ikut logout.
- Setiap request halaman Astro memanggil `GET /api/v1/auth/me`; bila token
  kedaluwarsa, pengguna dialihkan ke `/login`.

## 5. Peta URL utama

Frontend (Astro, port 4321):

| URL                                                         | Fungsi               |
| ----------------------------------------------------------- | -------------------- |
| `/login`, `/logout`                                         | Autentikasi (SSO)    |
| `/dashboard`                                                | Beranda / ringkasan  |
| `/surat-keluar/ambil`, `/sisip`, `/daftar`, `/daftar-semua` | Surat Keluar         |
| `/surat-keluar/{id}`                                        | Edit & upload berkas |
| `/surat-masuk`, `/surat-masuk/daftar?tahun=2026`            | Surat Masuk          |
| `/surat-masuk/{id}`, `/disposisi`, `/disposisi/{id}`        | Kelola surat masuk   |
| `/laporan`, `/laporan/cetak`                                | Laporan PDF (admin)  |

Backend (CI3, JSON saja):

| URL                                          | Fungsi                    |
| -------------------------------------------- | ------------------------- |
| `/sinos/api/v1/auth/exchange`, `/auth/me`    | Token & identitas         |
| `/sinos/api/v1/dashboard`                    | Ringkasan                 |
| `/sinos/api/v1/surat-masuk`, `/surat-keluar` | Daftar & mutasi surat     |
| `/sinos/api/v1/disposisi`, `/laporan/opsi`   | Disposisi & opsi laporan  |
| `/sinos/api/v1/berkas/{token}`               | Unduh PDF bertanda tangan |

Semua URI lain di bawah `sinos/` dibalas JSON 404; source frontend ditolak
Apache dengan **403**.

Menu **Pegawai** tidak tersedia di SINOS. Pengelolaan pegawai dilakukan di
aplikasi `pegawai/` dan dapat dibuka dari launcher SSO.

## 6. Penomoran surat keluar

Form **Ambil Nomor** dan **Sisip Nomor** memakai field berikut. Pada form sisip,
**Tanggal** diletakkan bersebelahan dengan **Kode Penetapan**.

| Field               | Sumber                                                                                                                                                               | Tata letak                            |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------- |
| Pengambil Nomor     | `pegawai_db.pegawai` aktif, **tanpa** akun administrator. Ditampilkan **nama saja**                                                                                  | ½ baris                               |
| Penandatangan Surat | Pegawai aktif pada jabatan Ketua, Wakil Ketua, Panitera, Sekretaris, Para Panitera Muda, dan Para Kasubbag. Label: `Nama [KODE]`, mis. `Dahron, S.Ag., M.S.I. [KPA]` | ½ baris (satu baris dengan pengambil) |
| Kode Penetapan      | `SK`, `SP`, `ST`, `SKET`, `PENG`, `UND`, atau `-` (Surat Lainnya)                                                                                                    | ¼ baris                               |
| Tanggal             | **hanya form sisip**; tanggal mundur, bersebelahan dengan Kode Penetapan                                                                                             | ¼ baris                               |
| Kode Surat          | `ref_klasifikasi.kode` — sama dengan pilihan Kode Klasifikasi pada Surat Masuk                                                                                       | ¾ baris (ambil), ½ baris (sisip)      |
| Perihal             | input teks                                                                                                                                                           | penuh                                 |
| Tujuan              | input teks                                                                                                                                                           | penuh                                 |

Field **Jumlah Nomor tidak ditampilkan** di form; setiap pengambilan selalu
menghasilkan satu nomor. `surat_keluar_max_jumlah` hanya menjadi batas bila
fitur tersebut diaktifkan kembali.

### Format nomor

```
{no_urut}{huruf}/{kode_penandatangan}.{instansi}/{kode_penetapan}.{kode_klasifikasi}/{bulan}/{tahun}
```

Contoh:

```
1500/KPA.W23-A1/KU1.1.2/X/2026
1500A/KPA.W23-A1/SK.HK1.2.5/X/2026
```

Bagian `{kode_penetapan}.` dilewati bila nilainya `-`.

### Aturan huruf

- **Ambil**: nomor melanjutkan nomor terakhir pada tanggal hari itu, tanpa huruf.
- **Sisip**: hanya untuk **tanggal mundur**. Sistem mengambil nomor terakhir pada
  tanggal tersebut; bila sudah terpakai, ditambahkan huruf `A`, `B`, `C`, dst.
  agar tidak terjadi nomor ganda.
- Nomor urut **tidak** dinaikkan untuk setiap nomor dalam satu pengambilan —
  semua memakai nomor urut yang sama dengan pembeda huruf.

### Konfigurasi

Di [`application/config/config.php`](application/config/config.php):

```php
$config['kode_penetapan']         = array('-' => 'Surat Lainnya', 'SK' => 'Surat Keputusan', ...);
$config['penandatangan_jabatan']  = array('Ketua' => array(2), 'Wakil Ketua' => array(3), ...);
$config['surat_keluar_max_jumlah'] = 20;
```

### Migrasi

Jalankan [`db/migration_surat_keluar_fields.sql`](db/migration_surat_keluar_fields.sql)
untuk menambah kolom `penandatangan_nip`, `penandatangan_nama`, `kode_penetapan`,
`jumlah`, dan memperlebar `kode` menjadi `varchar(20)`.

## 7. Kustomisasi tema

Antarmuka dibangun di atas **Astro SSR + Tailwind 4 + Preline UI 5**
(`preline@^5`, devDependency di `frontend/package.json`). Tidak ada
`tailwind.config.js` — konfigurasi tema sepenuhnya CSS-first.

- **Warna, font, radius** — blok `@theme` di
  [`frontend/src/styles/global.css`](frontend/src/styles/global.css). Palet
  `brand` (primer `#0A2463`) dan `accent` (merah `#FB3640`) didefinisikan sebagai
  custom property Tailwind 4.
- **Komponen** — kelas `.btn`, `.card`, `.input`, `.table`, `.badge-*` juga di
  `global.css`, memakai `@layer components`.
- **Varian Preline** — `@import "preline/variants.css"` menyediakan
  `hs-dropdown-open:`, `hs-overlay-open:`, `hs-selected:`, dll.
- **Pemindaian kelas** — `@source "../../node_modules/preline/dist/*.js"` dan
  `@source "../**/*.astro"` memberi tahu compiler Tailwind 4 di mana mencari
  kelas. Glob pertama **wajib** menunjuk `frontend/node_modules/preline/dist/`;
  bila kedalamannya salah, build tetap sukses tetapi kelas Preline hilang dan
  ukuran CSS anjlok dari ~56.8 KB.
- **JS Preline** — dimuat sebagai modul dari `node_modules` oleh layout Astro;
  tidak ada lagi salinan ke `assets/js/`.

Setelah mengubah komponen atau CSS, jalankan `npm run build` dari
`sinos/frontend/`.

Detail keputusan desain ada di
[`plans/2026-10-17-sinos-astro-preline.md`](plans/2026-10-17-sinos-astro-preline.md).

## 8. Catatan kompatibilitas

- Diuji pada **PHP 8.5**. Ada patch kecil pada core CI3 untuk PHP 8.2+:
  penanganan `E_DEPRECATED` (dynamic property), `E_STRICT` (dihapus di PHP 8.4),
  dan `is_really_writable`/`mkdir()`. Patch ditandai komentar di
  `system/core/Common.php` dan `system/core/Exceptions.php`.
- Seluruh query memakai Query Builder; redirect memakai helper CI3; proteksi
  login dipusatkan di `MY_Controller`.

## 9. Lisensi

CodeIgniter 3 dirilis dengan lisensi MIT. Aplikasi SINOS milik Pengadilan Agama
Kupang.

# SINOS — Sistem Informasi Nomor Surat (CodeIgniter 3 + Tailwind CSS)

Aplikasi persuratan: surat keluar, surat masuk, disposisi, dan laporan.

SINOS adalah **pemilik schema `sinos`** dan **pembaca** data pegawai dari schema
`pegawai_db`. SINOS tidak lagi menyimpan maupun mengubah password pegawai.

| Aplikasi      | Folder     | URL                         | Schema       |
| ------------- | ---------- | --------------------------- | ------------ |
| SSO Gateway   | `sso/`     | `http://localhost/sso/`     | `sso_db`     |
| Pegawai (CI3) | `pegawai/` | `http://localhost/pegawai/` | `pegawai_db` |
| SINOS (CI3)   | `sinos/`   | `http://localhost/sinos/`   | `sinos`      |

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
│   ├── controllers/     # Auth, Dashboard, Surat_keluar, Surat_masuk,
│   │                    # Disposisi, User, Laporan, Sso
│   ├── core/            # MY_Controller, MY_Model
│   ├── helpers/         # sinos_helper.php
│   ├── libraries/       # Pdf.php, Sso_client.php
│   ├── models/          # M_user, M_nosur, M_surmas, M_disposisi, M_klasifikasi
│   └── views/           # layouts/, auth/, surat_keluar/, surat_masuk/, ...
├── assets/              # css/app.css hasil build Tailwind, js/preline.js
├── db/migration_extra.sql
├── file/ file/sm/       # berkas PDF
├── system/              # core CodeIgniter 3.1.13
├── vendor/              # composer (mPDF)
├── .env.example
├── router.php           # router untuk `php -S` (development)
└── tailwind.config.js
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

- **Node.js ≥ 20** — wajib untuk pipeline v4 (Tailwind 4 memakai compiler
  Oxide; versi Node lebih lama gagal saat build).
- **npm** (ikut bersama Node).
- **Composer** untuk mPDF.

```bash
composer install     # mPDF
npm install
npm run copy:preline # salin runtime Preline 5 -> assets/js/preline.v5.js
```

#### Dua pipeline build

Repositori ini memelihara **dua pipeline berdampingan**: v3 (legacy) dan v4
(desain baru). Keduanya harus tetap dapat di-build.

| Perintah               | Pipeline     | Input                                | Output                    |
| ---------------------- | ------------ | ------------------------------------ | ------------------------- |
| `npm run build`        | v3 legacy    | `assets/css/input.css`               | `assets/css/app.css`      |
| `npm run build:v4`     | v4 (default) | `assets/css/input.v4.css`            | `assets/css/app.v4.css`   |
| `npm run watch`        | v3 legacy    | —                                    | watch `app.css`           |
| `npm run watch:v4`     | v4 (default) | —                                    | watch `app.v4.css`        |
| `npm run copy:preline` | —            | `node_modules/preline/dist/index.js` | `assets/js/preline.v5.js` |

```bash
npm run build        # v3 legacy
npm run build:v4     # v4 (desain baru)
```

> **`app.css` dan `app.v4.css` adalah artefak build, bukan berkas sumber.**
> Keduanya wajib dihasilkan di server saat deploy dengan `npm run build` dan
> `npm run build:v4`. Catatan: `app.css` masuk `.gitignore` (tidak di-commit),
> sedangkan `app.v4.css` **di-commit** agar redesign bisa dipreview tanpa build.
> Jangan menyunting kedua berkas itu secara manual — perubahan akan hilang pada
> build berikutnya.

#### Memilih stylesheet yang aktif

**Default adalah v4** sejak 2026-10-17: `assets/css/app.v4.css` (Tailwind 4)
plus `assets/js/preline.v5.js` (Preline 5). Build v3 legacy
(`assets/css/app.css` + `assets/js/preline.js`) tetap tersedia dan tidak
dihapus — itulah jalur rollback, tanpa perlu rebuild.

Varian UI diselesaikan sekali per request oleh
[`application/helpers/ui_helper.php`](application/helpers/ui_helper.php) dengan
urutan prioritas:

1. Query string `?ui=v3` atau `?ui=v4` — menang, lalu dipersist ke cookie.
2. Environment variable `SINOS_UI` — saklar global untuk seluruh deployment.
3. Cookie `sinos_ui`.
4. Default: **`v4`**.

Cara berpindah:

- **Rollback per-browser:** buka `?ui=v3` pada URL mana pun, mis.
  `http://localhost/sinos/dashboard?ui=v3`. Pilihan disimpan di cookie
  `sinos_ui` selama **30 hari** (`path=/`, `SameSite=Lax`), sehingga halaman
  berikutnya tetap memakai stylesheet legacy tanpa query string.
- **Rollback global (server-side, instan):** set environment variable
  `SINOS_UI=v3` di web server, lalu restart PHP-FPM/Apache. Ini mengembalikan
  **semua** pengguna ke v3 tanpa menyentuh cookie siapa pun — jalur rollback
  tercepat saat insiden.
- **Kembali ke v4:** buka `?ui=v4` (menimpa cookie per-browser) atau hapus
  `SINOS_UI` dari environment untuk memakai default.
- Query string tetap menang atas env, jadi `?ui=v4` masih bisa dipakai untuk
  membandingkan varian saat `SINOS_UI=v3` sedang aktif.

Helper yang dipakai view: `ui_css_tag()`, `ui_js_tag()`, dan `ui_body_class()`.
Jangan menulis `<link>`/`<script>` ke `app.css`/`app.v4.css` secara langsung —
selalu lewat helper agar isolasi varian tetap terjaga.

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

- **XAMPP**: buka `http://localhost/sinos/`
- **Dev server**:

  ```bash
  php -S localhost:8899 -t . router.php
  ```

## 4. Login

- Autentikasi lewat gateway SSO (`app_id = sinos`).
- Login lokal tidak lagi memverifikasi password; form hanya menampilkan pesan
  untuk masuk melalui SSO.
- Logout melakukan **single logout**: token seluruh grup sesi dihapus di gateway,
  sehingga aplikasi lain juga ikut logout.
- Setiap request halaman memeriksa `introspect`; bila sesi gateway berakhir,
  sesi lokal SINOS dihancurkan otomatis.

## 5. Peta URL utama

| URL                                                         | Fungsi               |
| ----------------------------------------------------------- | -------------------- |
| `/login`, `/logout`                                         | Autentikasi          |
| `/dashboard`                                                | Beranda / ringkasan  |
| `/surat-keluar/ambil`, `/sisip`, `/daftar`, `/daftar-semua` | Surat Keluar         |
| `/surat-keluar/edit/{id}`, `/upload/{id}`                   | Edit & upload berkas |
| `/surat-masuk`, `/surat-masuk/daftar?tahun=2026`            | Surat Masuk          |
| `/surat-masuk/edit/{id}`, `/disposisi/{id}`, `/hapus/{id}`  | Kelola surat masuk   |
| `/laporan`, `/laporan/cetak`                                | Laporan PDF (admin)  |

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

Antarmuka dibangun di atas **Preline UI** (`preline@2.7.0`, devDependency).

- **Warna & font** — `tailwind.config.js`, palet `brand` (primer `#0A2463`) dan
  `accent` (merah `#FB3640`).
- **Komponen** — `.btn`, `.card`, `.input`, `.table`, `.badge-*` di
  `assets/css/input.css` pada `@layer components`.
- **Varian Preline** — `require("preline/plugin")` di `tailwind.config.js`
  menyediakan `hs-dropdown-open:`, `hs-overlay-open:`, `hs-selected:`, dll.
- **JS Preline** — `assets/js/preline.js` (salinan
  `node_modules/preline/dist/index.js`). Bila paket diperbarui, salin ulang:

  ```bash
  npm install preline@2.7.0
  copy /Y node_modules\preline\dist\index.js assets\js\preline.js
  ```

Setelah mengubah view atau CSS, jalankan `npm run build`.

Catatan: Preline 3.x memakai `@custom-variant` (Tailwind 4) sehingga tidak
kompatibel dengan Tailwind 3.4 yang dipakai proyek ini.

Detail keputusan desain ada di
[`plans/ui-redesign-preline-2026-10-17.md`](plans/ui-redesign-preline-2026-10-17.md).

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

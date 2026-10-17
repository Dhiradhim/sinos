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
├── assets/              # css/app.css hasil build Tailwind
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

```bash
composer install     # mPDF
npm install
npm run build        # atau: npm run watch
```

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

## 6. Kustomisasi tema

Warna dan font di `tailwind.config.js` (palet `brand`). Komponen (`.btn`,
`.card`, `.form-input`, `.table-modern`) didefinisikan di
`assets/css/input.css` pada `@layer components`. Setelah mengubah, jalankan
`npm run build`.

## 7. Catatan kompatibilitas

- Diuji pada **PHP 8.5**. Ada patch kecil pada core CI3 untuk PHP 8.2+:
  penanganan `E_DEPRECATED` (dynamic property), `E_STRICT` (dihapus di PHP 8.4),
  dan `is_really_writable`/`mkdir()`. Patch ditandai komentar di
  `system/core/Common.php` dan `system/core/Exceptions.php`.
- Seluruh query memakai Query Builder; redirect memakai helper CI3; proteksi
  login dipusatkan di `MY_Controller`.

## 8. Lisensi

CodeIgniter 3 dirilis dengan lisensi MIT. Aplikasi SINOS milik Pengadilan Agama
Kupang.

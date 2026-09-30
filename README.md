# SINOS — Sistem Informasi Nomor Surat (CodeIgniter 3 + Tailwind CSS)

Versi migrasi dari aplikasi SINOS (procedural PHP + SB Admin 2) menjadi
**CodeIgniter 3 (MVC)** dengan tampilan modern **Tailwind CSS**.

## ✨ Yang Berubah

| Sebelum                                             | Sesudah                                         |
| --------------------------------------------------- | ----------------------------------------------- |
| PHP prosedural, logika + query + HTML tercampur     | Pola MVC (Controller / Model / View)            |
| `mysqli` + interpolasi string (rawan SQL Injection) | CI3 Query Builder (aman, ter-escape otomatis)   |
| Kredensial hardcoded di `koneksi.php`               | File `.env` + `config/database.php`             |
| Bootstrap 4 / SB Admin 2 + jQuery + DataTables      | Tailwind CSS + Alpine-less vanilla JS (ringan)  |
| `vendor/fontawesome`, DataTables                    | Tanpa dependensi berat front-end                |
| `display_errors` menyala di produksi                | Error hanya muncul di environment `development` |
| Sidebar/topbar/footer di-copy tiap halaman          | Layout terpusat (`views/layouts/*`)             |

Fitur tetap lengkap: Autentikasi, Ganti Password, Surat Keluar (ambil/sisip/daftar/edit/upload),
Surat Masuk (input/daftar/edit/disposisi/hapus), Manajemen User, Laporan PDF (mPDF).

## 📁 Struktur

Aplikasi ini terpasang di **root** folder `htdocs/sinos` (bukan subfolder `ci3`).

```
sinos/
├── application/
│   ├── config/          # config.php, database.php, routes.php, autoload.php
│   ├── controllers/     # Auth, Dashboard, Surat_keluar, Surat_masuk, User, Laporan
│   ├── core/            # MY_Controller, MY_Model
│   ├── helpers/         # sinos_helper.php (getRomawi, tanggal_indonesia, dll)
│   ├── libraries/       # Pdf.php (wrapper mPDF)
│   ├── models/          # M_user, M_nosur, M_surmas, M_jabatan, M_klasifikasi
│   └── views/           # layouts/, auth/, dashboard/, surat_keluar/, surat_masuk/, user/, laporan/
├── assets/              # css/app.css (hasil build Tailwind), js/app.js, img/, klasifikasi.pdf
├── db/migration_extra.sql
├── file/ file/sm/       # penyimpanan berkas PDF
├── system/              # core CodeIgniter 3.1.13
├── vendor/              # composer (mpdf)
├── .env.example
├── router.php           # router untuk `php -S` (development)
├── tailwind.config.js
└── package.json
```

## 🚀 Instalasi

### 1. Database

Database `sinos` yang sudah ada **dapat langsung dipakai** (tabel: `user`, `nosur`,
`surmas`, `jabatan`, `ref_klasifikasi`). Bila instalasi baru:

```bash
mysql -u root sinos < db/migration_extra.sql
```

> Catatan: dump lama `../db/sinos.sql` hanya memuat tabel `nosur` & `user` dengan skema
> berbeda. Untuk instalasi baru gunakan `db/migration_extra.sql` yang sesuai skema aplikasi.

### 2. Konfigurasi

Salin `.env.example` menjadi `.env` lalu sesuaikan:

```
DB_HOSTNAME=localhost
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=sinos
```

Sesuaikan `base_url` di `application/config/config.php` (default: `http://localhost/sinos/`).

### 3. Dependency PHP (mPDF)

```bash
composer install
```

### 4. Asset Tailwind CSS

```bash
npm install
npm run build      # sekali
npm run watch      # saat pengembangan
```

Hasil build: `assets/css/app.css`.

### 5. Folder tulis

Pastikan folder berikut dapat ditulis web server:
`file/`, `file/sm/`, `application/cache/`, `application/cache/sessions/`, `tmp/`.

### 6. Jalankan

- **XAMPP**: buka `http://localhost/sinos/`
- **Dev server PHP**:
  ```bash
  php -S localhost:8899 -t . router.php
  ```

## 🔐 Login

Akun mengikuti tabel `user`. Password mendukung **bcrypt** (`password_hash`) dan
**md5 lama** (akan otomatis di-upgrade ke bcrypt saat login berhasil).
Ganti password admin lama dengan:

```sql
UPDATE user SET pass = '$2y$...' WHERE nip='admin';
```

Atau gunakan menu **Ganti Password**.

## 🧭 Peta URL Utama

| URL                                                         | Fungsi                 |
| ----------------------------------------------------------- | ---------------------- |
| `/login`, `/logout`, `/ganti-password`                      | Autentikasi            |
| `/dashboard`                                                | Beranda / ringkasan    |
| `/surat-keluar/ambil`, `/sisip`, `/daftar`, `/daftar-semua` | Surat Keluar           |
| `/surat-keluar/edit/{id}`, `/upload/{id}`                   | Edit & upload berkas   |
| `/surat-masuk`, `/surat-masuk/daftar?tahun=2026`            | Surat Masuk            |
| `/surat-masuk/edit/{id}`, `/disposisi/{id}`, `/hapus/{id}`  | Kelola surat masuk     |
| `/user`, `/user/tambah`, `/user/edit/{id}`                  | Manajemen user (admin) |
| `/laporan`, `/laporan/cetak`                                | Laporan PDF (admin)    |

## 🎨 Kustomisasi Tema

Warna & font di `tailwind.config.js` (palet `brand`). Komponen (`.btn`, `.card`,
`.form-input`, `.table-modern`) didefinisikan di `assets/css/input.css` pada `@layer components`.
Setelah mengubah, jalankan `npm run build`.

## 🛠️ Catatan Kompatibilitas

- Diuji pada **PHP 8.5**. Dilakukan patch kecil pada core CI3 untuk PHP 8.2+:
  penanganan `E_DEPRECATED` (dynamic property), `E_STRICT` (removed di PHP 8.4),
  dan `is_really_writable`/`mkdir()`. Patch ditandai komentar di
  `system/core/Common.php` dan `system/core/Exceptions.php`.
- Keamanan: seluruh query memakai Query Builder (prepared statement), redirect
  memakai helper CI3, dan proteksi login dipusatkan di `MY_Controller`.

## 📄 Lisensi

CodeIgniter 3 dirilis dengan lisensi MIT. Aplikasi SINOS milik Pengadilan Agama Kupang.

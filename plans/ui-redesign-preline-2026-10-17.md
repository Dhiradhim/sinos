# Redesign UI SINOS dengan Preline UI — 17 Oktober 2026

## Tujuan

Mengganti lapisan visual SINOS dari design token shadcn-style ke **Preline UI**,
tanpa mengubah alur bisnis, validasi, penomoran surat, maupun struktur navigasi.

## Keputusan teknis

| Aspek          | Sebelum                                            | Sesudah                                                                   |
| -------------- | -------------------------------------------------- | ------------------------------------------------------------------------- |
| Design token   | CSS variable HSL (`--primary`, `--border`, …)      | Utility Preline + palet merek `brand` / `accent`                          |
| Komponen       | Kelas `.card`, `.btn-*`, `.badge-*` buatan sendiri | Kelas Preline + varian `hs-*` (dropdown, overlay, select)                 |
| Dropdown       | JS manual (`data-dropdown`)                        | `hs-dropdown` (Preline Dropdown)                                          |
| Modal          | `hidden`/`flex` manual                             | `hs-overlay` (Preline Overlay + backdrop)                                 |
| Select panjang | Searchable select buatan sendiri (≈190 baris JS)   | `hs-select` (Preline Advanced Select, pencarian bawaan)                   |
| Tema           | shadcn neutral                                     | Preline default: permukaan putih, garis `gray-200`, sudut `rounded-lg/xl` |

Preline dipasang sebagai **devDependency** (`preline@2.7.0`) karena hanya dipakai
saat build:

- [`tailwind.config.js`](../tailwind.config.js) memuat `require("preline/plugin")`
  agar varian `hs-dropdown-open`, `hs-overlay-open`, `hs-selected`, dll. tersedia.
- [`assets/js/preline.js`](../assets/js/preline.js) adalah salinan
  `node_modules/preline/dist/index.js` (309 KB) yang dimuat sebelum `app.js`.

> Preline 2.x sengaja tidak dipakai: plugin Tailwind-nya berbasis
> `@custom-variant` (Tailwind 4) dan tidak berjalan di Tailwind 3.4 yang
> digunakan proyek ini.

## Palet

Warna merek dipertahankan, kini sebagai skala penuh:

- `brand` 50–950 → primer `#0A2463` di `brand-900`
- `accent` 50–900 → merah `#FB3640` di `accent-500`

Aksen merah **hanya** dipakai untuk aksi sekunder/destruktif. Status netral
memakai `badge-muted` (abu), bukan merah.

## Perubahan per area

### Shell

- [`layouts/master.php`](../application/views/layouts/master.php): latar
  `bg-gray-50`, kartu putih, blok CSS DataTables diselaraskan ke Preline
  (garis `#e5e7eb`, header `#f9fafb`, paging `rounded-lg`).
- [`layouts/sidebar.php`](../application/views/layouts/sidebar.php): item aktif
  memakai `bg-brand-50` + teks `brand-900`; garis penanda kiri dihapus agar
  tidak bersaing dengan gaya Preline.
- [`layouts/topbar.php`](../application/views/layouts/topbar.php): menu pengguna
  menjadi `hs-dropdown` dengan `[--placement:bottom-right]`.
- [`layouts/logout_modal.php`](../application/views/layouts/logout_modal.php):
  `hs-overlay` penuh dengan backdrop bawaan Preline.
- [`layouts/flash.php`](../application/views/layouts/flash.php): alert Preline
  (`alert-success`, `alert-destructive`, …) tanpa animasi kustom.

### Halaman

- **Login** ([`auth/login.php`](../application/views/auth/login.php)): panel dua
  kolom — panel merek biru gelap + panel masuk. Di layar < `lg` panel merek
  disembunyikan.
- **Dashboard** ([`dashboard/index.php`](../application/views/dashboard/index.php)):
  stat card putih dengan ikon `rounded-lg`, tabel bergaris rambut.
- **Surat masuk / keluar**: `.table` Preline, kolom nomor surat `col-nowrap`
  agar tidak pecah dua baris, aksi sekunder di menu elipsis (`hs-dropdown`).
- **Form nomor surat** ([`surat_keluar/_form_nomor_fields.php`](../application/views/surat_keluar/_form_nomor_fields.php)):
  dua `fieldset` (Penanggung Jawab, Identitas Surat) dengan legenda berikon.

## Detail penting: `required` pada Preline Select

Preline menyembunyikan `<select>` asli (`display: none`). Bila `required`
dibiarkan, Chrome memblokir submit dengan pesan
_"An invalid form control with name='…' is not focusable"_ — form diam saja.

Solusi di [`assets/js/app.js`](../assets/js/app.js):

1. Saat `load`, `required` dipindahkan ke `data-required="1"` pada select.
2. Pada event `submit` (fase capture), select kosong diberi
   `aria-invalid="true"` pada toggle Preline dan difokuskan.
3. Toggle memakai varian `aria-[invalid=true]:border-red-500` sehingga tampil
   merah tanpa kelas tambahan.

Ini juga berlaku untuk form yang dimuat lewat AJAX (`sinosInitPreline()`).

## Verifikasi

- `npm run build` berhasil tanpa error (53 KB CSS).
- 20 view dirender dengan data contoh: **0 error PHP**.
- Semua `data-hs-select` menghasilkan JSON valid (0 invalid).
- Headless Chromium, 1440×900 dan 390×844:
  - overflow horizontal **0 px** di seluruh halaman;
  - select Preline terbuka, punya kolom cari, dan menulis nilai ke `<select>`;
  - dropdown topbar, overlay logout, dan sidebar mobile berfungsi;
  - validasi `required` menandai toggle merah dan menahan submit;
  - **0 error konsol** (kecuali 404 favicon di halaman login).
- Screenshot tersimpan di `ui-evidence/preline-redesign/`.

## Yang tidak diubah

- Struktur navigasi, label menu, dan URL.
- Alur bisnis, validasi server, dan penomoran surat.
- Palet merek (primer `#0A2463`, aksen `#FB3640`).
- Halaman login gateway SSO (`sso/views/login.php`) — di luar cakupan.

## Catatan jujur

- **Belum diuji di browser nyata** selain Chromium headless. Firefox/Safari
  belum diperiksa.
- **Belum ada uji 200% zoom** maupun uji keyboard menyeluruh (tab order pada
  select Preline mengandalkan perilaku bawaan plugin).
- **DataTables** masih memakai CSS dari CDN plus blok override lokal; bila
  tema DataTables berubah, blok di `master.php` perlu disesuaikan.
- Menu elipsis pada tabel surat keluar belum diterapkan (kolom aksi masih dua
  ikon dan muat di layar).

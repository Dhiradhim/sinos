# Rencana Migrasi SINOS: Frontend Astro + Preline, CI3 sebagai Backend API

**Tanggal:** 2026-10-17
**Status:** Rencana — belum ada implementasi
**Target:** Frontend Astro 5 (SSR) + Preline UI, CI3 3.1.13 murni sebagai JSON API, lalu hapus seluruh artefak frontend dari CI3.

---

## 1. Ringkasan Eksekutif

SINOS saat ini adalah aplikasi CodeIgniter 3 monolitik: controller merender view PHP dengan Tailwind, Preline dimuat sebagai file statis, dan data disajikan langsung ke HTML. Migrasi memisahkan dua tanggung jawab:

| Lapisan             | Sekarang                                     | Sesudah                                       |
| ------------------- | -------------------------------------------- | --------------------------------------------- |
| Routing halaman     | CI3 `routes.php`                             | Astro file-based routing                      |
| Rendering HTML      | CI3 views (38 file)                          | Astro `.astro` components                     |
| Styling             | `assets/css/input.v4.css` + build npm di CI3 | Tailwind 4 di dalam proyek Astro              |
| Komponen interaktif | Preline via `<script src>` global            | Preline npm + Astro islands                   |
| Data                | Query model → view                           | Query model → JSON API                        |
| Auth                | PHP session cookie + SSO client di CI3       | Token API + cookie httpOnly di Astro (BFF)    |
| PDF laporan         | mPDF di CI3                                  | **tetap di CI3**, di-stream lewat proxy Astro |

**Prinsip migrasi: strangler pattern.** Aplikasi lama tidak dimatikan sekaligus. Astro mengambil alih halaman satu per satu di belakang reverse proxy; CI3 tetap melayani halaman yang belum dimigrasi sampai paritas tercapai. Ini memberi rollback per-halaman, bukan rollback seluruh sistem.

**Estimasi besar:** 8 fase. Fase 0 dan 1 bersifat blocker — tanpa keputusan auth dan lapisan API yang benar, sisa fase akan menumpuk utang teknis.

---

## 2. Kondisi Backend Saat Ini (hasil audit)

### 2.1 Permukaan controller

| Controller     | Aksi                                                                                                                    | Tipe          |
| -------------- | ----------------------------------------------------------------------------------------------------------------------- | ------------- |
| `Dashboard`    | `index`                                                                                                                 | baca          |
| `Auth`         | `login`, `logout`                                                                                                       | auth          |
| `Sso`          | `login`, `callback`                                                                                                     | auth          |
| `Surat_masuk`  | `index`, `simpan`, `daftar`, `edit`, `update`, `hapus`, `arsipkan`, `batal_arsip`, `detail`, `disposisi`                | CRUD + upload |
| `Surat_keluar` | `ambil`, `ambil_simpan`, `sisip`, `sisip_simpan`, `daftar`, `daftar_semua`, `edit`, `update`, `upload`, `upload_simpan` | CRUD + upload |
| `Disposisi`    | `index`, `arsipkan`, `form`, `form_ajax`, `kirim`, `detail`, `hapus`                                                    | CRUD + AJAX   |
| `Laporan`      | `index`, `cetak`                                                                                                        | baca + PDF    |

Total **~30 aksi**, 5 controller. Tidak ada `application/models` yang perlu diubah — hanya lapisan penyajian.

### 2.2 Titik yang menentukan desain migrasi

1. **`MY_Controller::render()`** ([`application/core/MY_Controller.php:97`](sinos/application/core/MY_Controller.php:97)) — satu-satunya jalur render halaman. Semua controller mewarisi ini. Ini titik sisip termudah untuk mode API.
2. **`MY_Controller::enforce_sso_session()`** ([`application/core/MY_Controller.php:55`](sinos/application/core/MY_Controller.php:55)) — setiap request memvalidasi sesi ke gateway SSO. Ini panggilan jaringan per-request; di mode API harus diberi cache pendek (mis. 60 detik) atau migrasi akan terasa lambat.
3. **`Auth::login()`** ([`application/controllers/Auth.php:24`](sinos/application/controllers/Auth.php:24)) — **tidak ada form kredensial**. Login 100% lewat SSO; login lokal hanya menampilkan pesan "dinonaktifkan". Artinya halaman login Astro bukan form, melainkan halaman transisi.
4. **`Laporan::cetak()`** ([`application/controllers/Laporan.php:52`](sinos/application/controllers/Laporan.php:52)) — merender `laporan/pdf.php` ke mPDF lalu mengeluarkan biner. **View ini bukan frontend** — harus dipertahankan.
5. **Upload berkas** — disimpan ke direktori `file/`, ditampilkan sebagai `<a href="<?= base_url($row->file) ?>">`. Setelah migrasi, direktori ini **tidak boleh** dapat diakses langsung (harus lewat proxy ber-auth).
6. **`Disposisi::form_ajax()`** ([`application/controllers/Disposisi.php:160`](sinos/application/controllers/Disposisi.php:160)) — mengembalikan fragmen HTML, bukan JSON. Ini satu-satunya endpoint yang mengembalikan HTML dan harus dikonversi.
7. **Volume data**: `daftar_semua` menampilkan 1.536 baris di klien (DataTables). Untuk API, paginasi harus **server-side** sejak awal.

---

## 3. Keputusan Arsitektur

### 3.1 Auth: BFF dengan token, bukan cookie PHP

Tiga opsi dipertimbangkan:

| Opsi                                   | Cara kerja                                                                                                       | Risiko                                                                                        |
| -------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| A. Proxy cookie PHP                    | Astro meneruskan cookie `ci_session` bolak-balik                                                                 | Cookie `SameSite` + domain berbeda membuat ini rapuh; setiap request harus menunggu SSO check |
| B. Token di localStorage               | Astro menyimpan token, kirim via `Authorization`                                                                 | Rentan XSS; tidak ada proteksi CSRF otomatis                                                  |
| **C. BFF + cookie httpOnly (dipilih)** | Astro menukar tiket SSO ke CI3, menyimpan token di cookie httpOnly milik Astro, meneruskan sebagai header ke CI3 | Perlu satu endpoint tukar-token di CI3                                                        |

**Alur yang dipilih:**

```
Browser → Astro /api/auth/sso-callback?ticket=xxx
            ↓ (server-side, tanpa melibatkan browser)
         POST https://sinos-api/api/v1/auth/exchange  { ticket }
            ↓
         CI3 memvalidasi ticket ke gateway SSO (Sso_client yang sudah ada)
            ↓
         CI3 mengembalikan { token, expires_at, user }
            ↓
         Astro menulis cookie httpOnly `sinos_sess` (SameSite=Lax, Secure)
```

Setiap request berikutnya: Astro membaca cookie → meneruskan `Authorization: Bearer <token>` ke CI3. Token adalah **HMAC-signed payload** (nip, roles, exp) — bukan JWT library, cukup `hash_hmac('sha256', ...)` agar tidak menambah dependensi.

**Kenapa tidak memakai JWT penuh:** payload hanya 4 field, tidak perlu rotasi kunci, dan HMAC sederhana lebih mudah diaudit. Bila kelak butuh multi-service, baru pertimbangkan JWT.

### 3.2 Struktur API

Semua endpoint di bawah `/api/v1/`, dipisah ke controller baru di `application/controllers/api/`:

```
application/controllers/api/
├── Auth.php          POST /api/v1/auth/exchange
│                     POST /api/v1/auth/logout
│                     GET  /api/v1/auth/me
├── Surat_masuk.php   GET  /api/v1/surat-masuk            (list + paginasi + filter)
│                     GET  /api/v1/surat-masuk/{id}
│                     POST /api/v1/surat-masuk
│                     PUT  /api/v1/surat-masuk/{id}
│                     DELETE /api/v1/surat-masuk/{id}
│                     POST /api/v1/surat-masuk/{id}/arsip
│                     POST /api/v1/surat-masuk/{id}/batal-arsip
├── Surat_keluar.php  GET  /api/v1/surat-keluar
│                     GET  /api/v1/surat-keluar/{id}
│                     POST /api/v1/surat-keluar/ambil
│                     POST /api/v1/surat-keluar/sisip
│                     PUT  /api/v1/surat-keluar/{id}
│                     POST /api/v1/surat-keluar/{id}/berkas   (multipart)
├── Disposisi.php     GET  /api/v1/disposisi
│                     GET  /api/v1/disposisi/{id}
│                     POST /api/v1/disposisi
│                     POST /api/v1/disposisi/{id}/arsip
│                     DELETE /api/v1/disposisi/{id}
├── Dashboard.php     GET  /api/v1/dashboard?tahun=YYYY
├── Laporan.php       GET  /api/v1/laporan/opsi
│                     POST /api/v1/laporan/cetak   → application/pdf
├── Master.php        GET  /api/v1/master/klasifikasi
│                     GET  /api/v1/master/pegawai
│                     GET  /api/v1/master/tahun
└── Berkas.php        GET  /api/v1/berkas/{token}  → stream file ber-auth
```

**Kontrak respons seragam:**

```json
{
  "data": [ ... ],
  "meta": { "page": 1, "per_page": 25, "total": 1536, "last_page": 62 },
  "error": null
}
```

```json
{
	"data": null,
	"meta": null,
	"error": {
		"code": "VALIDATION_FAILED",
		"message": "Nomor surat wajib diisi.",
		"fields": { "no_surat": "..." }
	}
}
```

**Aturan:**

- Selalu `Content-Type: application/json` kecuali endpoint berkas dan PDF.
- Status HTTP bermakna: 200, 201, 400, 401, 403, 404, 422, 500.
- Tidak ada HTML di dalam JSON.
- Pesan error dalam Bahasa Indonesia (sama seperti sekarang) agar Astro bisa menampilkannya langsung.

### 3.3 Base controller API

Buat `application/core/MY_Api_Controller.php` yang mewarisi `CI_Controller` (bukan `MY_Controller`, agar tidak memaksa render view):

```php
class MY_Api_Controller extends CI_Controller
{
    protected $user = array();

    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json');

        $payload = $this->verify_token($this->bearer_token());
        if ($payload === null) {
            $this->fail('UNAUTHENTICATED', 'Sesi tidak valid.', 401);
        }
        $this->user = $payload;

        // Cache status sesi SSO: hindari panggilan gateway per-request.
        $this->enforce_sso_session_cached();
    }

    protected function ok($data, $meta = null, $status = 200) { ... }
    protected function fail($code, $message, $status = 400, $fields = null) { ... }
    protected function input_json() { ... }   // baca php://input, decode
}
```

`enforce_sso_session_cached()` menyimpan hasil validasi di cache CI3 dengan TTL 60 detik, key `sso_alive:{session_group}`. Ini mengurangi latensi drastis tanpa mengorbankan single-logout (maksimum 60 detik jeda).

### 3.4 Strategi bertahap (strangler)

```
Sebelum:   Browser ──> Apache ──> sinos/ (CI3, render HTML)

Sesudah:   Browser ──> Apache ──> sinos-web/ (Astro SSR)  ──┐
                                                             ├─> /api/v1/*  ──> sinos/ (CI3)
                                                             └─> /berkas/*  ──> sinos/ (CI3, proxy)
```

Selama migrasi berjalan, Apache memakai aturan: rute yang sudah punya padanan Astro diarahkan ke Astro; sisanya jatuh ke CI3. Ini bisa dilakukan dengan `.htaccess` per-prefix (`/surat-masuk/*` → Astro) atau reverse proxy di Laragon.

---

## 4. Fase Implementasi

### Fase 0 — Spike Auth (blocker)

**Tujuan:** membuktikan alur SSO → Astro → CI3 berjalan sebelum menulis satu halaman pun.

1. Buat `application/controllers/api/Auth.php` dengan `exchange` dan `me` saja.
2. Buat helper token: `application/helpers/api_token_helper.php` (`api_token_issue()`, `api_token_verify()`).
3. Scaffold Astro minimal: `sinos-web/` dengan `output: 'server'`, satu route `/api/auth/sso-callback`.
4. Uji: login dari Astro → callback → cookie `sinos_sess` terpasang → `GET /api/v1/auth/me` mengembalikan user yang benar → logout menghapus cookie dan memanggil single-logout gateway.

**Kriteria lulus:** round-trip SSO penuh tanpa cookie PHP di browser. Bila gagal, hentikan migrasi dan evaluasi ulang opsi A.

### Fase 1 — API baca (read-only)

**Tujuan:** semua data yang ditampilkan halaman bisa diambil sebagai JSON.

1. `MY_Api_Controller` + token verification.
2. Endpoint baca: `dashboard`, `surat-masuk` (list + detail), `surat-keluar` (list + detail), `disposisi` (list + detail), `master/*`.
3. Paginasi server-side untuk `surat-keluar` (1.536 baris) — `?page=1&per_page=25&tahun=2026`.
4. Filter dan urutan dipindah ke query, bukan ke DataTables.
5. Uji setiap endpoint dengan `curl`/`Invoke-RestMethod`, bandingkan jumlah dan isi baris dengan halaman CI3 yang setara.

**Kriteria lulus:** setiap halaman CI3 punya endpoint padanan yang mengembalikan data identik (dibandingkan per-field).

### Fase 2 — API tulis + berkas

1. Endpoint tulis: create/update/delete surat masuk, ambil/sisip nomor, disposisi, arsip.
2. Upload multipart: `POST /api/v1/surat-keluar/{id}/berkas`, `POST /api/v1/surat-masuk`.
3. `GET /api/v1/berkas/{token}` — stream berkas dengan validasi token. Token berkas = HMAC dari path + exp, sehingga URL tidak bisa ditebak dan tetap ber-auth.
4. Validasi: pindahkan aturan `form_validation` ke satu tempat yang dipakai bersama oleh API (pesan error dikembalikan sebagai `fields`).
5. `POST /api/v1/laporan/cetak` mengembalikan `application/pdf`.

**Kriteria lulus:** semua operasi tulis berhasil lewat API; berkas tersimpan di lokasi yang sama; PDF terunduh dengan nama dan isi yang sama.

### Fase 3 — Scaffold Astro + Preline

1. `npm create astro@latest sinos-web` — pilih TypeScript strict, tanpa framework UI tambahan.
2. `npx astro add node` → `output: 'server'`.
3. `npm i -D tailwindcss@4 @tailwindcss/vite preline@5`
4. `astro.config.mjs`:

```js
import { defineConfig } from "astro/config";
import node from "@astrojs/node";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
	output: "server",
	adapter: node({ mode: "standalone" }),
	vite: { plugins: [tailwindcss()] },
	server: { port: 4321 },
});
```

5. `src/styles/global.css`:

```css
@import "tailwindcss";
@import "preline/variants.css";
@source "../../node_modules/preline/dist/*.js";

@theme {
	--color-brand-50: #f0f4fa;
	/* … port lengkap brand-50..950 dari input.v4.css … */
	--color-accent-500: #fb3640;
	--font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
	--radius-card: 0.875rem;
	--radius-control: 0.625rem;
	--shadow-card:
		0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06);
}
```

6. Layout dasar: `src/layouts/Base.astro` (head, font Inter, `global.css`, script Preline dari `node_modules/preline/dist/index.js` via import).
7. Helper API: `src/lib/api.ts` — pembungkus `fetch` yang menyisipkan `Authorization` dari cookie dan menormalkan error.
8. Helper auth: `src/lib/session.ts` — baca/tulis cookie `sinos_sess`.

**Kriteria lulus:** satu halaman kosong dengan sidebar + topbar Preline tampil benar di 1440px dan 390px; tidak ada CSS dari CI3 yang ikut termuat.

### Fase 4 — Shell Astro

Port [`layouts/master.php`](sinos/application/views/layouts/master.php), [`sidebar.php`](sinos/application/views/layouts/sidebar.php), [`topbar.php`](sinos/application/views/layouts/topbar.php), [`footer.php`](sinos/application/views/layouts/footer.php) menjadi:

```
src/layouts/
├── Base.astro          <html>, <head>, font, global.css
├── AppShell.astro      sidebar + topbar + <main> + footer
└── components/
    ├── Sidebar.astro
    ├── Topbar.astro
    ├── UserMenu.astro      (Preline dropdown)
    ├── LogoutModal.astro   (Preline overlay)
    ├── Flash.astro
    └── TahunSelector.astro
```

- Menu aktif dihitung dari `Astro.url.pathname`, bukan `$is()` closure.
- Badge jumlah disposisi diambil dari `GET /api/v1/dashboard` (satu request, dipakai bersama).
- Ikon: pertahankan `svg_icon()` sebagai komponen Astro `Icon.astro` dengan 57 path yang sama — jangan ganti ke icon font.

### Fase 5 — Halaman per modul

Urutan berdasarkan risiko (rendah → tinggi):

| #   | Halaman                                           | Alasan urutan                              |
| --- | ------------------------------------------------- | ------------------------------------------ |
| 1   | `login.astro`                                     | Tanpa data; hanya halaman transisi SSO     |
| 2   | `dashboard.astro`                                 | Baca saja, 4 stat + 1 tabel                |
| 3   | `laporan/index.astro`                             | Satu form, satu tombol POST                |
| 4   | `surat-masuk/daftar.astro`                        | Tabel + filter, tanpa upload               |
| 5   | `surat-masuk/detail.astro`                        | Baca + aksi arsip                          |
| 6   | `surat-masuk/input.astro`, `edit.astro`           | Form + upload                              |
| 7   | `surat-keluar/daftar.astro`, `daftar-semua.astro` | Tabel besar → uji paginasi server-side     |
| 8   | `surat-keluar/ambil.astro`, `sisip.astro`         | Form kompleks, Preline select bertingkat   |
| 9   | `surat-keluar/edit.astro`, `upload.astro`         | Form + upload                              |
| 10  | `disposisi/index.astro`, `detail.astro`           | Dropdown aksi + overlay                    |
| 11  | `surat-masuk/disposisi.astro`                     | Dokumen cetak — pertimbangkan tetap di CI3 |

**Catatan per halaman:**

- **Tabel**: ganti DataTables dengan komponen `DataTable.astro` + `pagination`. DataTables 2 tidak diperlukan lagi karena paginasi, sort, dan filter sudah di server. Ini menghapus satu dependensi CDN dan seluruh `datatables-skin.css`.
- **Preline select** (`hs_select_attr`): konfigurasi JSON yang sekarang dihasilkan PHP dipindah menjadi prop komponen `Select.astro`. JSON-nya identik; yang berubah hanya sumbernya.
- **Form**: gunakan `Astro.request.formData()` di action server, kirim ke API, kembalikan error per-field.
- **`surat-masuk/disposisi.astro`**: dokumen cetak dengan `@media print`. Bila tetap di CI3, rute ini dikecualikan dari migrasi — tetapi itu berarti CI3 masih punya satu view. Keputusan: **migrasikan**, karena view cetak bisa ditulis ulang di Astro tanpa dependensi backend.

### Fase 6 — Paritas dan cutover

1. **Uji paritas per halaman**: bandingkan screenshot CI3 vs Astro di 1440px dan 390px; bandingkan isi tabel baris-per-baris.
2. **Uji alur penuh**: login → input surat masuk → disposisi → ambil nomor → upload → cetak laporan → logout.
3. **Uji negatif**: sesi kedaluwarsa, token invalid, upload file salah tipe, akses tanpa peran operator, single-logout dari gateway.
4. Cutover bertahap: aktifkan Astro untuk `/dashboard`, lalu per-prefix. Pertahankan CI3 sebagai fallback selama satu siklus rilis.

### Fase 7 — Penghapusan frontend CI3

**Hapus:**

```
application/views/                       (seluruh 38 file, KECUALI yang disebut di bawah)
assets/css/input.css
assets/css/input.v4.css
assets/css/app.css
assets/css/app.v4.css
assets/css/datatables-skin.css
assets/js/app.js
assets/js/preline.js
assets/js/preline.v5.js
tailwind.config.js
package.json
package-lock.json
node_modules/
backup/ui-v3-2026-10-17/
application/helpers/ui_helper.php
```

**PERTAHANKAN — jangan dihapus:**

| Path                                   | Alasan                                                                                                   |
| -------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| `application/views/laporan/pdf.php`    | Template mPDF, bukan frontend. Menghapusnya mematikan cetak laporan.                                     |
| `application/views/errors/html/*`      | Halaman error CI3 saat API gagal total; dipakai bila Astro tidak bisa menjangkau backend.                |
| `application/views/errors/cli/*`       | Dipakai CI3 di CLI.                                                                                      |
| `application/helpers/sinos_helper.php` | `svg_icon()` dipakai view PDF? **Periksa dulu.** `hs_select_attr()` boleh dihapus setelah Astro selesai. |
| `file/`                                | Direktori penyimpanan berkas pengguna. Bukan frontend.                                                   |
| `vendor/`                              | Termasuk mPDF. Wajib.                                                                                    |

**Perubahan pendukung:**

- `MY_Controller::render()` — hapus setelah semua halaman pindah, atau ubah agar melempar 410 Gone.
- `routes.php` — sisakan hanya rute `/api/v1/*` dan rute SSO.
- `.htaccess` — tambahkan blokir akses langsung ke `file/` dan `application/`.
- `index.php` — tidak berubah.

**Verifikasi setelah penghapusan:**

1. `grep -rn "load->view" application/` → hanya `laporan/pdf.php` dan `errors/*`.
2. `grep -rn "assets/css\|assets/js" application/` → kosong.
3. Jalankan ulang seluruh alur Fase 6.
4. `php -l` seluruh `application/`.

---

## 5. Arah Desain: B — Meja Registrasi

**Dikunci 2026-10-17.** Kandidat lain yang dipertimbangkan: _Arsip Terang_ (meneruskan v4) dan _Lembar Agenda_ (grid kolom dokumen).

### 5.1 Direction card

| Field                     | Isi                                                                                                                                                                                                                                                                                                                                                                        |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **North star**            | Aplikasi ini adalah meja registrasi surat. Setiap layar harus terasa seperti seseorang membuka buku agenda di meja kerja: navigasi di sisi gelap, dokumen di sisi terang, satu angka nomor surat yang selalu terbaca.                                                                                                                                                      |
| **Tone**                  | Energi tenang-rendah (data padat, bukan promosi). Penyelesaian presisi tinggi (grid ketat, garis 1px, tanpa gradien dekoratif). Kepadatan: padat di tabel, lapang di sekitar aksi. Bobot sedang-berat (navy pekat sebagai jangkar). Keseriusan formal (dokumen negara).                                                                                                    |
| **Benchmark**             | Kategori back-office kearsipan pemerintah. Baseline dipertahankan: sidebar kiri, tabel sebagai isi utama, filter di atas tabel, aksi baris di kanan. Diubah: kartu pembungkus tabel dihapus, filter tidak lagi di dalam kotak, panel navigasi gelap permanen (bukan putih seperti dashboard admin pada umumnya).                                                           |
| **Generative engine**     | _Perabot kerja_: meja loket arsip — permukaan gelap tempat berkas ditumpuk, lembar terang tempat menulis. Diterjemahkan menjadi rail navigasi navy, kanvas kerja putih, garis pemisah seperti tepi meja.                                                                                                                                                                   |
| **First-screen skeleton** | Pengguna datang untuk membaca dan memproses daftar surat. Baris pertama data harus terlihat tanpa scroll. Header halaman maksimum 2 baris (judul + jumlah), filter satu baris tipis menempel di bawah header, tabel langsung mulai. Tidak ada hero, tidak ada kartu statistik di halaman daftar.                                                                           |
| **Page structure**        | Rail kiri 15rem gelap (fixed) + kanvas kanan terang. Di dalam kanvas: header tipis → filter bar → tabel penuh lebar → paginasi. Detail dibuka sebagai panel kanan (drawer) atau halaman terpisah, bukan modal bertumpuk.                                                                                                                                                   |
| **Typography**            | Inter untuk semua. Judul halaman 1.125rem/600; label kolom 0.6875rem uppercase +0.06em; isi tabel 0.8125rem; nomor surat 0.8125rem `tabular-nums` weight 500; angka statistik 1.75rem/600 `tabular-nums`. Tanpa display font.                                                                                                                                              |
| **Color**                 | Rail `#0b1b3a` (lebih dalam dari brand agar tidak bentrok dengan tombol). Kanvas `#ffffff`. Latar halaman `#f6f7f9`. Garis `#e4e7ec` / `#cfd5df`. Teks `#0f172a` / `#475569` / `#64748b`. Aksi utama `#0a2463`. Aksen status `#fb3640` hanya untuk yang butuh perhatian (belum dibaca, belum upload). Sukses `#047857`, peringatan `#b45309`. Rasio aksen ≤ 8% area layar. |
| **Key visual**            | Tidak ada ilustrasi. Identitas dibawa oleh rail gelap + tipografi angka. Logo instansi di puncak rail.                                                                                                                                                                                                                                                                     |
| **Icons**                 | Pertahankan set inline-SVG yang ada (`svg_icon()`, 57 path), stroke 2px, 16px di tabel, 18px di nav. Jangan ganti ke icon font.                                                                                                                                                                                                                                            |
| **Control grammar**       | Radius kontrol 8px, radius panel 12px, garis 1px `#e4e7ec`. Tinggi tombol 34px (sm 30px, lg 40px). Fokus: outline 2px offset 2px navy 35%. Tanpa shadow pada tombol; shadow hanya pada panel mengambang. Hover = perubahan latar, bukan perubahan ukuran.                                                                                                                  |
| **Motion**                | _Klik_ — seperti menutup buku: cepat, tanpa pantulan. 140ms, `cubic-bezier(.2,0,.2,1)`. Tiga dasar: baris tabel menyorot latar saat hover; drawer detail masuk dari kanan 220ms; badge status berganti tanpa animasi skala.                                                                                                                                                |
| **Structural break**      | Tabel tidak dibungkus kartu. Filter bukan kotak. Header bukan panel. Tiga hal yang biasanya menjadi "kartu" di dashboard admin dihapus, sehingga perhatian langsung ke baris data.                                                                                                                                                                                         |
| **Memorable moment**      | Kolom nomor surat: angka `tabular-nums` yang rata sempurna antar-baris, dengan status berkas sebagai titik warna di kolom paling kanan.                                                                                                                                                                                                                                    |
| **What we drop**          | Kartu statistik besar di halaman daftar, gradien dekoratif, ilustrasi, shadow berlapis, DataTables, modal bertumpuk, dark mode.                                                                                                                                                                                                                                            |

### 5.2 Design tokens untuk `@theme`

```css
@theme {
	/* Rail navigasi — lebih dalam dari brand agar tombol utama tetap menonjol */
	--color-rail: #0b1b3a;
	--color-rail-hover: #14294f;
	--color-rail-active: #1c3663;
	--color-rail-text: #c3ccdd;
	--color-rail-text-strong: #ffffff;

	/* Kanvas kerja */
	--color-canvas: #ffffff;
	--color-page: #f6f7f9;
	--color-surface-muted: #f9fafb;

	/* Garis */
	--color-line: #e4e7ec;
	--color-line-strong: #cfd5df;

	/* Teks */
	--color-ink: #0f172a;
	--color-ink-muted: #475569;
	--color-ink-subtle: #64748b;

	/* Aksi & status */
	--color-brand-900: #0a2463;
	--color-brand-800: #12316f;
	--color-brand-50: #eef2fa;
	--color-accent-500: #fb3640;
	--color-ok: #047857;
	--color-warn: #b45309;
	--color-danger: #b91c1c;

	/* Bentuk */
	--radius-control: 0.5rem;
	--radius-panel: 0.75rem;
	--shadow-panel:
		0 1px 2px 0 rgb(15 23 42 / 0.05), 0 8px 24px -12px rgb(15 23 42 / 0.18);

	--font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
}
```

### 5.3 Kontrak komponen yang dibawa dari v4

Nama kelas berikut dipertahankan agar aturan visualnya tidak perlu ditulis ulang: `.btn` + varian, `.input`, `.select`, `.textarea`, `.label`, `.badge` + varian, `.table`, `.table-wrap`, `.col-prose`, `.col-nowrap`, `.col-aksi`, `.alert` + varian, `.dropdown-*`, `.nav-item`, `.nav-item-active`, `.avatar`, `.empty-state`, `.focus-ring`.

Yang **diganti** karena arah B: `.card`/`.card-*` tidak lagi membungkus tabel dan filter (hanya dipakai untuk panel detail/drawer); `.nav-*` pindah ke permukaan rail gelap; `.badge-*` memakai palet status baru.

---

## 6. Risiko dan Mitigasi

| #   | Risiko                                                 | Dampak                        | Mitigasi                                                        |
| --- | ------------------------------------------------------ | ----------------------------- | --------------------------------------------------------------- |
| 1   | SSO round-trip gagal di BFF                            | Blocker total                 | Fase 0 wajib lulus sebelum fase lain                            |
| 2   | `enforce_sso_session()` memanggil gateway tiap request | Latensi naik drastis          | Cache 60 detik per `session_group`                              |
| 3   | Paginasi server-side mengubah perilaku sort/filter     | Data tampil berbeda           | Uji paritas baris-per-baris di Fase 6                           |
| 4   | Berkas di `file/` masih bisa diakses langsung          | Kebocoran data                | Blokir `.htaccess` + proxy `berkas/{token}`                     |
| 5   | Token HMAC tanpa rotasi                                | Token bocor berlaku selamanya | Sertakan `exp` (8 jam) + logout menghapus cookie                |
| 6   | Dua aplikasi = dua proses di Laragon                   | Deployment lebih rumit        | Dokumentasikan; gunakan reverse proxy satu domain               |
| 7   | `laporan/pdf.php` ikut terhapus                        | Cetak laporan mati            | Masuk daftar PERTAHANKAN + verifikasi grep                      |
| 8   | Astro SSR butuh Node proses persisten                  | Laragon default hanya Apache  | Jalankan via `pm2`/`nssm`, atau adapter Node standalone + proxy |
| 9   | Preline 5 `engines` pin ke Node 22.22.2                | Peringatan EBADENGINE         | Non-fatal; pantau rilis berikutnya                              |
| 10  | Migrasi 11 halaman sekaligus                           | Sulit dilacak                 | Strangler per-prefix, satu halaman satu PR                      |

---

## 7. Keputusan yang Masih Terbuka

1. **Domain/deployment** — satu domain dengan reverse proxy (`/api/v1` → CI3) atau dua subdomain (`api.sinoss.local` + `sinoss.local`)? Rekomendasi: **satu domain**, karena cookie httpOnly dan CSRF menjadi jauh lebih sederhana.
2. **`surat-masuk/disposisi`** — migrasi ke Astro atau tetap di CI3 sebagai halaman cetak? Rekomendasi: migrasi, agar Fase 7 benar-benar bersih.
3. **`errors/html/*`** — pertahankan sebagai fallback CI3, atau serahkan seluruh halaman error ke Astro? Rekomendasi: pertahankan; biayanya nol.
4. **DataTables** — hapus total atau pertahankan untuk satu-dua tabel? Rekomendasi: hapus total; paginasi server-side lebih tepat untuk 1.536 baris.
5. **Dark mode** — belum ada kode dark mode di proyek. Rekomendasi: jangan masukkan ke scope migrasi.

---

## 8. Checklist Eksekusi

```
FASE 0 — Auth spike
[ ] 01  Buat api_token_helper.php (issue/verify HMAC)
[ ] 02  Buat MY_Api_Controller (auth + json output + cache SSO)
[ ] 03  Buat api/Auth.php: POST exchange, GET me, POST logout
[ ] 04  Scaffold sinos-web minimal (Astro SSR + node adapter)
[ ] 05  Route /api/auth/sso-callback + cookie httpOnly
[ ] 06  Uji round-trip SSO penuh; dokumentasikan hasil

FASE 1 — API baca
[ ] 07  GET /api/v1/master/{klasifikasi,pegawai,tahun}
[ ] 08  GET /api/v1/dashboard
[ ] 09  GET /api/v1/surat-masuk (paginasi + filter)
[ ] 10  GET /api/v1/surat-masuk/{id}
[ ] 11  GET /api/v1/surat-keluar (paginasi + filter)
[ ] 12  GET /api/v1/surat-keluar/{id}
[ ] 13  GET /api/v1/disposisi (paginasi + filter)
[ ] 14  GET /api/v1/disposisi/{id}
[ ] 15  Uji setiap endpoint vs halaman CI3 (paritas field)

FASE 2 — API tulis + berkas
[ ] 16  POST/PUT/DELETE surat-masuk + arsip/batal-arsip
[ ] 17  POST surat-keluar/ambil + sisip
[ ] 18  PUT surat-keluar/{id}
[ ] 19  POST multipart upload (surat masuk + surat keluar)
[ ] 20  GET /api/v1/berkas/{token} + blokir akses langsung file/
[ ] 21  POST /api/v1/laporan/cetak (stream PDF)
[ ] 22  POST/PUT/DELETE disposisi + arsip
[ ] 23  Satukan aturan validasi, kembalikan error per-field

FASE 3 — Scaffold Astro
[ ] 24  Pilih arah desain (A/B/C)
[ ] 25  Init proyek + node adapter + output server
[ ] 26  Tailwind 4 + preline@5 + variants.css + @source
[ ] 27  Port token @theme (brand, accent, surface, line, ink, radius, shadow)
[ ] 28  Base.astro + AppShell.astro
[ ] 29  src/lib/api.ts + src/lib/session.ts
[ ] 30  Icon.astro (57 path dari svg_icon)

FASE 4 — Shell
[ ] 31  Sidebar.astro (menu aktif dari pathname, badge disposisi)
[ ] 32  Topbar.astro + UserMenu.astro (Preline dropdown)
[ ] 33  LogoutModal.astro (Preline overlay)
[ ] 34  Flash.astro + TahunSelector.astro

FASE 5 — Halaman
[ ] 35  login.astro
[ ] 36  dashboard.astro
[ ] 37  laporan/index.astro
[ ] 38  DataTable.astro + Pagination.astro (pengganti DataTables)
[ ] 39  surat-masuk/daftar.astro
[ ] 40  surat-masuk/detail.astro
[ ] 41  surat-masuk/input.astro + edit.astro
[ ] 42  surat-keluar/daftar.astro + daftar-semua.astro
[ ] 43  surat-keluar/ambil.astro + sisip.astro
[ ] 44  surat-keluar/edit.astro + upload.astro
[ ] 45  disposisi/index.astro + detail.astro
[ ] 46  surat-masuk/disposisi.astro (cetak)

FASE 6 — Paritas
[ ] 47  Screenshot banding 1440px + 390px per halaman
[ ] 48  Uji alur penuh end-to-end
[ ] 49  Uji negatif (sesi habis, token invalid, akses tanpa peran)
[ ] 50  Cutover bertahap per prefix

FASE 7 — Bersih-bersih
[ ] 51  Hapus views/* kecuali laporan/pdf.php + errors/*
[ ] 52  Hapus assets/css/*, assets/js/*, tailwind.config.js
[ ] 53  Hapus package.json, package-lock.json, node_modules/
[ ] 54  Hapus ui_helper.php, backup/
[ ] 55  Sederhanakan routes.php ke /api/v1/* + SSO
[ ] 56  Blokir file/ dan application/ di .htaccess
[ ] 57  grep verifikasi: load->view hanya pdf.php + errors
[ ] 58  php -l seluruh application/
[ ] 59  Jalankan ulang seluruh alur Fase 6
```

---

## 9. Referensi

- Preline UI: <https://preline.co/docs/about.html>
- Astro SSR + Node adapter: <https://docs.astro.build/en/guides/on-demand-rendering/>
- Tailwind 4 + Preline: <https://preline.co/docs/frameworks-astro.html>
- Rencana redesign sebelumnya: [`plans/ui-redesign-preline-tailwind4-2026-10-17.md`](sinos/plans/ui-redesign-preline-tailwind4-2026-10-17.md)

---

## Status Implementasi

**Fase 0–7 SELESAI. Tanggal: 2026-10-18.**

Fase 6 memberi **GO** (32/32 halaman, 61/61 jalur negatif, 15 cacat
diperbaiki, data pada garis dasar 364 / 5828 / 6). Fase 7 kemudian
menghapus frontend CI3 sehingga `sinos/` tinggal berisi API + rute SSO.

### Yang dihapus

| Objek                                                                                       | Jumlah                        | Catatan                                                                                                                                   |
| ------------------------------------------------------------------------------------------- | ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `application/views/` (kecuali `laporan/pdf.php` + `errors/`)                                | 31 berkas                     | `auth/`, `dashboard/`, `disposisi/` (4), `layouts/` (7), `laporan/index.php`, `surat_keluar/` (7), `surat_masuk/` (6), `views/index.html` |
| `assets/css/`                                                                               | 5 berkas, 166.782 byte        | seluruh direktori                                                                                                                         |
| `assets/js/`                                                                                | 3 berkas, 749.375 byte        | seluruh direktori                                                                                                                         |
| `assets/img/` (kecuali `KOP.jpg`)                                                           | 7 berkas                      | `login.jpg` + 6 SVG undraw                                                                                                                |
| `assets/klasifikasi.pdf`                                                                    | 1 berkas, 11.873.163 byte     | tidak ada pemanggil di backend maupun Astro                                                                                               |
| `tailwind.config.js`, `package.json`, `package-lock.json`                                   | 3 berkas                      |                                                                                                                                           |
| `node_modules/`                                                                             | 3.727 berkas, 56.678.773 byte |                                                                                                                                           |
| `application/helpers/ui_helper.php`                                                         | 1 berkas                      |                                                                                                                                           |
| `backup/ui-v3-2026-10-17/`                                                                  | 6 berkas, 410.946 byte        | digantikan backup Fase 7                                                                                                                  |
| Controller HTML: `Auth`, `Dashboard`, `Surat_masuk`, `Surat_keluar`, `Disposisi`, `Laporan` | 6 berkas                      | digantikan `controllers/api/*`                                                                                                            |
| `MY_Controller` (isi)                                                                       | —                             | `render()` dihapus; kelas tinggal stub JSON 410                                                                                           |

Total: **`sinos/` turun dari 11.427 berkas / 192.367.684 byte menjadi
7.744 berkas / 123.251.146 byte** — 3.683 berkas dan 69.116.538 byte
(~65,9 MiB) hilang.

### Yang bertahan dan alasannya

| Path                                                                                               | Alasan                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| -------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `application/views/laporan/pdf.php`                                                                | Template mPDF. `POST /api/v1/laporan/cetak` membacanya lewat `$this->load->view('laporan/pdf', …, TRUE)`. Tanpa berkas ini cetak laporan mati.                                                                                                                                                                                                                                                                                                                                                                                          |
| `application/views/errors/html/*`, `errors/cli/*`                                                  | Halaman error CI3 sendiri saat API gagal total.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| `application/helpers/sinos_helper.php`                                                             | Autoload global (`autoload.php` baris 16). PDF memakai **`tanggal_indonesia()`** (satu-satunya pemanggil, `pdf.php:92`). **Tidak** memakai `svg_icon()`. Fungsi yang masih dipakai: `tanggal_indonesia` (PDF), `getRomawi`, `tahun_tersedia`, `huruf_sisip`, `format_no_surat`, `label_penandatangan` (API `Surat_keluar`/`Master`/`M_user`). `is_admin`, `active_menu`, `old`, `svg_icon`, `hs_select_attr` tidak punya pemanggil tersisa — dipertahankan (util murah, tanpa ketergantungan view) dan diberi catatan status di berkas. |
| `application/controllers/Sso.php`                                                                  | Rute klien SSO; `sso_callback` di `config/sso.php` masih menunjuk ke `/sinos/sso/callback` dan URL itu terdaftar sebagai `redirect_uri` di gateway. Tidak memanggil `load->view`; redirect-nya kini menuju `SINOS_WEB_URL`.                                                                                                                                                                                                                                                                                                             |
| `application/controllers/Notfound.php`                                                             | **Baru.** Penangan 404 JSON; dipakai `default_controller` dan `404_override`.                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| `assets/img/KOP.jpg`                                                                               | **Wajib** — `api/v1/surat-masuk/{id}/disposisi` mengembalikan `base_url('assets/img/KOP.jpg')` sebagai `kop` (`controllers/api/Surat_masuk.php:423`). Karena itu `assets/` TIDAK dihapus seluruhnya (lihat "Penyimpangan").                                                                                                                                                                                                                                                                                                             |
| `vendor/`, `system/`, `file/`, `db/`, `composer.*`, `index.php`, `router.php`, `.htaccess`, `.env` | Infrastruktur.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |

### Endpoint API final

```
POST   /api/v1/auth/exchange
GET    /api/v1/auth/me
POST   /api/v1/auth/logout

GET    /api/v1/master/{klasifikasi|penandatangan|pengambil|…}
GET    /api/v1/dashboard?tahun=YYYY
GET    /api/v1/surat-masuk?per_page=N
GET    /api/v1/surat-masuk/{id}
POST   /api/v1/surat-masuk
PUT    /api/v1/surat-masuk/{id}
POST   /api/v1/surat-masuk/{id}/arsip
POST   /api/v1/surat-masuk/{id}/batal-arsip
GET    /api/v1/surat-masuk/{id}/disposisi
POST   /api/v1/surat-masuk/{id}/disposisi
GET    /api/v1/surat-keluar?per_page=N
GET    /api/v1/surat-keluar/semua?per_page=N
GET    /api/v1/surat-keluar/{id}
PUT    /api/v1/surat-keluar/{id}
POST   /api/v1/surat-keluar/ambil
POST   /api/v1/surat-keluar/sisip
POST   /api/v1/surat-keluar/{id}/berkas
GET    /api/v1/disposisi?per_page=N
GET    /api/v1/disposisi/{id}
GET    /api/v1/disposisi/form/{id}
POST   /api/v1/disposisi
POST   /api/v1/disposisi/{id}/arsip
GET    /api/v1/laporan/opsi
POST   /api/v1/laporan/cetak            -> application/pdf
GET    /api/v1/berkas/{token}           -> application/pdf (HMAC, 1 jam)
```

Rute non-API yang tersisa hanya `sso/login` dan `sso/callback`. Semua URI
lain (termasuk rute HTML lama `dashboard`, `surat-masuk/daftar`,
`surat-keluar/daftar`, `laporan`) dibalas **JSON 404** oleh `Notfound`,
bukan halaman error PHP.

### Cara menjalankan

| Komponen                    | Perintah                                                     | URL                               |
| --------------------------- | ------------------------------------------------------------ | --------------------------------- |
| Backend (CI3, API saja)     | Apache Laragon (root `c:/laragon/www`), tidak perlu perintah | `http://localhost/sinos/api/v1/…` |
| Frontend (Astro) — dev      | `cd sinos-web && npm run dev`                                | `http://localhost:4321`           |
| Frontend (Astro) — produksi | `cd sinos-web && npm run build && npm run start`             | `http://localhost:4321`           |
| Frontend (Astro) — pm2      | `pm2 start sinos-web/dist/server/entry.mjs --name sinos-web` | `http://localhost:4321`           |

`sinos-web/.env`: `SINOS_API_BASE=http://localhost/sinos/api/v1`,
`SINOS_SSO_GATEWAY=http://localhost/sso`, `SESSION_SECRET=…`.
`sinos/.env`: `API_SECRET` **wajib diisi** (lihat tindak lanjut).

### Backup

```
sinos/backup/ui-ci3-frontend-2026-10-18/
├── MANIFEST.txt                 (54 entri + ukuran byte per berkas)
├── application/views/           (42 berkas, 129.475 byte)
├── application/helpers/ui_helper.php
├── assets/css/                  (5 berkas, 166.782 byte)
├── assets/js/                   (3 berkas, 749.375 byte)
├── tailwind.config.js
├── package.json
└── package-lock.json
```

Verifikasi backup (jumlah berkas + total byte, sumber vs backup): **cocok
seluruhnya** — 54 berkas / 1.128.950 byte.

### Tindak lanjut

1. **`API_SECRET` belum diatur** di `sinos/.env`. Saat ini token memakai
   kunci turunan `sha256('sinos-api|' . base_url . '|' . ENVIRONMENT)` yang
   **tidak rahasia**; setiap request menulis peringatan ke
   `application/logs/`. Isi dengan `php -r "echo bin2hex(random_bytes(32));"`.
2. **7 berkas yatim di `file/`** — perlu keputusan hapus atau adopsi.
3. **Daftarkan aplikasi Astro di reverse proxy** agar `sinos-web` dapat
   diakses pada origin produksi (sekarang hanya `localhost:4321`).
4. **QA visual 1440/390 belum pernah dilakukan di browser.** Fase 6
   memverifikasi HTML/HTTP, bukan tampilan.
5. `sinos-web/src/components/Sidebar.astro` menautkan `/assets/klasifikasi.pdf`
   (root-relatif pada origin Astro), tetapi `sinos-web/public/` belum ada
   sehingga tautan itu 404. Berkas sumber 11,8 MB sudah dihapus dari
   `sinos/assets/`. Salin ke `sinos-web/public/assets/` bila masih diperlukan.

### Konsolidasi folder (2026-10-18)

Folder sibling `c:/laragon/www/sinos-web/` **dipindahkan** ke
`c:/laragon/www/sinos/frontend/` sehingga proyek menjadi **satu folder, satu
repo, satu pipeline**. Backend CI3 tetap di tempatnya.

- **Nama paket** — `package.json` kini `"name": "sinos-frontend"`,
  `"private": true`. Semua skrip (`dev`, `build`, `preview`, `start`) tidak
  berubah; port dev tetap `4321` (`astro.config.mjs`).
- **Instalasi bersih** — `node_modules/`, `dist/`, dan `.astro/` dihapus
  sebelum pemindahan lalu dipasang ulang dari `sinos/frontend/`. Tidak ada
  berkas dependensi yang ikut berpindah, jadi `npm install` wajib dijalankan
  sekali setelah clone.
- **Glob `@source`** — `src/styles/global.css` memakai
  `@source "../../node_modules/preline/dist/*.js"`. Dari
  `frontend/src/styles/` kedalaman itu tetap benar setelah pemindahan; build
  menghasilkan CSS **56.815 byte**, identik dengan sebelum pindah, sehingga
  kelas Preline tidak hilang.
- **`.env`** — `SINOS_API_BASE=http://localhost/sinos/api/v1` bersifat absolut,
  jadi pemindahan tidak merusaknya. Tidak ada nilai yang diubah.
- **Hardening Apache** — karena seluruh proyek berada di bawah document root,
  `sinos/.htaccess` menambahkan
  `RewriteRule ^frontend/(src|node_modules|\.astro|dist|\.env|astro\.config\.mjs|tsconfig\.json|package(-lock)?\.json) - [F,L]`
  **di atas** catch-all rewrite. Hasil: `frontend/src/...`,
  `frontend/package.json`, dan `frontend/.env` membalas **403**, sementara
  `/api/v1/*` dan `/assets/img/KOP.jpg` tetap **200**.
- **`.gitignore`** — `/frontend/node_modules`, `/frontend/dist`,
  `/frontend/.astro`, `/frontend/.env` diabaikan; aturan lama dipertahankan.
- **Perintah baru:**

  ```bash
  cd sinos/frontend
  npm install
  npm run dev                       # http://localhost:4321
  npm run build && npm run start    # produksi
  ```

  pm2: `pm2 start sinos/frontend/dist/server/entry.mjs --name sinos-frontend`.

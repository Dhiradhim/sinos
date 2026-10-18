<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| URI ROUTING
|--------------------------------------------------------------------------
| FASE 7 (2026-10-18): SELURUH RUTE HTML SUDAH DIHAPUS.
|
| Aplikasi ini kini hanya melayani:
|   1. JSON API untuk frontend Astro  (api/v1/*)
|   2. Rute klien SSO milik CI3       (sso/login, sso/callback)
|
| Catatan penting mengenai SSO:
|   Frontend Astro TIDAK memakai `Sso` controller di sini. Lihat
|   sinos-web/src/pages/api/auth/login.ts — Astro membangun sendiri URL
|   /authorize gateway lalu callback ke sinos-web/api/auth/callback, yang
|   menukar tiket lewat POST api/v1/auth/exchange.
|
|   Rute `sso/login` & `sso/callback` DIPERTAHANKAN karena `sso_callback`
|   pada application/config/sso.php masih menunjuk ke
|   http://localhost/sinos/sso/callback dan URL tersebut terdaftar sebagai
|   redirect_uri di gateway. Menghapusnya akan memutus alur SSO berbasis CI3
|   (dan membuat pendaftaran redirect_uri di gateway menunjuk ke 404).
|
| Rute HTML lama (login, logout, dashboard, surat-masuk/*, disposisi/*,
| surat-keluar/*, laporan/*) sudah tidak ada. Permintaan ke URL tersebut
| sekarang ditangkap `404_override` (controller Notfound) yang membalas
| JSON 404 — bukan halaman error PHP dengan stack trace.
|
| PENTING: `404_override` diurai CI3 sebagai pasangan class/method
| (sscanf '%[^/]/%s') dan dicari di AKAR `controllers/`. Karena itu
| controller penangan 404 ada di `controllers/Notfound.php`, bukan di
| `controllers/api/`. Nilai 'api/notfound' akan salah dibaca sebagai
| class `Api` method `notfound`.
*/

$route['default_controller'] = 'notfound';
$route['404_override'] = 'notfound/index';
$route['translate_uri_dashes'] = FALSE;

// ---------------------------------------------------------------------------
// SSO client (CI3) — dipertahankan, lihat catatan di atas.
// ---------------------------------------------------------------------------
$route['sso/login']    = 'sso/login';
$route['sso/callback'] = 'sso/callback';

// ---------------------------------------------------------------------------
// API v1 (headless frontend / Astro)
// ---------------------------------------------------------------------------

// Auth
$route['api/v1/auth/exchange'] = 'api/auth/exchange';
$route['api/v1/auth/me']       = 'api/auth/me';
$route['api/v1/auth/logout']   = 'api/auth/logout';

// Fase 1 (baca saja)
// PENTING: rute statis harus berada SEBELUM rute ber-parameter numerik,
// karena CI3 memakai aturan pertama yang cocok (surat-keluar/semua akan
// tertangkap oleh (:num) bila urutannya dibalik).
$route['api/v1/master/(:any)']       = 'api/master/$1';
$route['api/v1/dashboard']           = 'api/dashboard/index';
$route['api/v1/surat-masuk']         = 'api/surat_masuk/index';
$route['api/v1/surat-masuk/(:num)']  = 'api/surat_masuk/show/$1';
$route['api/v1/surat-keluar']        = 'api/surat_keluar/index';
$route['api/v1/surat-keluar/semua']  = 'api/surat_keluar/semua';
$route['api/v1/surat-keluar/(:num)'] = 'api/surat_keluar/show/$1';
$route['api/v1/disposisi']           = 'api/disposisi/index';
$route['api/v1/disposisi/(:num)']    = 'api/disposisi/show/$1';
$route['api/v1/laporan/opsi']        = 'api/laporan/opsi';

// Fase 2 (tulis + berkas)
// Rute statis dan rute dengan segmen tambahan diletakkan SEBELUM rute
// numerik. CI3 mencocokkan pola secara utuh (^…$), jadi `(:num)` tidak akan
// menelan `/arsip`, tetapi urutan ini menjaga niat tetap terbaca.
$route['api/v1/surat-masuk/(:num)/arsip']       = 'api/surat_masuk/arsip/$1';
$route['api/v1/surat-masuk/(:num)/batal-arsip'] = 'api/surat_masuk/batal_arsip/$1';
$route['api/v1/surat-masuk/(:num)/disposisi']   = 'api/surat_masuk/disposisi/$1';
$route['api/v1/surat-keluar/ambil']             = 'api/surat_keluar/ambil';
$route['api/v1/surat-keluar/sisip']             = 'api/surat_keluar/sisip';
$route['api/v1/surat-keluar/(:num)/berkas']     = 'api/surat_keluar/berkas/$1';
$route['api/v1/disposisi/form/(:num)']          = 'api/disposisi/form/$1';
$route['api/v1/disposisi/(:num)/arsip']         = 'api/disposisi/arsip/$1';
$route['api/v1/berkas/(:any)']                  = 'api/berkas/show/$1';
$route['api/v1/laporan/cetak']                  = 'api/laporan/cetak';

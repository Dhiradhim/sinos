<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| URI ROUTING
|--------------------------------------------------------------------------
*/

$route['default_controller'] = 'dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Auth
$route['login'] = 'auth/login';
$route['logout'] = 'auth/logout';
// Ganti password tidak tersedia di SINOS; akan dipusatkan di SSO Gateway.

// SSO (klien)
$route['sso/login'] = 'sso/login';
$route['sso/callback'] = 'sso/callback';

// Surat Masuk
$route['surat-masuk'] = 'surat_masuk/index';
$route['surat-masuk/daftar'] = 'surat_masuk/daftar';
$route['surat-masuk/simpan'] = 'surat_masuk/simpan';
$route['surat-masuk/edit/(:num)'] = 'surat_masuk/edit/$1';
$route['surat-masuk/update/(:num)'] = 'surat_masuk/update/$1';
$route['surat-masuk/hapus/(:num)'] = 'surat_masuk/hapus/$1';
$route['surat-masuk/detail/(:num)'] = 'surat_masuk/detail/$1';
$route['surat-masuk/disposisi/(:num)'] = 'surat_masuk/disposisi/$1';
$route['surat-masuk/arsipkan/(:num)'] = 'surat_masuk/arsipkan/$1';
$route['surat-masuk/batal-arsip/(:num)'] = 'surat_masuk/batal_arsip/$1';

// Disposisi
$route['disposisi'] = 'disposisi/index';
$route['disposisi/form/(:num)'] = 'disposisi/form/$1';
$route['disposisi/form-ajax/(:num)'] = 'disposisi/form_ajax/$1';
$route['disposisi/kirim'] = 'disposisi/kirim';
$route['disposisi/detail/(:num)'] = 'disposisi/detail/$1';
$route['disposisi/arsipkan/(:num)'] = 'disposisi/arsipkan/$1';
$route['disposisi/hapus/(:num)'] = 'disposisi/hapus/$1';

// Surat Keluar
$route['surat-keluar/ambil'] = 'surat_keluar/ambil';
$route['surat-keluar/ambil-simpan'] = 'surat_keluar/ambil_simpan';
$route['surat-keluar/sisip'] = 'surat_keluar/sisip';
$route['surat-keluar/sisip-simpan'] = 'surat_keluar/sisip_simpan';
$route['surat-keluar/daftar'] = 'surat_keluar/daftar';
$route['surat-keluar/daftar-semua'] = 'surat_keluar/daftar_semua';
$route['surat-keluar/edit/(:num)'] = 'surat_keluar/edit/$1';
$route['surat-keluar/update/(:num)'] = 'surat_keluar/update/$1';
$route['surat-keluar/upload/(:num)'] = 'surat_keluar/upload/$1';
$route['surat-keluar/upload-simpan'] = 'surat_keluar/upload_simpan';

// Catatan: rute `/user` (kelola pegawai) sudah DIHAPUS.
// Pengelolaan pegawai hanya tersedia di aplikasi `pegawai/` (pemilik
// schema pegawai_db), sehingga tidak ada jalur masuk dari SINOS.

// Laporan
$route['laporan'] = 'laporan/index';
$route['laporan/cetak'] = 'laporan/cetak';

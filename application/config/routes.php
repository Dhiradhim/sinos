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
$route['ganti-password'] = 'auth/changepassword';

// Surat Masuk
$route['surat-masuk'] = 'surat_masuk/index';
$route['surat-masuk/daftar'] = 'surat_masuk/daftar';
$route['surat-masuk/simpan'] = 'surat_masuk/simpan';
$route['surat-masuk/edit/(:num)'] = 'surat_masuk/edit/$1';
$route['surat-masuk/update/(:num)'] = 'surat_masuk/update/$1';
$route['surat-masuk/hapus/(:num)'] = 'surat_masuk/hapus/$1';
$route['surat-masuk/disposisi/(:num)'] = 'surat_masuk/disposisi/$1';

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

// User (admin)
$route['user'] = 'user/index';
$route['user/tambah'] = 'user/tambah';
$route['user/simpan'] = 'user/simpan';
$route['user/edit/(:num)'] = 'user/edit/$1';
$route['user/update/(:num)'] = 'user/update/$1';
$route['user/hapus/(:num)'] = 'user/hapus/$1';

// Laporan
$route['laporan'] = 'laporan/index';
$route['laporan/cetak'] = 'laporan/cetak';

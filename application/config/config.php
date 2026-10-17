<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Base Site URL
|--------------------------------------------------------------------------
*/
$config['base_url'] = 'http://localhost/sinos/';

/*
|--------------------------------------------------------------------------
| Index File
|--------------------------------------------------------------------------
*/
$config['index_page'] = '';

/*
|--------------------------------------------------------------------------
| URI PROTOCOL
|--------------------------------------------------------------------------
*/
$config['uri_protocol']    = 'REQUEST_URI';

/*
|--------------------------------------------------------------------------
| URL suffix
|--------------------------------------------------------------------------
*/
$config['url_suffix'] = '';

/*
|--------------------------------------------------------------------------
| Default Language
|--------------------------------------------------------------------------
*/
$config['language']    = 'english';

/*
|--------------------------------------------------------------------------
| Default Character Set
|--------------------------------------------------------------------------
*/
$config['charset'] = 'UTF-8';

/*
|--------------------------------------------------------------------------
| Enable/Disable System Hooks
|--------------------------------------------------------------------------
*/
$config['enable_hooks'] = FALSE;

/*
|--------------------------------------------------------------------------
| Class Extension Prefix
|--------------------------------------------------------------------------
*/
$config['subclass_prefix'] = 'MY_';

/*
|--------------------------------------------------------------------------
| Composer auto-loading
|--------------------------------------------------------------------------
*/
$config['composer_autoload'] = FCPATH . 'vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Allowed URL Characters
|--------------------------------------------------------------------------
*/
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';

/*
|--------------------------------------------------------------------------
| Enable Query Strings
|--------------------------------------------------------------------------
*/
$config['allow_get_array'] = TRUE;
$config['enable_query_strings'] = FALSE;
$config['controller_trigger'] = 'c';
$config['function_trigger'] = 'm';
$config['directory_trigger'] = 'd';

/*
|--------------------------------------------------------------------------
| Error Logging Threshold
|--------------------------------------------------------------------------
*/
$config['log_threshold'] = 1;

/*
|--------------------------------------------------------------------------
| Error Logging Directory Path
|--------------------------------------------------------------------------
*/
$config['log_path'] = '';

/*
|--------------------------------------------------------------------------
| Error Logging Date Format
|--------------------------------------------------------------------------
*/
$config['log_date_format'] = 'Y-m-d H:i:s';

/*
|--------------------------------------------------------------------------
| Cache Directory Path
|--------------------------------------------------------------------------
*/
$config['cache_path'] = '';

/*
|--------------------------------------------------------------------------
| Cache Query String
|--------------------------------------------------------------------------
*/
$config['cache_query_string'] = FALSE;

/*
|--------------------------------------------------------------------------
| Encryption Key
|--------------------------------------------------------------------------
| IMPORTANT: Change this key to a random string of your choice!
*/
$config['encryption_key'] = 'SINOS_ci3_1f2e3d4c5b6a7980_change_me_2026';

/*
|--------------------------------------------------------------------------
| Session Variables
|--------------------------------------------------------------------------
*/
$config['sess_driver'] = 'files';
$config['sess_cookie_name'] = 'sinos_session';
$config['sess_expiration'] = 7200;
// Folder penyimpanan session. Dibuat otomatis bila belum ada.
$config['sess_save_path'] = FCPATH . 'application/cache/sessions';
$config['sess_match_ip'] = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = FALSE;

/*
|--------------------------------------------------------------------------
| Cookie Related Variables
|--------------------------------------------------------------------------
*/
$config['cookie_prefix']    = '';
$config['cookie_domain']    = '';
$config['cookie_path']        = '/';
$config['cookie_secure']    = FALSE;
$config['cookie_httponly']     = FALSE;

/*
|--------------------------------------------------------------------------
| Standardize newlines
|--------------------------------------------------------------------------
*/
$config['standardize_newlines'] = FALSE;

/*
|--------------------------------------------------------------------------
| Global XSS Filtering
|--------------------------------------------------------------------------
*/
$config['global_xss_filtering'] = FALSE;

/*
|--------------------------------------------------------------------------
| Cross Site Request Forgery
|--------------------------------------------------------------------------
| Enable CSRF protection, but the pages that receive file uploads require
| the token to be sent. Tokens are added in all forms.
*/
$config['csrf_protection'] = FALSE;
$config['csrf_token_name'] = 'csrf_test_name';
$config['csrf_cookie_name'] = 'csrf_cookie_name';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = TRUE;
$config['csrf_exclude_uris'] = array();

/*
|--------------------------------------------------------------------------
| Output Compression
|--------------------------------------------------------------------------
*/
$config['compress_output'] = FALSE;

/*
|--------------------------------------------------------------------------
| Master Time Reference
|--------------------------------------------------------------------------
*/
$config['time_reference'] = 'local';

/*
|--------------------------------------------------------------------------
| Rewrite PHP Short Tags
|--------------------------------------------------------------------------
*/
$config['rewrite_short_tags'] = FALSE;

/*
|--------------------------------------------------------------------------
| Reverse Proxy IPs
|--------------------------------------------------------------------------
*/
$config['proxy_ips'] = '';

/*
|--------------------------------------------------------------------------
| Aplikasi Pegawai (master data pegawai terpusat)
|--------------------------------------------------------------------------
| URL aplikasi `pegawai/` yang menjadi pemilik data pegawai. Dipakai untuk
| menautkan admin dari SINOS ke pengelolaan pegawai.
| Lihat plans/sistem-pegawai.md.
*/
$config['pegawai_app_url'] = 'http://localhost/pegawai/';

/*
|--------------------------------------------------------------------------
| SURAT KELUAR - Kode Penetapan
|--------------------------------------------------------------------------
| Pilihan jenis naskah dinas pada form ambil/sisip nomor surat.
| Kunci dipakai sebagai nilai `kode_penetapan` pada tabel `nosur`;
| label tampil di dropdown. Urutan array = urutan tampil.
|
| `-` dipakai untuk surat biasa yang bukan penetapan.
*/
$config['kode_penetapan'] = array(
    '-'    => 'Surat Lainnya',
    'SK'   => 'Surat Keputusan',
    'SP'   => 'Surat Perintah',
    'ST'   => 'Surat Tugas',
    'SKET' => 'Surat Keterangan',
    'PENG' => 'Pengumuman',
    'UND'  => 'Undangan',
);

/*
|--------------------------------------------------------------------------
| SURAT KELUAR - Penandatangan Surat
|--------------------------------------------------------------------------
| Daftar jabatan yang berhak menandatangani surat. Urutan array menentukan
| urutan grup pada dropdown. Nilai adalah `ref_jabatan.id` di schema
| `pegawai_db`. Semua pegawai aktif yang menduduki jabatan tersebut akan
| muncul sebagai pilihan, dengan label:
|
|   "<nama> [<kode jabatan>]"     mis. "Dahron, S.Ag., M.S.I. [KPA]"
|
| Jabatan `Administrator` (id 1) sengaja TIDAK dimasukkan karena bukan
| pejabat penanda tangan.
*/
$config['penandatangan_jabatan'] = array(
    'Ketua'                     => array(2),
    'Wakil Ketua'               => array(3),
    'Panitera'                  => array(5),
    'Sekretaris'                => array(9),
    'Para Panitera Muda'        => array(6, 7, 8),
    'Para Kepala Sub Bagian'    => array(10, 11, 12),
);

/*
|--------------------------------------------------------------------------
| SURAT KELUAR - Batas jumlah nomor
|--------------------------------------------------------------------------
| Jumlah nomor yang boleh diambil dalam satu transaksi (default 1).
*/
$config['surat_keluar_max_jumlah'] = 20;

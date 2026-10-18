<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| KONFIGURASI SSO (SINOS sebagai klien)
|--------------------------------------------------------------------------
| Nilai dapat di-override melalui file .env (lihat .env.example).
|
| $config['sso_enabled']    : aktifkan integrasi SSO.
| $config['sso_force']      : TRUE = hanya SSO (login lokal dimatikan).
| $config['sso_gateway']    : base URL gateway, harus diakhiri "/".
| $config['sso_app_id']     : app_id yang terdaftar di tabel sso_client.
| $config['sso_app_secret'] : rahasia bersama (harus sama dengan gateway).
| $config['sso_callback']   : URL callback di sisi SINOS (harus terdaftar).
| $config['sso_timeout']    : timeout cURL (detik).
| $config['web_url']        : base URL frontend Astro (sinos-web), TANPA "/"
|                             di akhir. Dipakai Fase 7 sebagai tujuan redirect
|                             pengganti rute HTML yang sudah dihapus
|                             (dulu `login` dan `dashboard`).
*/

// Muat .env bila ada (dipakai juga oleh database.php).
$sso_env = array();
$sso_env_file = FCPATH . '.env';
if (file_exists($sso_env_file)) {
    $sso_env = parse_ini_file($sso_env_file, false, INI_SCANNER_RAW);
}

$cfg_sso = function ($key, $default) use ($sso_env) {
    return isset($sso_env[$key]) && $sso_env[$key] !== '' ? $sso_env[$key] : $default;
};

$config['sso_enabled']    = (bool) $cfg_sso('SSO_ENABLED', TRUE);
$config['sso_force']      = (bool) $cfg_sso('SSO_FORCE', FALSE);
$config['sso_gateway']    = rtrim($cfg_sso('SSO_GATEWAY', 'http://localhost/sso/'), '/') . '/';
$config['sso_app_id']     = $cfg_sso('SSO_APP_ID', 'sinos');
$config['sso_app_secret'] = $cfg_sso('SSO_APP_SECRET', 'GANTI_SECRET_ACAK_PANJANG_SINOS');
$config['sso_callback']   = $cfg_sso('SSO_CALLBACK', 'http://localhost/sinos/sso/callback');
$config['sso_timeout']    = (int) $cfg_sso('SSO_TIMEOUT', 5);

// Fase 7: tujuan redirect pengganti rute HTML `login` dan `dashboard` yang
// sudah dihapus. Menunjuk ke frontend Astro.
$config['web_url']        = rtrim($cfg_sso('SINOS_WEB_URL', 'http://localhost:4321'), '/');

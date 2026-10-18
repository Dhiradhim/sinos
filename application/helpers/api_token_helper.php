<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API Token Helper
 *
 * Token stateless bertanda tangan HMAC-SHA256 untuk API headless (`/api/v1/`).
 *
 * Format:
 *   base64url(payload_json) . '.' . base64url(hmac_sha256(payload_b64, secret))
 *
 * Payload token sesi:
 *   sub, name, roles[], admin, operator, grp, iat, exp
 *
 * Payload token berkas:
 *   typ = "berkas", p (path relatif), iat, exp
 *
 * Tidak menambah dependensi Composer: cukup hash_hmac() bawaan PHP.
 * Secret diambil dari config `api_secret` (dapat dioverride env API_SECRET,
 * lihat application/config/config.php).
 */

if (! function_exists('api_token_secret')) {
    /**
     * Ambil secret HMAC untuk token API.
     *
     * Urutan: config `api_secret` -> fallback turunan base_url + ENVIRONMENT.
     * Fallback HANYA untuk masa pengembangan: nilai turunan tidak rahasia,
     * sehingga siapa pun yang tahu base_url dapat memalsukan token. Peringatan
     * dicatat sekali per request agar operator segera menggantinya.
     *
     * @return string
     */
    function api_token_secret()
    {
        static $secret = NULL;

        if ($secret !== NULL) {
            return $secret;
        }

        $cfg = (string) config_item('api_secret');

        // Placeholder bawaan ("GANTI_...") dianggap belum diatur.
        if ($cfg !== '' && strpos($cfg, 'GANTI_') !== 0) {
            return $secret = $cfg;
        }

        $env  = defined('ENVIRONMENT') ? ENVIRONMENT : 'production';
        $base = (string) config_item('base_url');

        $secret = hash('sha256', 'sinos-api|' . $base . '|' . $env);

        log_message('error', 'API_SECRET belum diatur (config api_secret / env API_SECRET). '
            . 'Token API memakai kunci turunan base_url yang TIDAK rahasia. '
            . 'Set API_SECRET ke nilai acak panjang sebelum dipakai di produksi.');

        return $secret;
    }
}

if (! function_exists('_api_token_b64url_encode')) {
    /**
     * base64url tanpa padding.
     *
     * @param  string $raw
     * @return string
     */
    function _api_token_b64url_encode($raw)
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}

if (! function_exists('_api_token_b64url_decode')) {
    /**
     * Decode base64url (padding opsional). NULL bila bukan base64 valid.
     *
     * @param  string $b64
     * @return string|null
     */
    function _api_token_b64url_decode($b64)
    {
        if (! is_string($b64) || $b64 === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $b64)) {
            return NULL;
        }

        $raw = base64_decode(strtr($b64, '-_', '+/'), TRUE);

        return ($raw === FALSE) ? NULL : $raw;
    }
}

if (! function_exists('_api_token_sign')) {
    /**
     * Tanda tangan HMAC-SHA256 atas payload yang sudah di-base64url.
     *
     * @param  string $payload_b64
     * @return string base64url
     */
    function _api_token_sign($payload_b64)
    {
        return _api_token_b64url_encode(hash_hmac('sha256', $payload_b64, api_token_secret(), TRUE));
    }
}

if (! function_exists('_api_token_decode_verified')) {
    /**
     * Verifikasi tanda tangan + masa berlaku, lalu kembalikan payload.
     *
     * @param  string $token
     * @return array|null NULL bila format salah, tanda tangan tidak cocok,
     *                    atau token kedaluwarsa.
     */
    function _api_token_decode_verified($token)
    {
        if (! is_string($token) || $token === '' || substr_count($token, '.') !== 1) {
            return NULL;
        }

        list($payload_b64, $signature) = explode('.', $token, 2);

        if ($payload_b64 === '' || $signature === '') {
            return NULL;
        }

        // Perbandingan waktu-tetap untuk mencegah timing attack.
        if (! hash_equals(_api_token_sign($payload_b64), $signature)) {
            return NULL;
        }

        $json = _api_token_b64url_decode($payload_b64);
        if ($json === NULL) {
            return NULL;
        }

        $payload = json_decode($json, TRUE);
        if (! is_array($payload)) {
            return NULL;
        }

        if (! isset($payload['exp']) || ! is_numeric($payload['exp'])) {
            return NULL;
        }

        // exp < time() -> kedaluwarsa. exp == time() masih diterima.
        if ((int) $payload['exp'] < time()) {
            return NULL;
        }

        return $payload;
    }
}

if (! function_exists('api_token_issue')) {
    /**
     * Terbitkan token sesi API.
     *
     * @param  array $claims  sub, name, roles[], admin, operator, grp
     * @param  int   $ttl     Masa berlaku (detik). Default 8 jam.
     * @return string
     */
    function api_token_issue(array $claims, $ttl = 28800)
    {
        $now = time();

        $payload = array(
            'sub'      => isset($claims['sub']) ? (string) $claims['sub'] : '',
            'name'     => isset($claims['name']) ? (string) $claims['name'] : '',
            'roles'    => isset($claims['roles']) ? array_values((array) $claims['roles']) : array(),
            'admin'    => ! empty($claims['admin']),
            'operator' => ! empty($claims['operator']),
            'grp'      => isset($claims['grp']) ? (string) $claims['grp'] : '',
            'iat'      => $now,
            'exp'      => $now + (int) $ttl,
        );

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === FALSE) {
            // Praktis tidak terjadi; jaga-jaga bila payload memuat byte non-UTF8.
            $json = json_encode(array('sub' => $payload['sub'], 'exp' => $payload['exp']));
        }

        $payload_b64 = _api_token_b64url_encode($json);

        return $payload_b64 . '.' . _api_token_sign($payload_b64);
    }
}

if (! function_exists('api_token_verify')) {
    /**
     * Verifikasi token sesi API.
     *
     * @param  string $token
     * @return array|null Payload (sub/name/roles/admin/operator/grp/iat/exp)
     *                    atau NULL bila tidak valid / kedaluwarsa / diubah.
     */
    function api_token_verify($token)
    {
        $payload = _api_token_decode_verified($token);
        if ($payload === NULL) {
            return NULL;
        }

        // Token sesi wajib punya subjek (NIP) yang tidak kosong.
        if (! isset($payload['sub']) || ! is_string($payload['sub']) || $payload['sub'] === '') {
            return NULL;
        }

        // Normalkan tipe data agar pemakai tidak perlu menebak.
        $payload['roles']    = isset($payload['roles']) ? array_values((array) $payload['roles']) : array();
        $payload['admin']    = ! empty($payload['admin']);
        $payload['operator'] = ! empty($payload['operator']);
        $payload['grp']      = isset($payload['grp']) ? (string) $payload['grp'] : '';
        $payload['name']     = isset($payload['name']) ? (string) $payload['name'] : '';
        $payload['iat']      = isset($payload['iat']) ? (int) $payload['iat'] : 0;
        $payload['exp']      = (int) $payload['exp'];

        return $payload;
    }
}

if (! function_exists('api_token_bearer')) {
    /**
     * Baca token dari header `Authorization: Bearer <token>`.
     *
     * Apache/CGI kadang menyembunyikan header Authorization dari $_SERVER,
     * sehingga apache_request_headers()/getallheaders() ikut diperiksa.
     *
     * @return string|null
     */
    function api_token_bearer()
    {
        $header = NULL;

        foreach (array('HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION') as $key) {
            if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
                $header = $_SERVER[$key];
                break;
            }
        }

        if ($header === NULL) {
            $headers = NULL;
            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
            }
            if ((! is_array($headers) || empty($headers)) && function_exists('getallheaders')) {
                $headers = getallheaders();
            }

            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strcasecmp((string) $name, 'Authorization') === 0) {
                        $header = $value;
                        break;
                    }
                }
            }
        }

        if (! is_string($header) || $header === '') {
            return NULL;
        }

        if (! preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $m)) {
            return NULL;
        }

        return $m[1];
    }
}

if (! function_exists('_api_berkas_normalize')) {
    /**
     * Normalisasi path relatif berkas. NULL bila tidak aman.
     *
     * Menolak path absolut, backslash, segmen ".." dan path kosong agar token
     * tidak dapat dipakai untuk membaca berkas di luar direktori `file/`.
     *
     * @param  string $relative_path
     * @return string|null
     */
    function _api_berkas_normalize($relative_path)
    {
        if (! is_string($relative_path)) {
            return NULL;
        }

        $path = ltrim(trim($relative_path), '/');

        if ($path === '' || strpos($path, '\\') !== FALSE || strpos($path, "\0") !== FALSE) {
            return NULL;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..' || $segment === '.') {
                return NULL;
            }
        }

        // Path absolut (mis. C:/...) atau berskema (file://) tidak diizinkan.
        if (preg_match('#^[A-Za-z]:#', $path) || strpos($path, '://') !== FALSE) {
            return NULL;
        }

        return $path;
    }
}

if (! function_exists('api_berkas_sign')) {
    /**
     * Terbitkan token akses berkas untuk path relatif tertentu.
     *
     * Dipakai Fase 2 (`GET /api/v1/berkas/{token}`) agar URL berkas tidak dapat
     * ditebak dan tetap terikat masa berlaku.
     *
     * @param  string $relative_path Path relatif terhadap FCPATH, mis. "file/sm/2026_x.pdf"
     * @param  int    $ttl           Masa berlaku (detik). Default 1 jam.
     * @return string|null NULL bila path tidak aman.
     */
    function api_berkas_sign($relative_path, $ttl = 3600)
    {
        $path = _api_berkas_normalize($relative_path);
        if ($path === NULL) {
            return NULL;
        }

        $now = time();

        $json = json_encode(array(
            'typ' => 'berkas',
            'p'   => $path,
            'iat' => $now,
            'exp' => $now + (int) $ttl,
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $payload_b64 = _api_token_b64url_encode($json);

        return $payload_b64 . '.' . _api_token_sign($payload_b64);
    }
}

if (! function_exists('api_berkas_verify')) {
    /**
     * Verifikasi token berkas.
     *
     * @param  string $token
     * @return string|null Path relatif bila token valid, selain itu NULL.
     */
    function api_berkas_verify($token)
    {
        $payload = _api_token_decode_verified($token);
        if ($payload === NULL) {
            return NULL;
        }

        if (! isset($payload['typ']) || $payload['typ'] !== 'berkas') {
            return NULL;
        }

        if (! isset($payload['p']) || ! is_string($payload['p'])) {
            return NULL;
        }

        return _api_berkas_normalize($payload['p']);
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sso_client - klien SSO untuk SINOS.
 *
 * Membangun URL authorize dan memanggil endpoint gateway
 * (validate/userinfo/introspect/logout) dengan signature HMAC-SHA256.
 * Memakai cURL native agar ringan.
 *
 * CATATAN: Kelas ini dinamai Sso_client (bukan Sso) untuk menghindari
 * bentrok nama kelas dengan controller `Sso` di application/controllers/Sso.php.
 */
class Sso_client
{
    /** @var CI_Controller */
    protected $CI;

    /** @var string|null Pesan error terakhir dari request gateway. */
    protected $last_error = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->config->load('sso', TRUE);
        // Session sudah di-autoload (lihat application/config/autoload.php),
        // jadi tidak perlu dimuat ulang di sini.
    }

    // Ambil nilai konfigurasi SSO.
    public function config($key, $default = NULL)
    {
        $val = $this->CI->config->item($key, 'sso');
        return ($val === NULL) ? $default : $val;
    }

    // Apakah SSO diaktifkan.
    public function enabled()
    {
        return (bool) $this->config('sso_enabled', FALSE);
    }

    // Bangun URL authorize ke gateway.
    public function authorize_url($state)
    {
        $base = rtrim($this->config('sso_gateway'), '/');
        $query = http_build_query(array(
            'app_id'       => $this->config('sso_app_id'),
            'redirect_uri' => $this->config('sso_callback'),
            'state'        => $state,
        ));
        return $base . '/authorize?' . $query;
    }

    // Validasi ticket ke gateway (server-to-server).
    // Mengembalikan identitas user (array) atau FALSE.
    public function validate($ticket)
    {
        $payload = $this->signed_payload('validate', array('ticket' => $ticket));
        $resp    = $this->post('validate', $payload);

        if (is_array($resp) && ! empty($resp['ok']) && isset($resp['data'])) {
            return $resp['data'];
        }
        return FALSE;
    }

    // Pesan error terakhir (NULL bila tidak ada).
    public function last_error()
    {
        return $this->last_error;
    }

    // Apakah ekstensi cURL tersedia. Dipakai untuk memutus redirect loop
    // ketika SSO tidak dapat dihubungi (mis. cURL nonaktif di php.ini).
    public function curl_available()
    {
        return function_exists('curl_init');
    }

    // Cek apakah sesi gateway masih aktif.
    public function introspect($session_group)
    {
        $payload = $this->signed_payload('group', array('session_group' => $session_group));
        $resp    = $this->post('introspect', $payload);
        return (is_array($resp) && ! empty($resp['ok']) && ! empty($resp['active']));
    }

    // Ambil identitas terkini berdasarkan session_group.
    public function userinfo($session_group)
    {
        $payload = $this->signed_payload('group', array('session_group' => $session_group));
        $resp    = $this->post('userinfo', $payload);
        if (is_array($resp) && ! empty($resp['ok']) && isset($resp['data'])) {
            return $resp['data'];
        }
        return FALSE;
    }

    // Logout: satu app atau global (single logout).
    public function logout($session_group, $global = TRUE)
    {
        $payload = $this->signed_payload('group', array(
            'session_group' => $session_group,
            'global'        => $global ? 1 : 0,
        ));
        $endpoint = $global ? 'logout-all' : 'logout';
        $resp     = $this->post($endpoint, $payload);
        return (is_array($resp) && ! empty($resp['ok']));
    }

    // Bangun payload bertanda tangan.
    // $type 'validate' (membawa ticket) atau 'group'.
    protected function signed_payload($type, $data)
    {
        $app_id = $this->config('sso_app_id');
        $ts     = time();
        $nonce  = bin2hex(random_bytes(16));

        if ($type === 'validate') {
            $ticket = isset($data['ticket']) ? $data['ticket'] : '';
            $base   = $app_id . "\n" . $ticket . "\n" . $ts . "\n" . $nonce;
        } else {
            $group = isset($data['session_group']) ? $data['session_group'] : '';
            $base  = $app_id . "\n" . $group . "\n" . $ts . "\n" . $nonce;
        }

        $signature = hash_hmac('sha256', $base, $this->config('sso_app_secret'));

        $payload = array_merge($data, array(
            'app_id'    => $app_id,
            'ts'        => $ts,
            'nonce'     => $nonce,
            'signature' => $signature,
        ));
        return $payload;
    }

    // Kirim POST JSON ke endpoint gateway. Mengembalikan array atau FALSE.
    protected function post($endpoint, array $payload)
    {
        $url  = rtrim($this->config('sso_gateway'), '/') . '/' . ltrim($endpoint, '/');
        $body = json_encode($payload);

        // Ekstensi cURL wajib ada. Bila belum aktif di php.ini (umumnya
        // perlu aktifkan `extension=curl` lalu restart Apache), beri pesan
        // yang jelas alih-alih fatal error "Call to undefined function curl_init()".
        if (! function_exists('curl_init')) {
            $this->last_error = 'Ekstensi cURL PHP tidak aktif. Aktifkan "extension=curl" di php.ini lalu restart Apache/XAMPP.';
            log_message('error', $this->last_error);
            return FALSE;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'Accept: application/json',
            ),
            CURLOPT_TIMEOUT        => (int) $this->config('sso_timeout', 5),
            CURLOPT_CONNECTTIMEOUT => (int) $this->config('sso_timeout', 5),
        ));

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === FALSE || $raw === '') {
            $this->last_error = 'Gagal menghubungi gateway SSO (' . $endpoint . '): ' . $err;
            log_message('error', $this->last_error);
            return FALSE;
        }

        $decoded = json_decode($raw, TRUE);
        if (! is_array($decoded)) {
            $this->last_error = 'Respons gateway bukan JSON (' . $endpoint . '): ' . $raw;
            log_message('error', $this->last_error);
            return FALSE;
        }

        // Gateway mengembalikan error (mis. invalid_signature/ticket_used).
        if (empty($decoded['ok']) && isset($decoded['error'])) {
            $this->last_error = 'Gateway menolak request: ' . $decoded['error'];
        }

        return $decoded;
    }

    /**
     * Validasi sesi gateway untuk $session_group. TRUE bila masih aktif.
     * Dipakai untuk auto-logout lokal ketika pengguna logout dari gateway.
     * Fail-open (TRUE) bila cURL tidak tersedia / gateway error.
     */
    public function session_alive($session_group)
    {
        if (empty($session_group)) {
            return FALSE;
        }
        if (! $this->curl_available()) {
            return TRUE;
        }
        $resp = $this->post('introspect', $this->signed_payload('group', array(
            'session_group' => $session_group,
        )));
        if (is_array($resp) && ! empty($resp['ok'])) {
            return ! empty($resp['active']);
        }
        return TRUE;
    }

    // Buat & simpan state anti-CSRF di session. Mengembalikan nilai state.
    public function make_state()
    {
        $state = bin2hex(random_bytes(16));
        $this->CI->session->set_userdata('sso_state', $state);
        return $state;
    }

    // Verifikasi state yang dikembalikan gateway.
    public function verify_state($state)
    {
        $expected = $this->CI->session->userdata('sso_state');
        $this->CI->session->unset_userdata('sso_state');
        return (is_string($expected) && $expected !== '' && hash_equals($expected, (string) $state));
    }
}

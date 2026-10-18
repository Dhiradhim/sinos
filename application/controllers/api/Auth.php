<?php
defined('BASEPATH') or exit('No direct script access allowed');

// CI3 hanya memuat otomatis application/core/MY_Controller.php, sehingga base
// controller API harus di-require eksplisit di sini.
require_once APPPATH . 'core/MY_Api_Controller.php';

/**
 * API Auth - endpoint autentikasi untuk frontend headless (Astro BFF).
 *
 *   POST /api/v1/auth/exchange   tukar tiket SSO menjadi token Bearer
 *   GET  /api/v1/auth/me         identitas user dari token
 *   POST /api/v1/auth/logout     single logout ke gateway
 *
 * CATATAN PENTING: controller ini TIDAK memakai session PHP sama sekali.
 * Tidak ada `$this->session` di sini; identitas hanya berasal dari token
 * HMAC stateless. Hal ini disengaja agar browser tidak perlu menyimpan
 * cookie `ci_session`.
 */
class Auth extends MY_Api_Controller
{
    /**
     * `exchange` harus dapat dipanggil TANPA Bearer token.
     *
     * Nilai ini dibaca oleh MY_Api_Controller::__construct(), sehingga harus
     * berupa deklarasi properti (bukan di-set di dalam method).
     *
     * @var bool
     */
    protected $require_auth = FALSE;

    /** Masa berlaku token yang diterbitkan (detik) = 8 jam. */
    const TOKEN_TTL = 28800;

    /**
     * POST /api/v1/auth/exchange
     *
     * Body: { "ticket": "...", "group": "..." (opsional) }
     *
     * Alur (meniru Sso::callback(), tanpa session):
     *   1. pastikan SSO aktif dan cURL tersedia,
     *   2. validasi tiket ke gateway lewat Sso_client::validate(),
     *   3. susun klaim dari identitas (nip, nama, roles, session_group),
     *   4. terbitkan token Bearer.
     */
    public function exchange()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode POST.', 405);
            return;
        }

        $input  = $this->input_json();
        $ticket = isset($input['ticket']) ? trim((string) $input['ticket']) : '';

        if ($ticket === '') {
            $this->fail('VALIDATION_FAILED', 'Tiket SSO wajib diisi.', 422, array(
                'ticket' => 'Tiket SSO wajib diisi.',
            ));
            return;
        }

        $this->load->library('sso_client');

        if (! $this->sso_client->enabled()) {
            $this->fail('SSO_DISABLED', 'Integrasi SSO tidak aktif di server ini.', 503);
            return;
        }

        if (! $this->sso_client->curl_available()) {
            $this->fail(
                'SSO_UNAVAILABLE',
                'SSO tidak dapat digunakan: ekstensi cURL PHP belum aktif.',
                503
            );
            return;
        }

        $identitas = $this->sso_client->validate($ticket);

        if (! is_array($identitas) || empty($identitas['nip'])) {
            $err = $this->sso_client->last_error();
            log_message('error', 'API exchange: validasi tiket SSO gagal. ' . ($err ? $err : 'tanpa detail'));

            $this->fail('SSO_TICKET_INVALID', 'Tiket SSO tidak valid atau sudah kedaluwarsa.', 401);
            return;
        }

        $nip   = (string) $identitas['nip'];
        $nama  = ! empty($identitas['nama']) ? (string) $identitas['nama'] : $nip;
        $roles = isset($identitas['roles']) ? (array) $identitas['roles'] : array();

        // Grup sesi: utamakan nilai dari gateway, fallback ke body request.
        $grp = '';
        if (! empty($identitas['session_group'])) {
            $grp = (string) $identitas['session_group'];
        } elseif (! empty($input['group'])) {
            $grp = (string) $input['group'];
        }

        $is_admin    = ($nip === 'admin') || in_array('admin', $roles, TRUE);
        $is_operator = $is_admin || in_array('operator', $roles, TRUE);

        if (! $is_operator) {
            $this->load->model('M_user');
            $is_operator = (bool) $this->M_user->is_operator($nip);
        }

        $token = api_token_issue(array(
            'sub'      => $nip,
            'name'     => $nama,
            'roles'    => $roles,
            'admin'    => $is_admin,
            'operator' => $is_operator,
            'grp'      => $grp,
        ), self::TOKEN_TTL);

        $this->ok(array(
            'token'      => $token,
            'expires_in' => self::TOKEN_TTL,
            'user'       => array(
                'nip'         => $nip,
                'nama'        => $nama,
                'is_admin'    => $is_admin,
                'is_operator' => $is_operator,
            ),
        ));
    }

    /**
     * GET /api/v1/auth/me
     *
     * Mengembalikan identitas dari klaim token. Status operator diperiksa
     * ulang ke database agar pencabutan hak akses langsung berlaku walau
     * token belum kedaluwarsa.
     */
    public function me()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET.', 405);
            return;
        }

        // Controller ini menurunkan $require_auth, jadi autentikasi dilakukan
        // di sini (fail() sudah menyiapkan respons 401).
        if (! $this->authenticate()) {
            return;
        }

        $nip = (string) $this->user['sub'];

        $this->load->model('M_user');
        $is_operator = (bool) $this->M_user->is_operator($nip);

        // Admin selalu operator, konsisten dengan MY_Controller.
        if (! empty($this->user['admin'])) {
            $is_operator = TRUE;
        }

        $this->ok(array(
            'nip'         => $nip,
            'nama'        => (string) $this->user['name'],
            'is_admin'    => (bool) $this->user['admin'],
            'is_operator' => $is_operator,
            'roles'       => $this->user['roles'],
            'expires_at'  => (int) $this->user['exp'],
        ));
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Single logout global ke gateway (menghapus seluruh token pada grup sesi
     * login ini). Cookie sesi milik Astro dihapus oleh Astro sendiri.
     */
    public function logout()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode POST.', 405);
            return;
        }

        if (! $this->authenticate()) {
            return;
        }

        $grp = isset($this->user['grp']) ? (string) $this->user['grp'] : '';

        if ($grp !== '') {
            $this->config->load('sso', TRUE);
            $sso_enabled = $this->config->item('sso_enabled', 'sso');

            if ($sso_enabled) {
                $this->load->library('sso_client');

                if (! $this->sso_client->logout($grp, TRUE)) {
                    // Token lokal tetap dianggap selesai; kegagalan gateway
                    // dicatat agar dapat ditelusuri tanpa memblokir logout.
                    log_message('error', 'API logout: gateway menolak single logout untuk grup '
                        . $grp . '. ' . (string) $this->sso_client->last_error());
                }
            }

            // Buang cache status sesi agar token ini tidak bisa memanfaatkan
            // sisa jendela cache 60 detik setelah logout.
            $cache = $this->sso_cache();
            if ($cache !== NULL) {
                $cache->delete($this->sso_cache_key($grp));
            }
        }

        $this->ok(array('ok' => TRUE));
    }
}

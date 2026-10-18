<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sso - controller klien SSO di sisi SINOS.
 *
 * Menangani redirect ke gateway (login) dan callback ticket (callback).
 *
 * STATUS Fase 7 (2026-10-18): DIPERTAHANKAN.
 *
 * Alasan:
 *   1. `sso_callback` pada application/config/sso.php masih menunjuk ke
 *      http://localhost/sinos/sso/callback dan URL itu terdaftar sebagai
 *      redirect_uri di gateway SSO.
 *   2. Rute `sso/login` dan `sso/callback` masih ada di
 *      application/config/routes.php.
 *
 * Catatan: frontend Astro TIDAK memakai controller ini. Lihat
 * sinos-web/src/pages/api/auth/login.ts — Astro membangun URL /authorize
 * gateway sendiri lalu callback ke sinos-web/api/auth/callback, yang menukar
 * tiket lewat POST api/v1/auth/exchange. Jadi ada dua jalur SSO yang sah:
 * jalur CI3 (controller ini) dan jalur Astro (api/v1/auth/exchange).
 *
 * PERUBAHAN Fase 7:
 *   - TIDAK ADA pemanggilan `load->view` (controller ini memang tidak pernah
 *     merender view; hanya redirect + session).
 *   - Semua `redirect('login')` dan `redirect('dashboard')` diganti ke
 *     `web_url` (frontend Astro) karena kedua rute HTML itu sudah dihapus.
 */
class Sso extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('sso_client');
        $this->config->load('sso', TRUE);
    }

    /**
     * URL frontend Astro, pengganti rute HTML `login`/`dashboard`.
     *
     * @param  string $path Path relatif, mis. 'login' atau 'dashboard'
     * @return string
     */
    protected function web($path = '')
    {
        $base = rtrim($this->config->item('web_url', 'sso'), '/');
        $path = ltrim($path, '/');

        return $base . ($path === '' ? '' : '/' . $path);
    }

    // Mulai proses SSO: arahkan ke gateway.
    // Query opsional: redirect (URL tujuan setelah login berhasil).
    public function login()
    {
        // Simpan tujuan akhir agar bisa dipakai setelah callback.
        $redirect = $this->input->get('redirect');
        if (! empty($redirect)) {
            $this->session->set_userdata('sso_redirect', $redirect);
        }

        if (! $this->sso_client->enabled()) {
            redirect($this->web('login'));
            return;
        }

        // Bila sudah login lokal, langsung ke tujuan.
        if ($this->session->userdata('nip')) {
            redirect($this->web('dashboard'));
            return;
        }

        // Bila cURL tidak aktif, gateway tidak dapat dipanggil sama sekali.
        // Jangan arahkan ke gateway (akan memicu loop) - jatuh ke login Astro.
        if (! $this->sso_client->curl_available()) {
            $this->session->set_flashdata('sso_failed', 1);
            $this->session->set_flashdata('flash_tipe', 'error');
            $this->session->set_flashdata('flash_pesan', 'SSO tidak dapat digunakan: ekstensi cURL PHP belum aktif.');
            redirect($this->web('login') . '?error=sso');
            return;
        }

        $state = $this->sso_client->make_state();
        redirect($this->sso_client->authorize_url($state));
    }

    // Terima ticket dari gateway, validasi, lalu buat sesi lokal.
    public function callback()
    {
        if (! $this->sso_client->enabled()) {
            redirect($this->web('login'));
            return;
        }

        // Bila ekstensi cURL tidak aktif, SSO tidak mungkin berhasil. Hentikan
        // alur di sini (jangan redirect ke /sso/login) agar tidak terjadi loop.
        if (! $this->sso_client->curl_available()) {
            $this->session->set_flashdata('sso_failed', 1);
            $this->session->set_flashdata('flash_tipe', 'error');
            $this->session->set_flashdata('flash_pesan', 'SSO tidak dapat digunakan: ekstensi cURL PHP belum aktif.');
            redirect($this->web('login') . '?error=sso');
            return;
        }

        $ticket = $this->input->get('ticket');
        $state  = $this->input->get('state');

        // Anti-CSRF: state harus cocok dengan yang disimpan di session.
        if (empty($ticket) || ! $this->sso_client->verify_state($state)) {
            $this->session->set_flashdata('sso_failed', 1);
            $this->session->set_flashdata('flash_tipe', 'error');
            $this->session->set_flashdata('flash_pesan', 'Sesi SSO tidak valid. Silakan coba lagi.');
            redirect($this->web('login') . '?error=sso');
            return;
        }

        $identitas = $this->sso_client->validate($ticket);
        if (! $identitas || empty($identitas['nip'])) {
            $err = $this->sso_client->last_error();
            $this->session->set_flashdata('sso_failed', 1);
            $this->session->set_flashdata('flash_tipe', 'error');
            $this->session->set_flashdata('flash_pesan', 'Gagal memvalidasi tiket SSO.' . ($err ? ' (' . $err . ')' : ''));
            redirect($this->web('login') . '?error=sso');
            return;
        }

        // Simpan identitas ke session lokal SINOS.
        $roles = isset($identitas['roles']) ? (array) $identitas['roles'] : array();

        $this->session->set_userdata(array(
            'nip'               => $identitas['nip'],
            'nama'              => isset($identitas['nama']) ? $identitas['nama'] : $identitas['nip'],
            'sso_roles'         => $roles,
            'sso_session_group' => isset($identitas['session_group']) ? $identitas['session_group'] : NULL,
            'sso_logged_in'     => TRUE,
        ));
        $this->session->sess_regenerate(TRUE);

        // Arahkan ke tujuan semula atau dashboard Astro.
        $redirect = $this->session->userdata('sso_redirect');
        $this->session->unset_userdata('sso_redirect');
        redirect(! empty($redirect) ? $redirect : $this->web('dashboard'));
    }
}

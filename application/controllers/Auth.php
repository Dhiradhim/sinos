<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Auth - login, logout, ganti password.
 */
class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_user');
    }

    /**
     * Halaman login + proses autentikasi.
     *
     * Bila SSO aktif, arahkan ke gateway terlebih dahulu.
     *
     * Login lokal TIDAK lagi memverifikasi password di SINOS: kolom `pass`
     * berada di schema `pegawai_db` dan tidak termasuk grant SELECT untuk
     * SINOS. Verifikasi kredensial adalah tanggung jawab gateway SSO.
     */
    public function login()
    {
        if ($this->session->userdata('nip')) {
            redirect('dashboard');
        }

        $this->config->load('sso', TRUE);
        $sso_enabled = $this->config->item('sso_enabled', 'sso');
        $sso_force   = $this->config->item('sso_force', 'sso');

        // Penanda bahwa baru saja gagal dari alur SSO. Dipakai untuk memutus
        // redirect loop: jangan lempar kembali ke /sso/login, tampilkan form
        // login lokal sebagai fallback.
        $sso_failed = (bool) $this->session->flashdata('sso_failed');

        // Arahkan ke SSO kecuali pengguna memilih login lokal (fallback)
        // atau SSO baru saja gagal.
        if ($sso_enabled && ! $this->input->get('local') && ! $sso_failed) {
            redirect('sso/login');
            return;
        }

        // sso_force: login lokal dimatikan sepenuhnya. Namun bila SSO gagal,
        // tetap tampilkan form lokal agar tidak terjadi loop tak berujung.
        if ($sso_force && ! $this->input->get('local') && ! $sso_failed) {
            redirect('sso/login');
            return;
        }

        $data = array('title' => 'Login');

        if ($this->input->post('login')) {
            $data['error'] = 'Login lokal dinonaktifkan. Silakan masuk melalui SSO.';
        }

        $this->load->view('auth/login', $data);
    }

    /**
     * Logout.
     *
     * Bila SSO aktif, lakukan single logout ke gateway (hapus seluruh token
     * pada session_group) lalu hancurkan sesi lokal.
     */
    public function logout()
    {
        $this->config->load('sso', TRUE);
        $sso_enabled = $this->config->item('sso_enabled', 'sso');
        $group       = $this->session->userdata('sso_session_group');

        if ($sso_enabled && ! empty($group)) {
            $this->load->library('sso_client');
            // Single logout global: hapus semua token grup sesi login ini.
            $this->sso_client->logout($group, TRUE);
        }

        $this->session->sess_destroy();

        if ($sso_enabled) {
            // Minta gateway menghapus sesi login-nya lalu kembali ke login SINOS.
            redirect(rtrim($this->config->item('sso_gateway', 'sso'), '/')
                . '/logout?redirect=' . urlencode(site_url('login')));
            return;
        }

        redirect('login');
    }

    // Catatan: fitur ganti password SENGAJA tidak tersedia di SINOS.
    // Fitur tersebut akan dipusatkan di SSO Gateway. SINOS juga tidak
    // memiliki akses tulis ke kolom `pass` pada schema pegawai_db.
}

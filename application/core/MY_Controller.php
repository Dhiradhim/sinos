<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * MY_Controller
 * Base controller untuk seluruh halaman yang membutuhkan autentikasi.
 * Menyediakan data user, layout Tailwind, dan proteksi session.
 */
#[\AllowDynamicProperties]
class MY_Controller extends CI_Controller
{
    /** @var array Data user yang sedang login */
    protected $user = array();

    public function __construct()
    {
        parent::__construct();

        // Proteksi: wajib login
        if (! $this->session->userdata('nip')) {
            $this->redirect_to_login();
            exit;
        }

        // Pastikan sesi gateway SSO masih aktif (single logout). Bila pengguna
        // sudah logout dari gateway, sesi lokal SINOS juga harus berakhir.
        $this->enforce_sso_session();

        $this->load->model('M_user');
        $nip  = $this->session->userdata('nip');
        $nama = $this->session->userdata('nama');
        if (empty($nama)) {
            $nama = $this->M_user->get_nama($nip);
        }

        // Peran: kombinasi sumber lokal (admin/operator) dan roles dari SSO.
        $sso_roles   = $this->session->userdata('sso_roles');
        $sso_roles   = is_array($sso_roles) ? $sso_roles : array();
        $is_admin    = ($nip === 'admin') || in_array('admin', $sso_roles, TRUE);
        $is_operator = $is_admin || in_array('operator', $sso_roles, TRUE) || $this->M_user->is_operator($nip);

        $this->user = array(
            'nip'         => $nip,
            'nama'        => $nama ? $nama : $nip,
            'is_admin'    => $is_admin,
            'is_operator' => $is_operator,
        );
    }

    /**
     * Validasi sesi SSO gateway. Bila grup sesi sudah tidak aktif (logout dari
     * gateway / single logout), hancurkan sesi lokal lalu arahkan ke login.
     * Login lokal murni (tanpa sso_session_group) dilewati.
     */
    protected function enforce_sso_session()
    {
        $group = $this->session->userdata('sso_session_group');
        if (empty($group)) {
            return;
        }

        $this->config->load('sso', TRUE);
        if (! $this->config->item('sso_enabled', 'sso')) {
            return;
        }

        $this->load->library('sso_client');
        if (! $this->sso_client->session_alive($group)) {
            $this->session->sess_destroy();
            redirect('sso/login?redirect=' . urlencode(current_url()));
            exit;
        }
    }

    /**
     * Arahkan ke SSO bila aktif, jika tidak ke login lokal.
     */
    protected function redirect_to_login()
    {
        $this->config->load('sso', TRUE);
        $sso_enabled = $this->config->item('sso_enabled', 'sso');

        if ($sso_enabled) {
            redirect('sso/login?redirect=' . urlencode(current_url()));
            return;
        }
        redirect('login');
    }

    /**
     * Render view konten dengan layout master Tailwind.
     *
     * @param string $view   Nama view konten (mis. 'surat_keluar/daftar')
     * @param array  $data   Data yang dikirim ke view
     * @param array  $meta   title, subtitle, active (menu aktif)
     */
    protected function render($view, $data = array(), $meta = array())
    {
        $data['user']      = $this->user;
        $data['title']     = isset($meta['title']) ? $meta['title'] : 'SINOS';
        $data['subtitle']  = isset($meta['subtitle']) ? $meta['subtitle'] : '';
        $data['active']    = isset($meta['active']) ? $meta['active'] : $this->uri->segment(1);
        $data['disposisi_count'] = $this->disposisi_count();
        $data['content']   = $this->load->view($view, $data, TRUE);

        $this->load->view('layouts/master', $data);
    }

    /**
     * Jumlah disposisi belum dibaca untuk user yang sedang login.
     */
    protected function disposisi_count()
    {
        if (empty($this->user['nip'])) {
            return 0;
        }
        $this->load->model('M_disposisi');
        return $this->M_disposisi->count_belum_dibaca($this->user['nip']);
    }

    /**
     * Set pesan flash dengan tipe (success|error|warning|info).
     */
    protected function flash($tipe, $pesan)
    {
        $this->session->set_flashdata('flash_tipe', $tipe);
        $this->session->set_flashdata('flash_pesan', $pesan);
    }
}

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
            redirect('login');
            exit;
        }

        $this->load->model('M_user');
        $nama = $this->M_user->get_nama($this->session->userdata('nip'));

        $this->user = array(
            'nip'  => $this->session->userdata('nip'),
            'nama' => $nama ? $nama : $this->session->userdata('nip'),
            'is_admin' => ($this->session->userdata('nip') === 'admin'),
        );
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

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
     */
    public function login()
    {
        if ($this->session->userdata('nip')) {
            redirect('dashboard');
        }

        $data = array('title' => 'Login');

        if ($this->input->post('login')) {
            $nip  = trim($this->input->post('nip'));
            $pass = $this->input->post('pass');

            if ($nip === '' || $pass === '') {
                $data['error'] = 'Username dan password wajib diisi.';
            } else {
                $user = $this->M_user->get_by_nip($nip);
                // Verifikasi password modern (bcrypt). Mendukung juga hash md5 lama (32 char).
                if ($user && $this->verify_password($pass, $user->pass)) {
                    // Upgrade transparan hash md5 lama -> bcrypt
                    if (strlen($user->pass) === 32 && ctype_xdigit($user->pass)) {
                        $this->M_user->update($user->id, array('pass' => password_hash($pass, PASSWORD_BCRYPT)));
                    }

                    $this->session->set_userdata('nip', $user->nip);
                    $this->session->sess_regenerate(TRUE);
                    redirect('dashboard');
                    return;
                }
                $data['error'] = 'Username atau Password salah!';
            }
        }

        $this->load->view('auth/login', $data);
    }

    /**
     * Verifikasi password: bcrypt atau md5 (legacy).
     */
    private function verify_password($plain, $hash)
    {
        if (password_verify($plain, $hash)) {
            return TRUE;
        }
        // Dukungan hash md5 lama (32 karakter heksadesimal)
        if (strlen($hash) === 32 && ctype_xdigit($hash) && md5($plain) === $hash) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Logout.
     */
    public function logout()
    {
        $this->session->sess_destroy();
        redirect('login');
    }

    /**
     * Ganti password user yang sedang login.
     */
    public function changepassword()
    {
        if ( ! $this->session->userdata('nip')) {
            redirect('login');
        }

        $nip  = $this->session->userdata('nip');
        $data = array('user' => array('nip' => $nip), 'title' => 'Ganti Password', 'active' => '');

        if ($this->input->post('simpan')) {
            $old = $this->input->post('old');
            $new = $this->input->post('new');
            $rep = $this->input->post('rep');

            $user = $this->M_user->get_by_nip($nip);

            if ( ! $user || ! $this->verify_password($old, $user->pass)) {
                $data['error'] = 'Password Lama Salah';
            } elseif ($new !== $rep) {
                $data['error'] = 'Password Baru Tidak Cocok';
            } elseif (strlen($new) < 6) {
                $data['error'] = 'Password baru minimal 6 karakter.';
            } else {
                $this->M_user->update($user->id, array('pass' => password_hash($new, PASSWORD_BCRYPT)));
                $this->session->sess_destroy();
                redirect('login');
                return;
            }
        }

        $this->load->view('auth/changepassword', $data);
    }
}

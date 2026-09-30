<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * User - manajemen user (khusus admin).
 */
class User extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (! $this->user['is_admin']) {
            show_error('Halaman ini hanya untuk admin.', 403);
        }
        $this->load->model(array('M_user', 'M_jabatan'));
    }

    public function index()
    {
        $this->render('user/index', array(
            'rows'           => $this->M_user->daftar(),
            'use_datatables' => TRUE,
        ), array(
            'title'    => 'Daftar User',
            'subtitle' => 'Kelola akun pengguna SINOS.',
            'active'   => 'user',
        ));
    }

    public function tambah()
    {
        $this->render('user/form', array(
            'jabatan' => $this->M_jabatan->get_all('id', 'ASC'),
            'row'     => NULL,
        ), array(
            'title'    => 'Tambah User',
            'subtitle' => 'Buat akun pengguna baru.',
            'active'   => 'user',
        ));
    }

    public function simpan()
    {
        $pass1 = $this->input->post('pass1');
        $pass2 = $this->input->post('pass2');

        if ($pass1 !== $pass2) {
            $this->flash('error', 'Password yang dimasukkan tidak sama!');
            redirect('user/tambah');
            return;
        }

        $nip = trim($this->input->post('nip'));
        if ($this->M_user->get_by_nip($nip)) {
            $this->flash('error', 'NIP/username sudah terdaftar.');
            redirect('user/tambah');
            return;
        }

        $this->M_user->insert(array(
            'nip'        => $nip,
            'id_jabatan' => $this->input->post('jabatan'),
            'nama'       => trim($this->input->post('nama')),
            'pass'       => password_hash($pass1, PASSWORD_BCRYPT),
            'aktif'      => $this->input->post('aktif'),
            'operator'   => $this->input->post('operator') ? 1 : 0,
        ));

        $this->flash('success', 'User baru berhasil didaftarkan.');
        redirect('user');
    }

    public function edit($id)
    {
        $row = $this->M_user->get($id);
        if (! $row) {
            show_404();
        }

        $this->render('user/form', array(
            'jabatan' => $this->M_jabatan->get_all('id', 'ASC'),
            'row'     => $row,
        ), array(
            'title'    => 'Edit User',
            'subtitle' => $row->nama,
            'active'   => 'user',
        ));
    }

    public function update($id)
    {
        $row = $this->M_user->get($id);
        if (! $row) {
            show_404();
        }

        $data = array(
            'nama'       => trim($this->input->post('nama')),
            'nip'        => trim($this->input->post('nip')),
            'id_jabatan' => $this->input->post('jabatan'),
            'aktif'      => $this->input->post('aktif'),
            'operator'   => $this->input->post('operator') ? 1 : 0,
        );

        $pass1 = $this->input->post('pass1');
        if (! empty($pass1)) {
            $pass2 = $this->input->post('pass2');
            if ($pass1 !== $pass2) {
                $this->flash('error', 'Password yang dimasukkan tidak sama!');
                redirect('user/edit/' . $id);
                return;
            }
            $data['pass'] = password_hash($pass1, PASSWORD_BCRYPT);
        }

        $this->M_user->update($id, $data);
        $this->flash('success', 'Data user berhasil diperbarui.');
        redirect('user');
    }

    public function hapus($id)
    {
        $row = $this->M_user->get($id);
        if (! $row) {
            show_404();
        }
        if ($row->nip === 'admin') {
            $this->flash('error', 'Akun admin utama tidak dapat dihapus.');
            redirect('user');
            return;
        }

        $this->M_user->delete($id);
        $this->flash('success', 'User berhasil dihapus.');
        redirect('user');
    }
}

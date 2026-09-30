<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Surat_masuk - modul Surat Masuk (tabel `surmas`)
 */
class Surat_masuk extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('M_surmas', 'M_klasifikasi'));
    }

    /**
     * Form input surat masuk.
     */
    public function index()
    {
        $data = array(
            'klasifikasi' => $this->M_klasifikasi->get_all(),
        );

        $this->render('surat_masuk/input', $data, array(
            'title'    => 'Input Surat Masuk',
            'subtitle' => 'Catat surat masuk beserta berkasnya.',
            'active'   => 'surat-masuk',
        ));
    }

    /**
     * Simpan surat masuk baru (dengan upload PDF opsional).
     */
    public function simpan()
    {
        $data = array(
            'kode'         => $this->input->post('kode'),
            'no_agenda'    => $this->input->post('no_agenda'),
            'no_surat'     => $this->input->post('no_surat'),
            'tgl_surat'    => $this->input->post('tgl_surat'),
            'pengirim'     => $this->input->post('pengirim'),
            'perihal'      => $this->input->post('perihal'),
            'tgl_diterima' => $this->input->post('tgl_diterima'),
            'pengolah'     => $this->input->post('pengolah'),
            'keterangan'   => $this->input->post('keterangan'),
            'file'         => '',
        );

        if ( ! empty($_FILES['file']['name'])) {
            $this->load->library('upload', array(
                'upload_path'   => FCPATH . 'file/sm',
                'allowed_types' => 'pdf',
                'max_size'      => 10240,
            ));

            if ( ! $this->upload->do_upload('file')) {
                $this->flash('error', 'Gagal upload: ' . $this->upload->display_errors('', ''));
                redirect('surat-masuk');
                return;
            }

            $info = $this->upload->data();
            $tahun = date('Y');
            $perihal_short = substr(preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['perihal']), 0, 30);
            $new_name = $tahun . '_' . $data['kode'] . '_' . $perihal_short . $info['file_ext'];
            rename($info['full_path'], FCPATH . 'file/sm/' . $new_name);
            $data['file'] = 'file/sm/' . $new_name;
        }

        $this->M_surmas->insert($data);
        $this->flash('success', 'Surat masuk berhasil disimpan.');
        redirect('surat-masuk/daftar?tahun=' . date('Y'));
    }

    /**
     * Daftar surat masuk per tahun.
     */
    public function daftar()
    {
        $tahun = (int) $this->input->get('tahun');
        $tahun = $tahun ? $tahun : date('Y');

        $data = array(
            'tahun' => $tahun,
            'rows'  => $this->M_surmas->daftar_tahun($tahun),
        );

        $this->render('surat_masuk/daftar', $data, array(
            'title'    => 'Daftar Surat Masuk',
            'subtitle' => 'Data surat masuk tahun ' . $tahun,
            'active'   => 'surat-masuk',
        ));
    }

    /**
     * Form edit surat masuk.
     */
    public function edit($id)
    {
        $row = $this->M_surmas->get($id);
        if ( ! $row) {
            show_404();
        }

        $data = array(
            'row'         => $row,
            'klasifikasi' => $this->M_klasifikasi->get_all(),
        );

        $this->render('surat_masuk/edit', $data, array(
            'title'    => 'Edit Surat Masuk',
            'subtitle' => $row->no_surat,
            'active'   => 'surat-masuk',
        ));
    }

    /**
     * Simpan perubahan surat masuk.
     */
    public function update($id)
    {
        $row = $this->M_surmas->get($id);
        if ( ! $row) {
            show_404();
        }

        $data = array(
            'kode'         => $this->input->post('kode'),
            'no_agenda'    => $this->input->post('no_agenda'),
            'no_surat'     => $this->input->post('no_surat'),
            'tgl_surat'    => $this->input->post('tgl_surat'),
            'pengirim'     => $this->input->post('pengirim'),
            'perihal'      => $this->input->post('perihal'),
            'tgl_diterima' => $this->input->post('tgl_diterima'),
            'pengolah'     => $this->input->post('pengolah'),
            'keterangan'   => $this->input->post('keterangan'),
        );

        if ( ! empty($_FILES['file']['name'])) {
            $this->load->library('upload', array(
                'upload_path'   => FCPATH . 'file/sm',
                'allowed_types' => 'pdf',
                'max_size'      => 10240,
            ));

            if ( ! $this->upload->do_upload('file')) {
                $this->flash('error', 'Gagal upload: ' . $this->upload->display_errors('', ''));
                redirect('surat-masuk/edit/' . $id);
                return;
            }

            $info = $this->upload->data();
            $tahun = date('Y');
            $perihal_short = substr(preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['perihal']), 0, 30);
            $new_name = $tahun . '_' . $data['kode'] . '_' . $perihal_short . $info['file_ext'];
            rename($info['full_path'], FCPATH . 'file/sm/' . $new_name);
            $data['file'] = 'file/sm/' . $new_name;
        }

        $this->M_surmas->update($id, $data);
        $this->flash('success', 'Data surat masuk berhasil diperbarui.');
        redirect('surat-masuk/daftar?tahun=' . date('Y', strtotime($data['tgl_surat'])));
    }

    /**
     * Hapus surat masuk.
     */
    public function hapus($id)
    {
        $row = $this->M_surmas->get($id);
        if ( ! $row) {
            show_404();
        }
        if ( ! $this->user['is_admin']) {
            show_error('Hanya admin yang dapat menghapus data.', 403);
        }

        $this->M_surmas->delete($id);
        $this->flash('success', 'Surat masuk berhasil dihapus.');
        redirect('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat)));
    }

    /**
     * Lembar disposisi surat masuk (tampilan cetak HTML).
     */
    public function disposisi($id)
    {
        $row = $this->M_surmas->get($id);
        if ( ! $row) {
            show_404();
        }
        $this->load->view('surat_masuk/disposisi', array('row' => $row));
    }
}

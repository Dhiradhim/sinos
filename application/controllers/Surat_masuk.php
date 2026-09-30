<?php
defined('BASEPATH') or exit('No direct script access allowed');

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
     * Form input surat masuk (khusus operator surat).
     */
    public function index()
    {
        if (empty($this->user['is_operator'])) {
            show_error('Halaman ini hanya untuk operator surat.', 403);
        }

        $data = array(
            'klasifikasi' => $this->M_klasifikasi->get_all(),
            // Nomor agenda otomatis: {no-urut}/SM/PA.Kp/{Tahun}
            'next_agenda' => $this->M_surmas->next_no_agenda(),
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
        if (empty($this->user['is_operator'])) {
            show_error('Hanya operator surat yang dapat menambah surat masuk.', 403);
        }

        // Nomor agenda dibuat otomatis di sisi server agar tidak dapat diubah
        // dari sisi klien: {no-urut}/SM/PA.Kp/{Tahun}
        $tahun_agenda = (int) date('Y', strtotime($this->input->post('tgl_diterima')));
        if (! $tahun_agenda) {
            $tahun_agenda = (int) date('Y');
        }

        $data = array(
            'kode'         => $this->input->post('kode'),
            'no_agenda'    => $this->M_surmas->next_no_agenda($tahun_agenda),
            'no_surat'     => $this->input->post('no_surat'),
            'tgl_surat'    => $this->input->post('tgl_surat'),
            'pengirim'     => $this->input->post('pengirim'),
            'perihal'      => $this->input->post('perihal'),
            'tgl_diterima' => $this->input->post('tgl_diterima'),
            'pengolah'     => $this->input->post('pengolah'),
            'keterangan'   => $this->input->post('keterangan'),
            'file'         => '',
        );

        // Dokumen wajib diunggah pada input surat masuk baru
        if (empty($_FILES['file']['name'])) {
            $this->flash('error', 'Dokumen surat (PDF) wajib diunggah.');
            redirect('surat-masuk');
            return;
        }

        if (! empty($_FILES['file']['name'])) {
            $this->load->library('upload', array(
                'upload_path'   => FCPATH . 'file/sm',
                'allowed_types' => 'pdf',
                'max_size'      => 10240,
            ));

            if (! $this->upload->do_upload('file')) {
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

        $rows = $this->M_surmas->daftar_tahun($tahun);

        // Ambil status disposisi untuk seluruh surat pada tahun ini
        $this->load->model('M_disposisi');
        $surmas_ids = array();
        foreach ($rows as $r) {
            $surmas_ids[] = (int) $r->id;
        }
        $status_disposisi = $this->M_disposisi->status_by_surmas($surmas_ids);

        // Tentukan siapa yang berhak mengirim/meneruskan disposisi per surat:
        // operator (semua surat kecuali yang sudah diarsipkan) atau penerima
        // disposisi surat tersebut. Surat yang sudah diarsipkan tidak dapat
        // lagi didisposisikan.
        $can_send_map = array();
        foreach ($rows as $r) {
            $diarsipkan = ($r->status === 'diarsipkan');
            $can_send_map[(int) $r->id] = ! $diarsipkan && (
                ! empty($this->user['is_operator'])
                || $this->M_disposisi->can_send((int) $r->id, $this->user['nip'], FALSE)
            );
        }

        $data = array(
            'tahun'            => $tahun,
            'tahun_list'       => tahun_tersedia(),
            'use_datatables'   => TRUE,
            'rows'             => $rows,
            'status_disposisi' => $status_disposisi,
            'can_send_map'     => $can_send_map,
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
        if (! $row) {
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
        if (! $row) {
            show_404();
        }

        // no_agenda tidak disertakan agar nomor agenda lama tidak berubah.
        $data = array(
            'kode'         => $this->input->post('kode'),
            'no_surat'     => $this->input->post('no_surat'),
            'tgl_surat'    => $this->input->post('tgl_surat'),
            'pengirim'     => $this->input->post('pengirim'),
            'perihal'      => $this->input->post('perihal'),
            'tgl_diterima' => $this->input->post('tgl_diterima'),
            'pengolah'     => $this->input->post('pengolah'),
            'keterangan'   => $this->input->post('keterangan'),
        );

        // Nilai kolom `file` hanya diubah bila ada dokumen baru yang diunggah;
        // jika tidak, berkas lama di database tetap dipertahankan.
        if (! empty($_FILES['file']['name'])) {
            $this->load->library('upload', array(
                'upload_path'   => FCPATH . 'file/sm',
                'allowed_types' => 'pdf',
                'max_size'      => 10240,
            ));

            if (! $this->upload->do_upload('file')) {
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
        if (! $row) {
            show_404();
        }
        if (! $this->user['is_admin']) {
            show_error('Hanya admin yang dapat menghapus data.', 403);
        }

        $this->M_surmas->delete($id);
        $this->flash('success', 'Surat masuk berhasil dihapus.');
        redirect('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat)));
    }

    /**
     * Arsipkan surat masuk (hanya operator surat, setelah disposisi dikembalikan).
     */
    public function arsipkan($id)
    {
        if (empty($this->user['is_operator'])) {
            show_error('Hanya operator surat yang dapat mengarsipkan surat.', 403);
        }

        $row = $this->M_surmas->get($id);
        if (! $row) {
            show_404();
        }

        $this->load->model('M_disposisi');

        // Surat yang sudah pernah didisposisi hanya boleh diarsipkan
        // setelah semua disposisi dikembalikan ke operator.
        $disposisi_ada = $this->db->where('surmas_id', $id)->count_all_results('disposisi');
        if ($disposisi_ada > 0 && ! $this->M_disposisi->semua_dikembalikan($id)) {
            $this->flash('error', 'Surat belum dapat diarsipkan. Menunggu disposisi dikembalikan ke operator.');
            redirect('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat)));
            return;
        }

        $this->M_surmas->set_status($id, 'diarsipkan');
        $this->flash('success', 'Surat masuk berhasil diarsipkan.');
        redirect('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat)));
    }

    /**
     * Batalkan arsip surat masuk.
     * Status dikembalikan ke posisi disposisi terakhir (user terakhir yang
     * menerima disposisi), sehingga surat kembali terlihat di menu Disposisi
     * bagi operator maupun penerima disposisi terakhir.
     */
    public function batal_arsip($id)
    {
        if (empty($this->user['is_operator'])) {
            show_error('Hanya operator surat yang dapat mengubah status arsip.', 403);
        }

        $row = $this->M_surmas->get($id);
        if (! $row) {
            show_404();
        }

        $this->load->model('M_disposisi');

        // Tentukan status tujuan: bila surat sudah pernah didisposisikan,
        // kembalikan ke status 'didiposisi' (posisi ada di penerima terakhir);
        // jika belum pernah didisposisikan, kembalikan ke 'aktif'.
        $ada = $this->db->where('surmas_id', $id)->count_all_results('disposisi');
        $status = ($ada > 0) ? 'didiposisi' : 'aktif';

        $this->M_surmas->set_status($id, $status);
        $this->flash('success', 'Status arsip surat dibatalkan.');
        redirect('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat)));
    }

    /**
     * Detail surat masuk (dapat dilihat oleh semua user yang login).
     */
    public function detail($id)
    {
        $row = $this->M_surmas->get($id);
        if (! $row) {
            show_404();
        }

        $this->load->model('M_disposisi');

        // Riwayat disposisi surat ini
        $riwayat = $this->db->order_by('tanggal', 'ASC')
            ->get_where('disposisi', array('surmas_id' => $id))->result();

        // Posisi disposisi saat ini (disposisi terakhir)
        $posisi = ! empty($riwayat) ? end($riwayat) : NULL;

        $this->render('surat_masuk/detail', array(
            'row'     => $row,
            'riwayat' => $riwayat,
            'posisi'  => $posisi,
        ), array(
            'title'    => 'Detail Surat Masuk',
            'subtitle' => $row->no_surat,
            'active'   => 'surat-masuk',
        ));
    }

    /**
     * Lembar disposisi surat masuk (tampilan cetak HTML).
     */
    public function disposisi($id)
    {
        $row = $this->M_surmas->get($id);
        if (! $row) {
            show_404();
        }
        $this->load->view('surat_masuk/disposisi', array('row' => $row));
    }
}

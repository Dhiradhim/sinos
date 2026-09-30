<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Disposisi - modul Disposisi Surat Masuk.
 */
class Disposisi extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('M_disposisi', 'M_surmas', 'M_user'));
    }

    /**
     * Daftar disposisi surat masuk (mirip daftar surat masuk), dengan pemilih tahun.
     */
    public function index()
    {
        $tahun = (int) $this->input->get('tahun');
        $tahun = $tahun ? $tahun : date('Y');

        // Admin melihat semua disposisi; user biasa hanya yang ditujukan kepadanya.
        if ($this->user['is_admin']) {
            $rows = $this->M_disposisi->daftar_tahun($tahun);
        } else {
            $rows = $this->M_disposisi->daftar_untuk($this->user['nip'], $tahun);
        }

        $data = array(
            'tahun'          => $tahun,
            'tahun_list'     => tahun_tersedia(),
            'use_datatables' => TRUE,
            'rows'           => $rows,
        );

        $this->render('disposisi/index', $data, array(
            'title'    => 'Disposisi Surat',
            'subtitle' => 'Daftar disposisi surat masuk tahun ' . $tahun,
            'active'   => 'disposisi',
        ));
    }

    /**
     * Form pop-up/modal kirim disposisi untuk satu surat masuk.
     * Ditampilkan sebagai halaman mandiri (dipanggil via AJAX untuk modal).
     */
    public function form($surmas_id)
    {
        $surat = $this->M_surmas->get($surmas_id);
        if (! $surat) {
            show_404();
        }

        $data = array(
            'surat'    => $surat,
            'penerima' => $this->M_user->penerima_aktif(),
            'riwayat'  => $this->db->order_by('tanggal', 'DESC')
                ->get_where('disposisi', array('surmas_id' => $surmas_id))->result(),
        );

        $this->render('disposisi/form', $data, array(
            'title'    => 'Kirim Disposisi',
            'subtitle' => $surat->no_surat,
            'active'   => 'disposisi',
        ));
    }

    /**
     * Endpoint AJAX: mengembalikan isi form disposisi (tanpa layout)
     * untuk ditampilkan di dalam modal pop-up.
     */
    public function form_ajax($surmas_id)
    {
        $surat = $this->M_surmas->get($surmas_id);
        if (! $surat) {
            show_404();
        }

        $this->load->view('disposisi/_form_ajax', array(
            'surat'    => $surat,
            'penerima' => $this->M_user->penerima_aktif(),
            'riwayat'  => $this->db->order_by('tanggal', 'DESC')
                ->get_where('disposisi', array('surmas_id' => $surmas_id))->result(),
        ));
    }

    /**
     * Proses kirim disposisi.
     */
    public function kirim()
    {
        $surmas_id = (int) $this->input->post('surmas_id');
        $surat = $this->M_surmas->get($surmas_id);
        if (! $surat) {
            show_404();
        }

        $target = $this->input->post('target'); // single value: id user atau "Arsip"
        $instruksi = $this->input->post('instruksi');
        $catatan   = $this->input->post('catatan');

        if (empty($target)) {
            $this->flash('error', 'Pilih penerima disposisi.');
            redirect('disposisi/form/' . $surmas_id);
            return;
        }

        // Peta penerima aktif: id -> data user ("Jabatan (Nama)")
        $penerima_aktif = $this->M_user->penerima_aktif();
        $map = array();
        foreach ($penerima_aktif as $u) {
            $map[(string) $u->id] = $u;
        }

        $rows = array();
        if ($target === 'Arsip') {
            $rows[] = array(
                'kepada'     => 'Arsip',
                'kategori'   => 'Arsip',
                'nip_tujuan' => NULL,
            );
        } elseif (isset($map[(string) $target])) {
            $u = $map[(string) $target];
            $rows[] = array(
                'kepada'     => $u->nama,
                'kategori'   => $u->jabatan,
                'nip_tujuan' => $u->nip,
            );
        }

        $n = $this->M_disposisi->simpan_batch($surmas_id, $rows, $this->user['nip'], $instruksi, $catatan);

        if ($n > 0) {
            $this->flash('success', 'Disposisi berhasil dikirim kepada ' . $n . ' penerima.');
        } else {
            $this->flash('error', 'Tidak ada penerima valid yang dipilih.');
        }
        redirect('disposisi');
    }

    /**
     * Detail satu disposisi (opsional).
     */
    public function detail($id)
    {
        $row = $this->M_disposisi->get($id);
        if (! $row) {
            show_404();
        }

        // Tandai dibaca bila ditujukan ke user ini
        if ($row->nip_tujuan === $this->user['nip']) {
            $this->M_disposisi->tandai_dibaca($id);
        }

        $surat = $this->M_surmas->get($row->surmas_id);

        $this->render('disposisi/detail', array(
            'row'   => $row,
            'surat' => $surat,
        ), array(
            'title'    => 'Detail Disposisi',
            'subtitle' => $surat ? $surat->no_surat : '',
            'active'   => 'disposisi',
        ));
    }

    /**
     * Hapus disposisi (admin atau pengirim).
     */
    public function hapus($id)
    {
        $row = $this->M_disposisi->get($id);
        if (! $row) {
            show_404();
        }
        if (! $this->user['is_admin'] && $row->dari !== $this->user['nip']) {
            show_error('Anda tidak berhak menghapus disposisi ini.', 403);
        }

        $this->M_disposisi->delete($id);
        $this->flash('success', 'Disposisi berhasil dihapus.');
        redirect('disposisi');
    }
}

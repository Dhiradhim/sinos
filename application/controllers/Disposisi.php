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
        // Operator (atau admin) melihat seluruh surat yang sedang/pernah
        // didisposisi, termasuk yang sudah diarsipkan. User lain hanya melihat
        // surat yang posisi disposisinya sedang ada padanya.
        if (! empty($this->user['is_operator'])) {
            $rows = $this->M_disposisi->daftar_operator();
            $subtitle = 'Seluruh surat yang didisposisikan (termasuk yang sudah diarsipkan).';
        } else {
            $rows = $this->M_disposisi->daftar_atas_nama($this->user['nip']);
            $subtitle = 'Disposisi yang posisinya sedang ada pada Anda.';
        }

        // Daftar NIP user yang bertag operator, untuk menentukan apakah posisi
        // disposisi surat saat ini berada pada seorang operator.
        $operator_nips = array();
        foreach ($this->db->select('nip')->where('operator', 1)->get('user')->result() as $ou) {
            $operator_nips[(string) $ou->nip] = TRUE;
        }

        // Sertakan lokasi & status posisi disposisi (user/arsip tempat surat berada)
        foreach ($rows as $r) {
            $r->lokasi = ((string) $r->kepada === 'Arsip' || empty($r->nip_tujuan))
                ? 'Arsip'
                : $r->kepada;

            // Posisi surat berada di operator bila penerima disposisi terakhir
            // adalah user bertag operator (dan belum dikembalikan / diarsip).
            $r->posisi_di_operator = ($r->status_surat !== 'diarsipkan'
                && (int) $r->dikembalikan === 0
                && ! empty($r->nip_tujuan)
                && isset($operator_nips[(string) $r->nip_tujuan]));
        }

        $data = array(
            'use_datatables' => TRUE,
            'rows'           => $rows,
            'is_operator'    => ! empty($this->user['is_operator']),
        );

        $this->render('disposisi/index', $data, array(
            'title'    => 'Disposisi Surat',
            'subtitle' => $subtitle,
            'active'   => 'disposisi',
        ));
    }

    /**
     * Arsipkan surat masuk dari menu disposisi.
     * Bila sebuah surat sudah didisposisikan kepada user lain, setiap user
     * yang memiliki tag operator berhak mengarsipkan surat tersebut.
     */
    public function arsipkan($surmas_id)
    {
        if (empty($this->user['is_operator'])) {
            show_error('Hanya operator surat yang dapat mengarsipkan surat.', 403);
        }

        $surat = $this->M_surmas->get($surmas_id);
        if (! $surat) {
            show_404();
        }

        if ($surat->status === 'diarsipkan') {
            $this->flash('warning', 'Surat sudah diarsipkan.');
            redirect('disposisi');
            return;
        }

        // Wajib sudah pernah didisposisikan
        $ada = $this->db->where('surmas_id', $surmas_id)->count_all_results('disposisi');
        if ($ada === 0) {
            $this->flash('error', 'Surat belum pernah didisposisikan.');
            redirect('disposisi');
            return;
        }

        // Surat hanya dapat diarsipkan bila posisi disposisi saat ini berada
        // pada seorang operator (penerima disposisi terakhir bertag operator).
        $posisi = $this->M_disposisi->posisi_terakhir(array($surmas_id));
        $last = isset($posisi[$surmas_id]) ? $posisi[$surmas_id] : NULL;
        $posisi_di_operator = ($last
            && (int) $last->dikembalikan === 0
            && ! empty($last->nip_tujuan)
            && $this->M_disposisi->is_operator_nip($last->nip_tujuan));

        if (! $posisi_di_operator) {
            $this->flash('error', 'Surat hanya dapat diarsipkan ketika posisinya berada di operator.');
            redirect('disposisi');
            return;
        }

        $this->M_surmas->set_status($surmas_id, 'diarsipkan');
        $this->flash('success', 'Surat berhasil diarsipkan.');
        redirect('disposisi');
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

        if (
            empty($this->user['is_operator'])
            && ! $this->M_disposisi->can_send($surmas_id, $this->user['nip'], FALSE)
        ) {
            show_error('Anda tidak berhak mengirim disposisi surat ini.', 403);
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

        if (
            empty($this->user['is_operator'])
            && ! $this->M_disposisi->can_send($surmas_id, $this->user['nip'], FALSE)
        ) {
            show_error('Anda tidak berhak mengirim disposisi surat ini.', 403);
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

        // Operator surat boleh mengirim disposisi awal; penerima disposisi
        // berhak meneruskan disposisi surat tersebut.
        if (
            empty($this->user['is_operator'])
            && ! $this->M_disposisi->can_send($surmas_id, $this->user['nip'], FALSE)
        ) {
            show_error('Hanya operator surat atau penerima disposisi yang dapat mengirim disposisi.', 403);
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
            // Tandai surat sedang dalam proses disposisi
            $this->M_surmas->set_status($surmas_id, 'didiposisi');
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

        // Berhak meneruskan bila operator surat atau penerima disposisi surat ini
        $can_forward = ! empty($this->user['is_operator'])
            || $this->M_disposisi->can_send($row->surmas_id, $this->user['nip'], FALSE);

        $this->render('disposisi/detail', array(
            'row'         => $row,
            'surat'       => $surat,
            'can_forward' => $can_forward,
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

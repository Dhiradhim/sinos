<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Api_Controller.php';

/**
 * API Dashboard - ringkasan beranda.
 *
 *   GET api/v1/dashboard?tahun=YYYY
 *
 * Meniru persis apa yang dibangun dashboard/index.php, tetapi mengirim nama
 * tone netral (brand, sky, amber, danger, ok) alih-alih kelas Tailwind:
 *
 *   stats[]                 key, label, value, href, tone, icon
 *   latest[]                nomor surat keluar terbaru milik user
 *   disposisi_belum_dibaca  angka badge sidebar
 *
 * Perhitungan memakai method model yang sudah ada:
 *   M_nosur::count_tahun(), M_nosur::count_belum_upload(),
 *   M_nosur::daftar_user(), M_surmas::count_tahun(), M_user::count_all(),
 *   M_disposisi::count_belum_dibaca().
 *
 * Catatan: dashboard/index.php memakai tahun berjalan tanpa parameter. Di sini
 * `?tahun=` diterima agar pemilih tahun Astro dapat memakai endpoint yang sama;
 * nilai kosong / tidak valid jatuh ke tahun berjalan.
 */
class Dashboard extends MY_Api_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('api_present');
        $this->load->model(array('M_nosur', 'M_surmas', 'M_user', 'M_disposisi'));
    }

    /**
     * GET dashboard
     */
    public function index()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET.', 405);
            return;
        }

        $nip   = (string) $this->user['sub'];
        $tahun = (int) $this->input->get('tahun');
        $tahun = $tahun ? $tahun : (int) date('Y');

        $total_surat_keluar = (int) $this->M_nosur->count_tahun($tahun);
        $total_surat_masuk  = (int) $this->M_surmas->count_tahun($tahun);
        $belum_upload       = (int) $this->M_nosur->count_belum_upload($nip, $tahun);

        $surat_keluar_saya_rows = $this->M_nosur->daftar_user($nip, $tahun);
        $surat_keluar_saya      = count($surat_keluar_saya_rows);

        // Susunan kartu statistik mengikuti dashboard/index.php baris demi baris.
        $stats = array(
            array(
                'key'   => 'surat_keluar',
                'label' => 'Total Surat Keluar',
                'value' => $total_surat_keluar,
                'href'  => '/surat-keluar/daftar-semua?tahun=' . $tahun,
                'tone'  => 'brand',
                'icon'  => 'folder',
            ),
            array(
                'key'   => 'surat_masuk',
                'label' => 'Total Surat Masuk',
                'value' => $total_surat_masuk,
                'href'  => '/surat-masuk/daftar?tahun=' . $tahun,
                'tone'  => 'sky',
                'icon'  => 'inbox',
            ),
            array(
                'key'   => 'surat_keluar_saya',
                'label' => 'Surat Keluar Saya',
                'value' => $surat_keluar_saya,
                'href'  => '/surat-keluar/daftar?tahun=' . $tahun,
                'tone'  => 'amber',
                'icon'  => 'user',
            ),
            array(
                'key'   => 'belum_upload',
                'label' => 'Belum Upload Berkas',
                'value' => $belum_upload,
                // View memakai merah bila ada tunggakan, hijau bila nol.
                'href'  => '/surat-keluar/daftar?tahun=' . $tahun,
                'tone'  => $belum_upload > 0 ? 'danger' : 'ok',
                'icon'  => 'upload',
            ),
        );

        // Lima nomor surat terakhir milik user (array_slice seperti controller).
        $latest = array();
        foreach (array_slice($surat_keluar_saya_rows, 0, 5) as $row) {
            $latest[] = array(
                'id'         => (int) $row->id,
                'no'         => (string) $row->no,
                'tanggal'    => api_tanggal(isset($row->tanggal) ? $row->tanggal : NULL),
                'hal'        => (string) $row->hal,
                'tujuan'     => (string) $row->tujuan,
                'nama'       => isset($row->nama) ? (string) $row->nama : '',
                'file'       => api_file_meta(isset($row->file) ? $row->file : NULL),
            );
        }

        $this->ok(array(
            'tahun'                 => $tahun,
            'stats'                 => $stats,
            'latest'                => $latest,
            'disposisi_belum_dibaca' => (int) $this->M_disposisi->count_belum_dibaca($nip),
        ));
    }
}

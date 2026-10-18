<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Api_Controller.php';

/**
 * API Laporan - pilihan form cetak + stream PDF.
 *
 *   GET  api/v1/laporan/opsi    data form laporan/index.php
 *   POST api/v1/laporan/cetak   application/pdf (binary)
 *
 * `cetak` meniru `Laporan::cetak()`:
 *   - filter yang sama (tipelaporan, tgl_cetak, bulan, tahun, kode),
 *   - view yang sama (`laporan/pdf.php`),
 *   - library yang sama (`Pdf` = mPDF),
 *   - nama berkas yang sama (`laporan_{tipelaporan}_{tahun}.pdf`),
 *   - hanya admin (403).
 *
 * Perbedaan satu-satunya: `Laporan::cetak()` memakai mPDF mode 'I'
 * (inline) yang langsung mengirim header sendiri; endpoint ini memakai mode
 * 'S' lalu mengirim biner lewat output CI3 dengan
 * `Content-Disposition: attachment` sesuai kontrak Fase 2.
 */
class Laporan extends MY_Api_Controller
{
    /**
     * Daftar bulan, identik dengan properti $bulan pada Laporan.php.
     *
     * @var array
     */
    protected $bulan = array(
        '00' => 'Tahun',
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'Nopember',
        '12' => 'Desember',
    );

    public function __construct()
    {
        parent::__construct();

        $this->load->model('M_klasifikasi');
    }

    /**
     * GET laporan/opsi
     */
    public function opsi()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET.', 405);
            return;
        }

        // Laporan::__construct() membatasi halaman ini untuk admin. Endpoint
        // mengikuti aturan yang sama agar Astro menampilkan 403 yang konsisten.
        if (empty($this->user['admin'])) {
            $this->fail('FORBIDDEN', 'Halaman laporan hanya untuk admin.', 403);
            return;
        }

        $bulan = array();
        foreach ($this->bulan as $num => $nama) {
            $bulan[] = array(
                'value' => (string) $num,
                'label' => (string) $nama,
            );
        }

        // View memakai rentang tahun berjalan sampai 5 tahun ke belakang.
        $tahun = array();
        $now   = (int) date('Y');
        for ($y = $now; $y >= $now - 5; $y--) {
            $tahun[] = $y;
        }

        // M_klasifikasi::daftar_kode() adalah sumber yang sama dengan view.
        $klasifikasi = array();
        foreach ($this->M_klasifikasi->daftar_kode() as $k) {
            $klasifikasi[] = array(
                'kode' => (string) $k->kode,
                'nama' => isset($k->nama) ? (string) $k->nama : '',
            );
        }

        $this->ok(array(
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'klasifikasi' => $klasifikasi,
            'jenis'       => array(
                array('value' => 'surkel', 'label' => 'Agenda Surat Keluar'),
                array('value' => 'surmas', 'label' => 'Agenda Surat Masuk'),
            ),
            'default'     => array(
                'tipelaporan' => 'surkel',
                'bulan'       => '00',
                'tahun'       => $now,
                'kode'        => 'all',
                'tgl_cetak'   => date('d-m-Y'),
            ),
        ));
    }

    /**
     * POST api/v1/laporan/cetak
     *
     * Body (semua opsional, default sama dengan controller HTML):
     *   tipelaporan  surkel|surmas
     *   tgl_cetak    dd-mm-yyyy (teks yang dicetak di footer)
     *   bulan        00..12
     *   tahun        YYYY
     *   kode         'all' atau kode klasifikasi
     */
    public function cetak()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode POST.', 405);
            return;
        }

        if (empty($this->user['admin'])) {
            $this->fail('FORBIDDEN', 'Halaman laporan hanya untuk admin.', 403);
            return;
        }

        $input = $this->input_json();

        $tipelaporan = isset($input['tipelaporan']) && $input['tipelaporan'] !== ''
            ? (string) $input['tipelaporan']
            : 'surkel';
        $tgl_cetak   = isset($input['tgl_cetak']) && $input['tgl_cetak'] !== ''
            ? (string) $input['tgl_cetak']
            : date('d-m-Y');
        $cbulan      = isset($input['bulan']) && $input['bulan'] !== ''
            ? (string) $input['bulan']
            : '00';
        $ctahun      = isset($input['tahun']) && $input['tahun'] !== ''
            ? (string) $input['tahun']
            : date('Y');
        $kode        = isset($input['kode']) && $input['kode'] !== ''
            ? (string) $input['kode']
            : 'all';

        $tabel   = ($tipelaporan === 'surmas') ? 'surmas' : 'nosur';
        $judul   = ($tipelaporan === 'surmas') ? 'Agenda Surat Masuk' : 'Agenda Surat Keluar';
        $col_tgl = ($tipelaporan === 'surkel') ? 'tanggal' : 'tgl_diterima';
        $col_no  = ($tipelaporan === 'surkel') ? 'no' : 'no_surat';
        $col_hal = ($tipelaporan === 'surkel') ? 'hal' : 'perihal';

        // Query data - identik dengan Laporan::cetak().
        $this->db->select('*');
        if ($tipelaporan === 'surkel') {
            $this->db->where('hal !=', 'Belum Diambil');
        }
        if ($cbulan !== '00') {
            $this->db->where("MONTH($col_tgl)", $cbulan);
        }
        $this->db->where("YEAR($col_tgl)", $ctahun);
        if ($kode !== 'all') {
            if ($tipelaporan === 'surmas') {
                $this->db->like('kode', $kode);
            } else {
                $this->db->where('kode', $kode);
            }
        }
        $this->db->order_by($col_tgl, 'ASC');
        $rows = $this->db->get($tabel)->result();

        $namabulan = isset($this->bulan[$cbulan]) ? $this->bulan[$cbulan] : 'Tahun';

        $html = $this->load->view('laporan/pdf', array(
            'judul'     => $judul,
            'rows'      => $rows,
            'col_no'    => $col_no,
            'col_tgl'   => $col_tgl,
            'col_hal'   => $col_hal,
            'namabulan' => $namabulan,
            'ctahun'    => $ctahun,
            'tgl_cetak' => $tgl_cetak,
        ), TRUE);

        $this->load->library('pdf');
        $this->pdf->write_html($html);

        // Mode 'S' mengembalikan biner PDF sebagai string (tanpa header mPDF).
        $binary = $this->pdf->output('laporan_' . $tipelaporan . '_' . $ctahun . '.pdf', 'S');

        if (! is_string($binary) || strlen($binary) < 100) {
            $this->fail('PDF_GAGAL', 'Dokumen PDF gagal dibuat.', 500);
            return;
        }

        $filename = 'laporan_' . $tipelaporan . '_' . $ctahun . '.pdf';

        // Header disusun lewat CI_Output (bukan header() mentah) agar urutan
        // pengiriman konsisten: MY_Api_Controller sudah memasang
        // Content-Type: application/json, dan header di bawah menggantikannya.
        // `Content-Length` membantu klien memastikan unduhan tidak terpotong.
        $this->output->set_header('Content-Type: application/pdf');
        $this->output->set_header('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->output->set_header('Content-Length: ' . strlen($binary));
        $this->output->set_header('Cache-Control: private, max-age=0, must-revalidate');
        $this->output->set_header('X-Content-Type-Options: nosniff');
        $this->output->set_output($binary);
    }
}

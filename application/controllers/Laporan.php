<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Laporan - cetak laporan agenda surat (mPDF).
 */
class Laporan extends MY_Controller
{
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
        '12' => 'Desember'
    );

    public function __construct()
    {
        parent::__construct();
        if (! $this->user['is_admin']) {
            show_error('Halaman ini hanya untuk admin.', 403);
        }
        $this->load->model('M_klasifikasi');
    }

    /**
     * Form pilihan laporan.
     */
    public function index()
    {
        $this->render('laporan/index', array(
            'klasifikasi' => $this->M_klasifikasi->daftar_kode(),
            'bulan'       => $this->bulan,
        ), array(
            'title'    => 'Cetak Laporan',
            'subtitle' => 'Cetak agenda surat keluar / surat masuk.',
            'active'   => 'laporan',
        ));
    }

    /**
     * Proses cetak laporan ke PDF.
     */
    public function cetak()
    {
        $tipelaporan = $this->input->post('tipelaporan') ?: 'surkel';
        $tgl_cetak   = $this->input->post('tgl_cetak') ?: date('d-m-Y');
        $cbulan      = $this->input->post('bulan') ?: '00';
        $ctahun      = $this->input->post('tahun') ?: date('Y');
        $kode        = $this->input->post('kode') ?: 'all';

        $tabel = ($tipelaporan === 'surmas') ? 'surmas' : 'nosur';
        $judul = ($tipelaporan === 'surmas') ? 'Agenda Surat Masuk' : 'Agenda Surat Keluar';
        $col_tgl = ($tipelaporan === 'surkel') ? 'tanggal' : 'tgl_diterima';
        $col_no  = ($tipelaporan === 'surkel') ? 'no' : 'no_surat';
        $col_hal = ($tipelaporan === 'surkel') ? 'hal' : 'perihal';

        // Query data
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
        $this->pdf->output('laporan_' . $tipelaporan . '_' . $ctahun . '.pdf', 'I');
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dashboard - halaman beranda.
 */
class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('M_nosur', 'M_surmas', 'M_user'));
    }

    public function index()
    {
        $nip   = $this->user['nip'];
        $tahun = date('Y');

        $data = array(
            'total_surat_keluar' => $this->M_nosur->count_tahun($tahun),
            'total_surat_masuk'  => $this->M_surmas->count_tahun($tahun),
            'total_user'         => $this->M_user->count_all(),
            'belum_upload'       => $this->M_nosur->count_belum_upload($nip, $tahun),
            'surat_keluar_saya'  => count($this->M_nosur->daftar_user($nip, $tahun)),
            'terbaru'            => array_slice($this->M_nosur->daftar_user($nip, $tahun), 0, 5),
            'tahun'              => $tahun,
        );

        $this->render('dashboard/index', $data, array(
            'title'    => 'Beranda',
            'subtitle' => 'Ringkasan aktivitas persuratan tahun ' . $tahun,
            'active'   => 'dashboard',
        ));
    }
}

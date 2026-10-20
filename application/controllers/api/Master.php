<?php
defined('BASEPATH') or exit('No direct script access allowed');

// CI3 hanya memuat otomatis application/core/MY_Controller.php, sehingga base
// controller API harus di-require eksplisit (sama seperti api/Auth.php).
require_once APPPATH . 'core/MY_Api_Controller.php';

/**
 * API Master - daftar referensi yang dibutuhkan form.
 *
 * Endpoint:
 *   GET api/v1/master/klasifikasi    daftar kode + nama klasifikasi
 *   GET api/v1/master/pegawai        pegawai aktif calon penerima disposisi
 *   GET api/v1/master/tahun          tahun aktif + daftar tahun tersedia
 *   GET api/v1/master/pengambil      calon pengambil nomor surat keluar
 *   GET api/v1/master/penandatangan  pejabat penanda tangan per grup jabatan
 *
 * `pegawai` memakai M_user::penerima_aktif() (pegawai aktif TANPA akun
 * administrator, label "Jabatan (Nama)") karena itulah daftar yang dipakai
 * form disposisi — akun admin tidak boleh menjadi penerima disposisi.
 * `pengambil` dan `penandatangan` adalah dua daftar yang dipakai form
 * ambil/sisip nomor surat keluar (lihat surat_keluar/_form_nomor_fields.php);
 * keduanya hanya membaca method model yang sudah ada.
 *
 * Semua endpoint hanya baca; tidak ada penulisan ke database.
 */
class Master extends MY_Api_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('api_present');
        $this->load->model(array('M_klasifikasi', 'M_user'));
    }

    /**
     * Rute api/v1/master/<nama> memetakan segmen terakhir ke nama method.
     * _remap dipakai agar nama method yang tidak dikenal tetap menghasilkan
     * JSON 404 (bukan halaman error HTML milik CI3).
     *
     * @param  string $method
     * @param  array  $params
     * @return void
     */
    public function _remap($method, $params = array())
    {
        $allowed = array('klasifikasi', 'pegawai', 'tahun', 'pengambil', 'penandatangan');

        if (! in_array($method, $allowed, TRUE) || ! method_exists($this, $method)) {
            $this->fail('NOT_FOUND', 'Sumber data master tidak dikenal.', 404);
            return;
        }

        if ($this->input->method(TRUE) !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET.', 405);
            return;
        }

        $this->{$method}();
    }

    /**
     * GET /api/v1/master/klasifikasi
     *
     * Sumber: M_klasifikasi::get_all() (tabel ref_klasifikasi, urut kode ASC).
     * Hanya kode + nama yang dikirim; `id` dan `uraian` tidak dipakai form.
     */
    public function klasifikasi()
    {
        $out = array();

        foreach ($this->M_klasifikasi->get_all() as $row) {
            $out[] = array(
                'kode' => (string) $row->kode,
                'nama' => isset($row->nama) ? (string) $row->nama : '',
            );
        }

        $this->ok($out);
    }

    /**
     * GET /api/v1/master/pegawai
     *
     * Pegawai AKTIF yang dapat menerima disposisi (M_user::penerima_aktif()),
     * urut jabatan lalu nama. Akun administrator dikecualikan oleh model.
     */
    public function pegawai()
    {
        $out = array();

        foreach ($this->M_user->penerima_aktif() as $row) {
            $out[] = array(
                'nip'     => (string) $row->nip,
                'nama'    => (string) $row->nama,
                'jabatan' => (string) $row->jabatan,
                'label'   => (string) $row->label,
            );
        }

        $this->ok($out);
    }

    /**
     * GET /api/v1/master/tahun
     *
     * `tahun_aktif` = tahun berjalan (default yang dipakai controller halaman
     * bila query `?tahun=` kosong). `daftar` = hasil tahun_tersedia() yang
     * dipakai layouts/_tahun_selector.php, urut menurun.
     */
    public function tahun()
    {
        $this->ok(array(
            'tahun_aktif' => (int) date('Y'),
            'daftar'      => array_map('intval', tahun_tersedia()),
        ));
    }

    /**
     * GET /api/v1/master/pengambil
     *
     * Calon pengambil nomor surat keluar (M_user::pengambil_nomor()):
     * pegawai aktif, tanpa akun administrator.
     */
    public function pengambil()
    {
        $out = array();

        foreach ($this->M_user->pengambil_nomor() as $row) {
            $out[] = array(
                'nip'          => (string) $row->nip,
                'nama'         => (string) $row->nama,
                'jabatan'      => isset($row->jabatan) ? (string) $row->jabatan : '',
                'kode_jabatan' => isset($row->kode_jabatan) ? (string) $row->kode_jabatan : '',
                'label'        => (string) $row->label,
            );
        }

        $this->ok($out);
    }

    /**
     * GET /api/v1/master/penandatangan
     *
     * Pejabat penanda tangan dikelompokkan sesuai config
     * `penandatangan_jabatan` (M_user::penandatangan_grup()).
     * Tiap elemen: grup (nama grup) + items (daftar pejabat).
     */
    public function penandatangan()
    {
        $grup_cfg = $this->config->item('penandatangan_jabatan');
        if (empty($grup_cfg)) {
            $grup_cfg = array();
        }

        $out = array();

        foreach ($this->M_user->penandatangan_grup($grup_cfg) as $grup) {
            $items = array();
            foreach ($grup['items'] as $row) {
                $items[] = array(
                    'nip'          => (string) $row->nip,
                    'nama'         => (string) $row->nama,
                    'jabatan'      => isset($row->jabatan) ? (string) $row->jabatan : '',
                    'kode_jabatan' => isset($row->kode_jabatan) ? (string) $row->kode_jabatan : '',
                    'label'        => (string) $row->label,
                );
            }

            $out[] = array(
                'grup'  => (string) $grup['grup'],
                'items' => $items,
            );
        }

        $this->ok($out);
    }
}

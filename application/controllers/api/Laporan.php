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
        '00' => 'Tahunan',
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

        /**
         * Kode klasifikasi DASAR saja (2 huruf), bukan seluruh baris
         * `ref_klasifikasi`.
         *
         * Alasan: `nosur.kode` menyimpan campuran kode dasar ('HK') dan
         * turunan ('HK1.2.5'). Daftar dropdown yang berisi ratusan kode
         * turunan tidak terpakai; yang dipilih pengguna adalah kode induk,
         * dan filternya memakai prefix (LIKE 'HK%').
         *
         * Sumber utama: config `laporan_kode_dasar` (urutan kolom PDF).
         * Bila config kosong, diturunkan dari data `nosur` agar laporan tetap
         * berfungsi pada instalasi yang belum mengisi config.
         */
        $klasifikasi = array();
        $kode_config = $this->config->item('laporan_kode_dasar');

        if (is_array($kode_config) && ! empty($kode_config)) {
            foreach ($kode_config as $kode => $nama) {
                $klasifikasi[] = array(
                    'kode' => (string) $kode,
                    'nama' => (string) $nama,
                );
            }
        } else {
            foreach ($this->M_klasifikasi->daftar_kode() as $k) {
                $kode = strtoupper((string) $k->kode);
                // Hanya kode induk 2 huruf (mis. 'HK', bukan 'HK1.2.5').
                if (! preg_match('/^[A-Z]{2}$/', $kode)) {
                    continue;
                }
                $klasifikasi[] = array(
                    'kode' => $kode,
                    'nama' => isset($k->nama) ? (string) $k->nama : '',
                );
            }
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
                'tgl_cetak'   => date('Y-m-d'),
            ),
        ));
    }

    /**
     * POST api/v1/laporan/cetak
     *
     * Body (semua opsional, default sama dengan controller HTML):
     *   tipelaporan  surkel|surmas
     *   tgl_cetak    YYYY-MM-DD (dari <input type="date">)
     *   bulan        00..12  (00 = Tahunan)
     *   tahun        YYYY
     *   kode         'all' atau kode klasifikasi DASAR (mis. 'HK')
     *
     * ISI PDF
     *   Daftar SEMUA nomor surat pada periode terpilih:
     *     - surat keluar : No. | No Surat | Tanggal | Perihal
     *     - surat masuk  : No. | No Surat | Tanggal | Pengirim | Perihal
     *   Judul mengikuti jenis laporan ("... Surat Keluar" / "... Surat
     *   Masuk"), sub-judul berisi periode, dan blok tanda tangan memakai
     *   nama + NIP dari `pegawai_db.pegawai`.
     *
     *   Sumber baris adalah tabel `nosur` / `surmas`. Filter kode memakai
     *   PREFIX (kode LIKE '<kode>%'), sehingga memilih 'HK' mencakup 'HK',
     *   'HK1', 'HK1.2.5', dan seterusnya. Tidak ada penyaringan status:
     *   seluruh nomor pada periode + kode tersebut ikut tercetak.
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
            : date('Y-m-d');
        $cbulan      = isset($input['bulan']) && $input['bulan'] !== ''
            ? (string) $input['bulan']
            : '00';
        $ctahun      = isset($input['tahun']) && $input['tahun'] !== ''
            ? (string) $input['tahun']
            : date('Y');
        /**
         * Normalisasi kode klasifikasi.
         *
         * PENTING: nilai "all" HARUS tetap 'all' (huruf kecil). Sebelumnya
         * `strtoupper()` mengubahnya menjadi 'ALL', sehingga pengecekan
         * `$kode !== 'all'` dianggap "ada kode dipilih" dan query menambahkan
         * `kode LIKE 'ALL%'` — tidak ada baris yang cocok, laporan jadi
         * kosong meski periode Tahunan sudah dipilih.
         *
         * Aturannya: 'all' (case-insensitive) atau apa pun yang bukan kode
         * dasar 2 huruf diperlakukan sebagai 'all'; selain itu dipakai kode
         * dasar huruf besar (mis. 'HK').
         */
        $kode = isset($input['kode']) ? strtoupper(trim((string) $input['kode'])) : 'ALL';
        if ($kode === '' || $kode === 'ALL' || ! preg_match('/^[A-Z]{2}$/', $kode)) {
            $kode = 'all';
        }

        $is_surmas = ($tipelaporan === 'surmas');
        $jenis     = $is_surmas ? 'surmas' : 'surkel';
        $tabel     = $is_surmas ? 'surmas' : 'nosur';
        $col_tgl   = $is_surmas ? 'tgl_diterima' : 'tanggal';

        $rows = $this->ambil_baris($jenis, $cbulan, $ctahun, $kode);

        // Judul mengikuti jenis laporan yang dipilih.
        $judul = 'Laporan Rekapitulasi Surat ' . ($is_surmas ? 'Masuk' : 'Keluar');

        /**
         * Nama periode untuk sub-judul, judul tabel rekap, dan nama berkas:
         *   bulan '00' → "Tahunan"
         *   bulan lain → "Oktober"
         */
        $namabulan = ($cbulan === '00')
            ? 'Tahunan'
            : (isset($this->bulan[$cbulan]) ? $this->bulan[$cbulan] : '');

        /**
         * Sub-judul periode:
         *   bulan '00' → "Periode: Tahun 2026"
         *   bulan lain → "Periode: Oktober 2026"
         */
        $periode = ($cbulan === '00')
            ? 'Tahun ' . $ctahun
            : trim($namabulan . ' ' . $ctahun);

        /**
         * Rekap kode klasifikasi hanya dicetak bila pengguna memilih
         * "Semua Kode" ('all'). Daftar kode = 13 kode dasar pada config
         * `laporan_kode_dasar`, sehingga kolomnya selalu lengkap dan urutannya
         * tetap, terlepas dari kode apa yang muncul di data.
         */
        $kode_dasar = $this->config->item('laporan_kode_dasar');
        if (! is_array($kode_dasar) || empty($kode_dasar)) {
            $kode_dasar = array();
            foreach ($this->M_klasifikasi->daftar_kode() as $k) {
                $kode_upper = strtoupper((string) $k->kode);
                if (preg_match('/^[A-Z]{2}$/', $kode_upper)) {
                    $kode_dasar[$kode_upper] = isset($k->nama) ? (string) $k->nama : '';
                }
            }
        }

        $rekap = array();
        if (strcasecmp($kode, 'all') === 0) {
            foreach ($kode_dasar as $kd => $nama) {
                $rekap[$kd] = $this->hitung_kode($tabel, $col_tgl, $kd, $cbulan, $ctahun);
            }
        }

        /**
         * Label sub-kolom rekap: singkatan bulan (3 huruf), atau "Tahun"
         * bila periodenya Tahunan.
         *
         * `is_tahunan` dipakai view untuk memilih bentuk tabel rekap:
         * Tahunan → satu angka per kode (tanpa sub-kolom "Tahun / s.d.
         * Tahun" karena keduanya selalu sama); bulanan → dua sub-kolom.
         */
        $is_tahunan  = ($cbulan === '00');
        $label_bulan = $is_tahunan ? 'Tahun' : substr($namabulan, 0, 3);

        $html = $this->load->view('laporan/pdf', array(
            'judul'       => $judul,
            'periode'     => 'Periode: ' . trim($periode),
            'jenis'       => $jenis,
            'rows'        => $rows,
            'kode'        => $kode,
            'namabulan'   => $namabulan,
            'tahun'       => $ctahun,
            'kode_dasar'  => $kode_dasar,
            'rekap'       => $rekap,
            'label_bulan' => $label_bulan,
            'is_tahunan'  => $is_tahunan,
            'ttd'         => $this->susun_ttd($tgl_cetak),
        ), TRUE);

        /**
         * Nama berkas memuat jenis laporan, periode, dan tahun terpilih,
         * mis. `laporan_surkel_oktober_2026.pdf` atau
         * `laporan_surmas_tahunan_2026.pdf`.
         */
        $slug_periode = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $namabulan), '_'));
        if ($slug_periode === '') {
            $slug_periode = 'tahunan';
        }

        $filename = 'laporan_' . $jenis . '_' . $slug_periode . '_' . $ctahun . '.pdf';

        $this->load->library('pdf');
        $this->pdf->write_html($html);

        // Mode 'S' mengembalikan biner PDF sebagai string (tanpa header mPDF).
        $binary = $this->pdf->output($filename, 'S');

        if (! is_string($binary) || strlen($binary) < 100) {
            $this->fail('PDF_GAGAL', 'Dokumen PDF gagal dibuat.', 500);
            return;
        }

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

    /**
     * Hitung dua angka rekap untuk satu kode klasifikasi dasar.
     *
     *   tah = jumlah surat pada bulan terpilih (bulan '00' = seluruh tahun)
     *   sd  = jumlah KUMULATIF sampai bulan terpilih (bila bulan '00',
     *         nilainya sama dengan `tah` karena seluruh tahun sudah dihitung)
     *
     * Pencocokan memakai PREFIX pada `kode` (LIKE 'HK%'), sehingga kode
     * turunan seperti 'HK1.2.5' ikut terhitung pada kolom 'HK'.
     *
     * @param  string $tabel   nosur|surmas
     * @param  string $col_tgl tanggal|tgl_diterima
     * @param  string $kode    kode dasar 2 huruf
     * @param  string $bulan   00..12
     * @param  string $tahun   YYYY
     * @return array{tah:int,sd:int}
     */
    protected function hitung_kode($tabel, $col_tgl, $kode, $bulan, $tahun)
    {
        $dasar = function () use ($tabel, $col_tgl, $kode, $tahun) {
            $this->db->from($tabel);
            $this->db->where("YEAR($col_tgl)", (int) $tahun);
            // Prefix: 'HK' mencakup 'HK', 'HK1', 'HK1.2.5', …
            $this->db->like('kode', $kode, 'after');
        };

        // `tah` — bulan terpilih, atau seluruh tahun bila '00'.
        $dasar();
        if ($bulan !== '00') {
            $this->db->where("MONTH($col_tgl)", (int) $bulan);
        }
        $tah = (int) $this->db->count_all_results();

        // `sd` — kumulatif Januari s.d. bulan terpilih.
        if ($bulan === '00') {
            return array('tah' => $tah, 'sd' => $tah);
        }

        $dasar();
        $this->db->where("MONTH($col_tgl) <=", (int) $bulan);
        $sd = (int) $this->db->count_all_results();

        return array('tah' => $tah, 'sd' => $sd);
    }

    /**
     * Ambil SEMUA nomor surat pada periode + kode terpilih untuk tabel PDF.
     *
     * Aturan filter (sama untuk kedua tabel):
     *   1. tahun  : YEAR(tanggal) / YEAR(tgl_diterima) = tahun terpilih;
     *   2. periode: MONTH(tanggal) / MONTH(tgl_diterima) = bulan terpilih
     *               (dilewati bila bulan '00' = Tahunan, sehingga seluruh
     *               tahun ikut tercetak);
     *   3. kode   : `kode LIKE '<kode>%'` — hanya bila pengguna memilih satu
     *               kode klasifikasi dasar (bukan 'all'). Prefix membuat
     *               'HK' mencakup 'HK', 'HK1', 'HK1.2.5', dan seterusnya.
     *
     * Tidak ada penyaringan status/perihal: seluruh baris `nosur`/`surmas`
     * yang lolos tiga filter di atas ikut tercetak, termasuk nomor yang
     * belum dipakai ('Belum Diambil') pada `nosur`.
     *
     * SURAT KELUAR (`nosur`)
     *   Nomor lengkap sudah tersimpan pada kolom `no` (mis.
     *   "1/KPA.W23-A1/KU1.2/I/2026"), sehingga tidak perlu disusun ulang dari
     *   `no_urut`/`huruf`.
     *
     * SURAT MASUK (`surmas`)
     *   Memakai `no_surat` (nomor dari pengirim) dan `pengirim`, dengan
     *   tanggal `tgl_diterima`. `no_agenda` ikut dibawa agar view dapat
     *   mencetaknya kecil di bawah nomor surat.
     *
     * Urutan: tanggal naik, lalu nomor urut (surat keluar) atau id (surat
     * masuk) agar hasil cetak stabil dan mengikuti urutan agenda.
     *
     * @param  string $jenis surkel|surmas
     * @param  string $bulan 00..12
     * @param  string $tahun YYYY
     * @param  string $kode  'all' atau kode klasifikasi dasar
     * @return array<int,array{no_surat:string,no_agenda:string,pengirim:string,perihal:string,tanggal:string}>
     */
    protected function ambil_baris($jenis, $bulan, $tahun, $kode)
    {
        $rows = array();

        if ($jenis === 'surmas') {
            // `no_agenda` ikut diambil: dicetak kecil di bawah nomor surat.
            $this->db->select('no_surat, no_agenda, pengirim, perihal, tgl_diterima');
            $this->db->from('surmas');
            $this->db->where('YEAR(tgl_diterima)', (int) $tahun);

            if ($bulan !== '00') {
                $this->db->where('MONTH(tgl_diterima)', (int) $bulan);
            }

            // Perbandingan case-insensitive: 'all'/'ALL' sama-sama berarti
            // "tanpa filter kode". Tanpa ini, filter LIKE 'ALL%' mengosongkan
            // hasil laporan.
            if (strcasecmp((string) $kode, 'all') !== 0) {
                // kode LIKE 'HK%'
                $this->db->like('kode', $kode, 'after');
            }

            $this->db->order_by('tgl_diterima', 'ASC');
            $this->db->order_by('id', 'ASC');

            foreach ($this->db->get()->result() as $r) {
                $rows[] = array(
                    'no_surat'  => (string) $r->no_surat,
                    'no_agenda' => (string) $r->no_agenda,
                    'pengirim'  => (string) $r->pengirim,
                    'perihal'   => (string) $r->perihal,
                    'tanggal'   => $this->format_tanggal($r->tgl_diterima),
                );
            }

            return $rows;
        }

        $this->db->select('no, hal, tanggal');
        $this->db->from('nosur');
        $this->db->where('YEAR(tanggal)', (int) $tahun);

        if ($bulan !== '00') {
            $this->db->where('MONTH(tanggal)', (int) $bulan);
        }

        // Case-insensitive, lihat catatan pada cabang `surmas` di atas.
        if (strcasecmp((string) $kode, 'all') !== 0) {
            // kode LIKE 'HK%' — mencakup 'HK', 'HK1', 'HK1.2.5', …
            $this->db->like('kode', $kode, 'after');
        }

        $this->db->order_by('tanggal', 'ASC');
        $this->db->order_by('no_urut', 'ASC');
        // '' (tanpa huruf) → 'A' → 'B' → … → 'Z' → 'AA' → 'AB'
        $this->db->order_by("CHAR_LENGTH(COALESCE(huruf, ''))", 'ASC', FALSE);
        $this->db->order_by('huruf', 'ASC');

        foreach ($this->db->get()->result() as $r) {
            $rows[] = array(
                'no_surat'  => (string) $r->no,
                'no_agenda' => '',
                'pengirim'  => '',
                'perihal'   => (string) $r->hal,
                'tanggal'   => $this->format_tanggal($r->tanggal),
            );
        }

        return $rows;
    }

    /**
     * Tanggal untuk tabel PDF: dd-mm-yyyy (mis. 02-01-2026).
     *
     * @param  string|null $tanggal Y-m-d
     * @return string
     */
    protected function format_tanggal($tanggal)
    {
        if (empty($tanggal)) {
            return '';
        }

        $ts = strtotime((string) $tanggal);

        return ($ts === FALSE) ? (string) $tanggal : date('d-m-Y', $ts);
    }

    /**
     * Susun blok tanda tangan PDF.
     *
     * Tata letak mengikuti naskah dinas (tiga kolom 35% / 30% / 35%):
     *   baris 1 : "Mengetahui,"        | "Kupang, <tanggal cetak>"
     *   baris 2 : "Sekretaris"         | "Kepala Sub Bagian Umum dan Keuangan"
     *   baris 3 : ruang tanda tangan (60px)
     *   baris 4 : nama (tebal)         | nama (tebal)
     *   baris 5 : "NIP. ..."           | "NIP. ..."
     *
     * Sumber: config `laporan_ttd` (jabatan + NIP) dan `laporan_kota`. Nama
     * pejabat diambil dari `pegawai_db.pegawai` agar selalu mengikuti data
     * pegawai terpusat; bila NIP tidak ditemukan, dicetak garis titik-titik
     * sehingga laporan tidak pernah gagal karena data pegawai berubah.
     *
     * @param  string $tgl_cetak Y-m-d
     * @return array
     */
    protected function susun_ttd($tgl_cetak)
    {
        $cfg  = $this->config->item('laporan_ttd');
        $kota = (string) $this->config->item('laporan_kota');

        if (! is_array($cfg)) {
            $cfg = array();
        }

        $ts      = strtotime($tgl_cetak);
        $tanggal = ($ts === FALSE) ? (string) $tgl_cetak : tanggal_indonesia(date('Y-m-d', $ts));

        $kota_tanggal = trim($kota . ($tanggal !== '' ? ', ' . $tanggal : ''));

        // Kolom kiri: baris "Mengetahui," dipisahkan ke baris pertama,
        // sisanya menjadi nama jabatan ("Sekretaris").
        $kiri_baris = isset($cfg['kiri']['jabatan']) ? (array) $cfg['kiri']['jabatan'] : array();
        $mengetahui = 'Mengetahui,';

        if (! empty($kiri_baris) && stripos(trim((string) $kiri_baris[0]), 'mengetahui') === 0) {
            $mengetahui = trim((string) array_shift($kiri_baris));
        }

        $jabatan_kiri = trim(implode(' ', array_map('trim', $kiri_baris)));
        if ($jabatan_kiri === '') {
            $jabatan_kiri = 'Sekretaris';
        }

        // Kolom kanan: seluruh baris jabatan digabung menjadi satu kalimat.
        $kanan_baris   = isset($cfg['kanan']['jabatan']) ? (array) $cfg['kanan']['jabatan'] : array();
        $jabatan_kanan = trim(implode(' ', array_map('trim', $kanan_baris)));
        if ($jabatan_kanan === '') {
            $jabatan_kanan = 'Kepala Sub Bagian Umum dan Keuangan';
        }

        $nip_kiri  = isset($cfg['kiri']['nip']) ? trim((string) $cfg['kiri']['nip']) : '';
        $nip_kanan = isset($cfg['kanan']['nip']) ? trim((string) $cfg['kanan']['nip']) : '';

        return array(
            'mengetahui'    => $mengetahui,
            'jabatan_kiri'  => $jabatan_kiri,
            'kota_tanggal'  => $kota_tanggal,
            'jabatan_kanan' => $jabatan_kanan,
            'nama_kiri'     => $this->nama_pegawai($nip_kiri),
            'nama_kanan'    => $this->nama_pegawai($nip_kanan),
            'nip_kiri'      => $nip_kiri !== '' ? 'NIP. ' . $nip_kiri : '',
            'nip_kanan'     => $nip_kanan !== '' ? 'NIP. ' . $nip_kanan : '',
        );
    }

    /**
     * Nama pegawai berdasarkan NIP (tabel `pegawai_db.pegawai`).
     *
     * @param  string $nip
     * @return string Garis titik-titik bila NIP kosong / tidak ditemukan.
     */
    protected function nama_pegawai($nip)
    {
        if ($nip === '') {
            return '...................................';
        }

        $row = $this->db
            ->select('nama')
            ->where('nip', $nip)
            ->get('pegawai_db.pegawai')
            ->row();

        $nama = ($row && trim((string) $row->nama) !== '') ? trim((string) $row->nama) : '';

        return $nama !== '' ? $nama : '...................................';
    }
}

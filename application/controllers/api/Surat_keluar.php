<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Api_Controller.php';

/**
 * API Surat Keluar - baca daftar milik sendiri, seluruh daftar, detail,
 * lalu tulis (Fase 2).
 *
 * Endpoint baca:
 *   GET api/v1/surat-keluar          daftar nomor milik user login (paginasi)
 *   GET api/v1/surat-keluar/semua    daftar seluruh nomor (paginasi)
 *   GET api/v1/surat-keluar/ID       detail satu nomor
 *
 * Endpoint tulis:
 *   POST api/v1/surat-keluar/ambil        ambil nomor baru (hari ini)
 *   POST api/v1/surat-keluar/sisip        sisip nomor (tanggal mundur)
 *   PUT  api/v1/surat-keluar/ID           ubah nomor surat
 *   POST api/v1/surat-keluar/ID/berkas    unggah berkas PDF
 *
 * PENOMORAN - ATURAN PALING PENTING
 *
 * Nomor urut bersifat GLOBAL PER TAHUN (bukan per tanggal). Deret berjalan
 * terus melewati pergantian tanggal; nomor yang sama tidak boleh muncul dua
 * kali meski tanggalnya berbeda. Bila nomor urut sudah terpakai, dipakai
 * huruf berikutnya (1500 → 1500A → 1500B, dst).
 *
 * Urutan pemanggilan:
 *
 *   M_nosur::last_no_urut($tahun)                          titik awal deret
 *   M_nosur::huruf_terpakai($tanggal)                      audit huruf
 *   M_nosur::nomor_terpakai($no_urut, $huruf, $tahun)      (di dalam loop)
 *   M_nosur::insert_batch_nomor($rows)
 *
 * CATATAN PERUBAHAN: sebelumnya titik awal memakai `last_no_urut_tanggal()`
 * dan pengecekan bentrok memakai `nomor_terpakai($tanggal, ...)`. Keduanya
 * memfilter tanggal, sehingga sisip nomor bertanggal mundur dapat memakai
 * nomor urut yang sudah dipakai di tanggal lain — menghasilkan nomor ganda.
 *
 * Pembantu murni (huruf_sisip, format_no_surat) tetap dari
 * application/helpers/sinos_helper.php.
 *
 * CATATAN METODE: multipart PUT tidak dapat dibaca PHP ($_FILES kosong),
 * sehingga `PUT /surat-keluar/{id}` boleh dikirim sebagai POST dengan
 * `_method=PUT` atau header `X-HTTP-Method-Override: PUT`.
 */
class Surat_keluar extends MY_Api_Controller
{
    /** Batas per_page agar satu request tidak menarik seluruh tabel. */
    const MAX_PER_PAGE = 100;

    /** per_page default (sama dengan rencana Fase 1). */
    const DEFAULT_PER_PAGE = 25;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper(array('api_present', 'api_validation', 'api_berkas'));
        $this->load->model(array('M_nosur', 'M_user'));
    }

    /**
     * GET surat-keluar - nomor surat milik user yang login.
     */
    public function index()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET.', 405);
            return;
        }

        $nip = (string) $this->user['sub'];

        $this->kirim_daftar($nip, 'Nomor surat milik Anda.');
    }

    /**
     * GET surat-keluar/semua - seluruh nomor surat.
     *
     * Rute ini WAJIB berada sebelum rute numerik surat-keluar pada routes.php.
     */
    public function semua()
    {
        if ($this->input->method(TRUE) !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET.', 405);
            return;
        }

        $this->kirim_daftar(NULL, 'Seluruh nomor surat.');
    }

    /**
     * GET    surat-keluar/ID   detail satu nomor surat
     * PUT    surat-keluar/ID   simpan perubahan
     *
     * @param int $id
     */
    public function show($id = 0)
    {
        $method = $this->method_efektif();

        if ($method === 'PUT' || $method === 'PATCH') {
            $this->update($id);
            return;
        }

        if ($method !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET atau PUT.', 405);
            return;
        }

        $row = $this->ambil_nomor($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Nomor surat tidak ditemukan.', 404);
            return;
        }

        $this->ok($this->payload_nomor($row));
    }

    /**
     * POST api/v1/surat-keluar/ambil
     *
     * Meniru Surat_keluar::ambil() + ambil_simpan():
     *   - peringatan "Mohon upload softcopy…" untuk non-admin bila masih ada
     *     berkas berstatus '1' pada tahun berjalan (controller HTML
     *     memblokir navigasi dengan pesan yang sama),
     *   - penomoran melanjutkan nomor terakhir hari ini,
     *   - huruf A/B/C… dipakai bila nomor urut sudah terpakai.
     */
    public function ambil()
    {
        if ($this->method_efektif() !== 'POST') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode POST.', 405);
            return;
        }

        $nip     = (string) $this->user['sub'];
        $is_admin = ! empty($this->user['admin']);
        $tahun   = (int) date('Y');

        // Penjaga yang sama dengan Surat_keluar::ambil().
        $belum = $this->M_nosur->count_belum_upload($nip, $tahun);
        if ($belum > 0 && ! $is_admin) {
            $this->fail(
                'BERKAS_BELUM_LENGKAP',
                'Mohon upload softcopy nomor surat sebelumnya!',
                409,
                array('file' => 'Masih ada ' . $belum . ' nomor surat tanpa berkas.')
            );
            return;
        }

        $input  = $this->input_json();
        $fields = api_apply_rules(api_rules_surat_keluar_ambil(), $input);
        if (! empty($fields)) {
            $this->fail('VALIDATION_FAILED', api_first_error($fields), 422, $fields);
            return;
        }

        $penandatangan = $this->ambil_penandatangan(trim((string) $input['penandatangan_nip']));
        if (! $penandatangan) {
            $this->fail(
                'PENANDATANGAN_TIDAK_VALID',
                'Penandatangan tidak ditemukan atau tidak aktif.',
                422,
                array('penandatangan_nip' => 'Penandatangan tidak ditemukan atau tidak aktif.')
            );
            return;
        }

        $tanggal = date('Y-m-d');
        $tahun   = (int) date('Y', strtotime($tanggal));

        $rows = $this->susun_nomor(
            $input,
            $penandatangan,
            $tanggal,
            FALSE
        );

        $this->M_nosur->insert_batch_nomor($rows);

        $daftar_no = array();
        foreach ($rows as $r) {
            $daftar_no[] = $r['no'];
        }

        $pesan = count($daftar_no) === 1
            ? 'Nomor surat dibuat: ' . $daftar_no[0]
            : count($daftar_no) . ' nomor surat dibuat: ' . implode(', ', $daftar_no);

        $this->created(array(
            'nomor'  => $daftar_no,
            'rows'   => $rows,
            'pesan'  => $pesan,
            'perihal' => (string) $input['hal'],
        ), array(
            'tahun'   => $tahun,
            'tanggal' => $tanggal,
            'mode'    => 'ambil',
        ));
    }

    /**
     * POST api/v1/surat-keluar/sisip
     *
     * Meniru Surat_keluar::sisip_simpan():
     *   - tanggal wajib tanggal mundur,
     *   - nomor melanjutkan nomor terakhir pada tanggal tersebut; bila belum
     *     ada, melanjutkan `last_no_urut_sebelum()`,
     *   - huruf berikutnya dipakai bila kombinasi tanggal+no_urut+huruf
     *     sudah terpakai.
     *
     * Meta respons memuat `last_no_urut_sebelum`, `huruf_terpakai`, dan
     * `nomor_terpakai` agar perilaku penomoran dapat diaudit tanpa membaca DB.
     */
    public function sisip()
    {
        if ($this->method_efektif() !== 'POST') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode POST.', 405);
            return;
        }

        $input  = $this->input_json();
        $fields = api_apply_rules(api_rules_surat_keluar_sisip(), $input);
        if (! empty($fields)) {
            $this->fail('VALIDATION_FAILED', api_first_error($fields), 422, $fields);
            return;
        }

        $tanggal = trim((string) $input['tanggal']);
        $ts      = strtotime($tanggal);

        if ($ts === FALSE) {
            $fields = array('tanggal' => 'Tanggal surat wajib diisi dengan format yang benar.');
            $this->fail('VALIDATION_FAILED', api_first_error($fields), 422, $fields);
            return;
        }

        // Aturan yang tidak dapat dinyatakan sebagai rule CI3.
        if ($ts > strtotime(date('Y-m-d'))) {
            $pesan = 'Sisip nomor hanya untuk tanggal mundur (tidak boleh melebihi hari ini).';
            $this->fail('VALIDATION_FAILED', $pesan, 422, array('tanggal' => $pesan));
            return;
        }

        $penandatangan = $this->ambil_penandatangan(trim((string) $input['penandatangan_nip']));
        if (! $penandatangan) {
            $this->fail(
                'PENANDATANGAN_TIDAK_VALID',
                'Penandatangan tidak ditemukan atau tidak aktif.',
                422,
                array('penandatangan_nip' => 'Penandatangan tidak ditemukan atau tidak aktif.')
            );
            return;
        }

        $diagnostik = array();
        $rows = $this->susun_nomor($input, $penandatangan, $tanggal, TRUE, $diagnostik);

        $this->M_nosur->insert_batch_nomor($rows);

        $daftar_no = array();
        foreach ($rows as $r) {
            $daftar_no[] = $r['no'];
        }

        $pesan = count($daftar_no) === 1
            ? 'Nomor surat disisipkan: ' . $daftar_no[0]
            : count($daftar_no) . ' nomor surat disisipkan: ' . implode(', ', $daftar_no);

        $this->created(array(
            'nomor'  => $daftar_no,
            'rows'   => $rows,
            'pesan'  => $pesan,
        ), array(
            'tahun'               => (int) date('Y', $ts),
            'tanggal'             => $tanggal,
            'mode'                => 'sisip',
            'last_no_urut_sebelum' => $diagnostik['last_no_urut_sebelum'],
            'huruf_terpakai'      => $diagnostik['huruf_terpakai'],
            'nomor_terpakai'      => $diagnostik['nomor_terpakai'],
        ));
    }

    /**
     * PUT api/v1/surat-keluar/ID
     *
     * Meniru Surat_keluar::update(): no_urut dan huruf TIDAK berubah; nomor
     * lengkap disusun ulang dari penandatangan + kode yang baru.
     *
     * @param int $id
     */
    protected function update($id)
    {
        $row = $this->ambil_nomor($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Nomor surat tidak ditemukan.', 404);
            return;
        }

        // Surat_keluar::update() tidak memasang penjaga peran; endpoint detail
        // mengiklankan can_edit = admin/pemilik. API menegakkan nilai itu.
        if (! $this->boleh_ubah($row)) {
            $this->fail('FORBIDDEN', 'Anda tidak berhak mengubah nomor surat ini.', 403);
            return;
        }

        $input  = $this->input_json();
        $fields = api_apply_rules(api_rules_surat_keluar_update(), $input);
        if (! empty($fields)) {
            $this->fail('VALIDATION_FAILED', api_first_error($fields), 422, $fields);
            return;
        }

        $tanggal = trim((string) $input['tanggal']);
        $ts      = strtotime($tanggal);
        if ($ts === FALSE) {
            $fields = array('tanggal' => 'Tanggal surat wajib diisi dengan format yang benar.');
            $this->fail('VALIDATION_FAILED', api_first_error($fields), 422, $fields);
            return;
        }

        $penandatangan = $this->ambil_penandatangan(trim((string) $input['penandatangan_nip']));
        if (! $penandatangan) {
            $this->fail(
                'PENANDATANGAN_TIDAK_VALID',
                'Penandatangan tidak ditemukan atau tidak aktif.',
                422,
                array('penandatangan_nip' => 'Penandatangan tidak ditemukan atau tidak aktif.')
            );
            return;
        }

        $no_urut = (int) $row->no_urut;
        $huruf   = (string) $row->huruf;
        $ks      = trim((string) $input['ks']);
        $kode_penetapan = isset($input['kode_penetapan']) ? trim((string) $input['kode_penetapan']) : '';

        $no = format_no_surat(
            $no_urut,
            $huruf,
            $penandatangan['kode'],
            $this->config->item('instansi_kode') ?: 'W23-A1',
            $kode_penetapan,
            $ks,
            getRomawi((int) date('n', $ts)),
            (int) date('Y', $ts)
        );

        $this->M_nosur->update((int) $row->id, array(
            'nip'                => (string) $input['nip'],
            'penandatangan_nip'  => $penandatangan['nip'],
            'penandatangan_nama' => $penandatangan['label'],
            'no'                 => $no,
            'kode'               => $ks,
            'no_urut'            => $no_urut,
            'huruf'              => $huruf,
            'kode_penetapan'     => $kode_penetapan,
            'hal'                => (string) $input['hal'],
            'kj'                 => $penandatangan['kode'],
            'tujuan'             => (string) $input['tujuan'],
            'tanggal'            => $tanggal,
        ));

        $this->ok($this->payload_nomor($this->M_nosur->get((int) $row->id)));
    }

    /**
     * POST api/v1/surat-keluar/ID/berkas
     *
     * Meniru Surat_keluar::upload_simpan():
     *   - direktori `file/` (bukan `file/sk/`),
     *   - tipe pdf, maks 10 MB,
     *   - nama berkas {tahun}_{no_urut}{huruf}_file.pdf,
     *   - kolom `file` diganti path baru; berkas lama tidak dihapus.
     *
     * @param int $id
     */
    public function berkas($id = 0)
    {
        if ($this->method_efektif() !== 'POST') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode POST.', 405);
            return;
        }

        $row = $this->ambil_nomor($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Nomor surat tidak ditemukan.', 404);
            return;
        }

        if (! $this->boleh_ubah($row)) {
            $this->fail('FORBIDDEN', 'Anda tidak berhak mengunggah berkas surat ini.', 403);
            return;
        }

        if (empty($_FILES['file']['name'])) {
            $pesan = 'Berkas surat (PDF) wajib diunggah.';
            $this->fail('VALIDATION_FAILED', $pesan, 422, array('file' => $pesan));
            return;
        }

        $tahun   = date('Y');
        $suffix  = $row->huruf ? (string) $row->huruf : '';
        $new_name = $tahun . '_' . $row->no_urut . $suffix . '_file.pdf';

        $upload = api_upload_pdf('file', 'file', $new_name);
        if (! $upload['ok']) {
            $pesan = $upload['error'] !== '' ? $upload['error'] : 'Gagal upload berkas.';
            $this->fail('UPLOAD_FAILED', $pesan, 422, array('file' => $pesan));
            return;
        }

        $this->M_nosur->update((int) $row->id, array('file' => $upload['path']));

        $this->ok($this->payload_nomor($this->M_nosur->get((int) $row->id)));
    }

    // ------------------------------------------------------------------
    // Penomoran (memakai method model yang sama dengan controller HTML)
    // ------------------------------------------------------------------

    /**
     * Susun baris `nosur` untuk ambil/sisip.
     *
     * Deretan pemanggilan model identik dengan
     * Surat_keluar::ambil_simpan()/sisip_simpan(); hanya pembungkusnya yang
     * berbeda. `jumlah` selalu 1, sama seperti form saat ini.
     *
     * @param  array      $input
     * @param  array      $penandatangan ['nip','nama','kode','label']
     * @param  string     $tanggal       Y-m-d
     * @param  bool       $sisip         TRUE untuk mode sisip (diagnostik)
     * @param  array|null $diagnostik    Keluar: nilai antara yang dipakai
     * @return array baris siap insert_batch_nomor()
     */
    protected function susun_nomor(array $input, array $penandatangan, $tanggal, $sisip, &$diagnostik = NULL)
    {
        $ts    = strtotime($tanggal);
        $tahun = (int) date('Y', $ts);
        $bulan = getRomawi((int) date('n', $ts));
        $kode  = trim((string) $input['ks']);

        /**
         * Nomor urut bersifat GLOBAL per tahun, bukan per tanggal.
         *
         * Karena itu titik awalnya adalah nomor urut tertinggi pada tahun
         * tersebut — bukan nomor tertinggi pada tanggal yang dipilih. Dengan
         * begitu menyisipkan nomor bertanggal mundur tetap melanjutkan deret
         * yang sedang berjalan dan tidak pernah menghasilkan nomor urut yang
         * sudah dipakai di tanggal lain.
         */
        $dasar   = $this->M_nosur->last_no_urut($tahun);
        $sebelum = ($dasar === 0) ? NULL : $dasar;
        $no_urut = $dasar + 1;

        // Huruf yang sudah terpakai untuk nomor urut ini (semua tanggal pada
        // tahun berjalan) — dipakai untuk audit pada respons.
        $terpakai = $this->M_nosur->huruf_terpakai($tanggal);

        $rows      = array();
        $tercatat  = array();
        $jumlah    = 1; // form tidak lagi menampilkan field jumlah

        for ($i = 0; $i < $jumlah; $i++) {
            $index = $i;
            $huruf = huruf_sisip($index);

            // Hindari bentrok nomor urut + huruf dengan SELURUH tanggal pada
            // tahun berjalan (batas 200, sama dengan controller HTML).
            $pengaman = 0;
            while ($this->M_nosur->nomor_terpakai($no_urut, $huruf, $tahun) && $pengaman < 200) {
                $index++;
                $huruf = huruf_sisip($index);
                $pengaman++;
            }

            $tercatat[] = array(
                'no_urut' => $no_urut,
                'huruf'   => $huruf,
            );

            $rows[] = array(
                'no'                 => format_no_surat(
                    $no_urut,
                    $huruf,
                    $penandatangan['kode'],
                    $this->config->item('instansi_kode') ?: 'W23-A1',
                    isset($input['kode_penetapan']) ? trim((string) $input['kode_penetapan']) : '',
                    $kode,
                    $bulan,
                    $tahun
                ),
                'kode'               => $kode,
                'no_urut'            => $no_urut,
                'huruf'              => $huruf,
                'nip'                => (string) $input['nip'],
                'penandatangan_nip'  => $penandatangan['nip'],
                'penandatangan_nama' => $penandatangan['label'],
                'kj'                 => $penandatangan['kode'],
                'kode_penetapan'     => isset($input['kode_penetapan']) ? trim((string) $input['kode_penetapan']) : '',
                'tanggal'            => $tanggal,
                'hal'                => (string) $input['hal'],
                'tujuan'             => (string) $input['tujuan'],
                'jumlah'             => $jumlah,
            );
        }

        if (is_array($diagnostik)) {
            $diagnostik = array(
                'last_no_urut_sebelum' => $sebelum,
                'huruf_terpakai'       => $terpakai,
                'nomor_terpakai'       => $tercatat,
            );
        }

        return $rows;
    }

    /**
     * Data penandatangan dari NIP, query yang sama dengan
     * `Surat_keluar::ambil_penandatangan()`.
     *
     * Hanya kolom yang tercakup GRANT SELECT yang dibaca.
     *
     * @param  string $nip
     * @return array|null ['nip','nama','kode','label']
     */
    protected function ambil_penandatangan($nip)
    {
        if ($nip === '') {
            return NULL;
        }

        $row = $this->db
            ->select('p.nip, p.nama, j.kode AS kode_jabatan, j.jabatan')
            ->from('pegawai_db.pegawai p')
            ->join('pegawai_db.ref_jabatan j', 'p.id_jabatan = j.id', 'left')
            ->where('p.nip', $nip)
            ->where('p.aktif', 1)
            ->get()
            ->row();

        if (! $row) {
            return NULL;
        }

        return array(
            'nip'   => $row->nip,
            'nama'  => $row->nama,
            'kode'  => (string) $row->kode_jabatan,
            'label' => label_penandatangan($row->nama, $row->kode_jabatan),
        );
    }

    // ------------------------------------------------------------------
    // Bantuan
    // ------------------------------------------------------------------

    /**
     * Payload satu nomor surat, dipakai oleh GET, PUT, dan upload berkas.
     *
     * @param  object $row
     * @return array
     */
    protected function payload_nomor($row)
    {
        $nip      = (string) $this->user['sub'];
        $is_admin = ! empty($this->user['admin']);
        $is_owner = ((string) $row->nip === $nip);

        // Nama pemilik nomor diambil lewat method model yang sama dengan
        // daftar_user(): join pegawai_db.pegawai.
        $nama = $this->M_user->get_nama($row->nip);

        return array(
            'id'                 => (int) $row->id,
            'no'                 => (string) $row->no,
            'kode'               => isset($row->kode) ? (string) $row->kode : '',
            'no_urut'            => (int) $row->no_urut,
            'huruf'              => isset($row->huruf) ? (string) $row->huruf : '',
            'nip'                => (string) $row->nip,
            'nama'               => $nama ? (string) $nama : '',
            'kj'                 => isset($row->kj) ? (string) $row->kj : '',
            'kode_penetapan'     => isset($row->kode_penetapan) ? (string) $row->kode_penetapan : '',
            'penandatangan_nip'  => isset($row->penandatangan_nip) ? (string) $row->penandatangan_nip : '',
            'penandatangan_nama' => isset($row->penandatangan_nama) ? (string) $row->penandatangan_nama : '',
            'tanggal'            => api_tanggal(isset($row->tanggal) ? $row->tanggal : NULL),
            'hal'                => (string) $row->hal,
            'tujuan'             => (string) $row->tujuan,
            'jumlah'             => isset($row->jumlah) ? (int) $row->jumlah : NULL,
            'file'               => api_file_meta(isset($row->file) ? $row->file : NULL),
            'aksi'               => array(
                'can_view'   => TRUE,
                'can_edit'   => (bool) ($is_admin || $is_owner),
                'can_upload' => (bool) ($is_admin || $is_owner),
            ),
        );
    }

    /**
     * Ambil satu nomor, atau NULL bila id tidak valid.
     *
     * @param  mixed $id
     * @return object|null
     */
    protected function ambil_nomor($id)
    {
        $id = (int) $id;

        return $id > 0 ? $this->M_nosur->get($id) : NULL;
    }

    /**
     * Apakah user boleh mengubah nomor ini (admin atau pemilik).
     *
     * @param  object $row
     * @return bool
     */
    protected function boleh_ubah($row)
    {
        return ! empty($this->user['admin'])
            || ((string) $row->nip === (string) $this->user['sub']);
    }

    /**
     * Metode HTTP efektif, dengan dukungan override untuk multipart PUT.
     *
     * @return string
     */
    protected function method_efektif()
    {
        $method = strtoupper((string) $this->input->method(TRUE));

        if ($method === 'POST') {
            $override = $this->input->get_request_header('X-HTTP-Method-Override', TRUE);
            if (! is_string($override) || $override === '') {
                $override = isset($_POST['_method']) ? (string) $_POST['_method'] : (string) $this->input->get('_method');
            }

            $override = strtoupper(trim((string) $override));
            if (in_array($override, array('PUT', 'PATCH', 'DELETE'), TRUE)) {
                return $override;
            }
        }

        return $method;
    }

    /**
     * Bangun respons daftar terpaginasi.
     *
     * @param  string|null $nip  NULL = seluruh pegawai
     * @param  string      $note Catatan pada meta
     * @return void
     */
    protected function kirim_daftar($nip, $note)
    {
        $tahun    = (int) $this->input->get('tahun');
        $tahun    = $tahun ? $tahun : (int) date('Y');
        $page     = max(1, (int) $this->input->get('page'));
        $per_page = $this->per_page();
        $q        = trim((string) $this->input->get('q'));

        $total  = (int) $this->M_nosur->api_count($tahun, $nip, $q);
        $offset = ($page - 1) * $per_page;
        $rows   = $this->M_nosur->api_daftar($tahun, $nip, $q, $per_page, $offset);

        $data = array();
        foreach ($rows as $r) {
            $data[] = array(
                'id'         => (int) $r->id,
                'no'         => (string) $r->no,
                'kode'       => isset($r->kode) ? (string) $r->kode : '',
                'nip'        => (string) $r->nip,
                'nama'       => isset($r->nama) ? (string) $r->nama : '',
                'tanggal'    => api_tanggal(isset($r->tanggal) ? $r->tanggal : NULL),
                'hal'        => (string) $r->hal,
                'tujuan'     => (string) $r->tujuan,
                'file'       => api_file_meta(isset($r->file) ? $r->file : NULL),
            );
        }

        $meta             = $this->paginate($page, $per_page, $total);
        $meta['tahun']    = $tahun;
        $meta['lingkup']  = ($nip === NULL) ? 'semua' : 'sendiri';
        $meta['catatan']  = $note;

        $this->ok($data, $meta);
    }

    /**
     * per_page tervalidasi (1..MAX_PER_PAGE).
     *
     * @return int
     */
    protected function per_page()
    {
        $per_page = (int) $this->input->get('per_page');
        $per_page = $per_page > 0 ? $per_page : self::DEFAULT_PER_PAGE;

        return min(self::MAX_PER_PAGE, $per_page);
    }
}

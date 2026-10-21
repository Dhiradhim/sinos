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
 *   POST   api/v1/surat-keluar/ambil      ambil nomor baru (hari ini)
 *   POST   api/v1/surat-keluar/sisip      sisip nomor (tanggal mundur)
 *   PUT    api/v1/surat-keluar/ID         ubah nomor surat
 *   POST   api/v1/surat-keluar/ID/berkas  unggah berkas PDF
 *   DELETE api/v1/surat-keluar/ID         hapus nomor surat (ADMIN)
 *
 *   Hapus menerima POST + `_method=DELETE` (atau header
 *   `X-HTTP-Method-Override`) karena form HTML tidak dapat mengirim DELETE.
 *
 * PENOMORAN - ATURAN PALING PENTING
 *
 * Nomor urut bersifat GLOBAL PER TAHUN (bukan per tanggal), tetapi cara
 * memilih angkanya berbeda antara "ambil" dan "sisip":
 *
 *   AMBIL  (tanggal hari ini, ujung deret)
 *          → nomor urut tertinggi tahun + 1, tanpa huruf.
 *          Contoh: terakhir 1500 → 1501.
 *
 *   SISIP  (tanggal mundur, tengah riwayat)
 *          → nomor urut yang BERLAKU pada tanggal itu (tertinggi pada baris
 *            bertanggal <= tanggal tersebut), lalu huruf bebas pertama.
 *            Contoh: pada 10 Sep berlaku 100 → 100A; bila 100A ada → 100B.
 *
 * Karena itu surat sisipan selalu tampil SETELAH surat aslinya pada tanggal
 * tersebut (100 → 100A → 100B) dan tidak pernah mendahuluinya, persis seperti
 * perilaku legacy (kolom `huruf` diisi 'A', 'B', … selama tanggalnya bukan
 * tanggal terakhir yang tercatat).
 *
 * Seluruh aturan itu kini berada di SATU tempat:
 *
 *   M_nosur::nomor_berikut($tanggal, $tahun)   → ['no_urut','huruf','mode',…]
 *   M_nosur::insert_batch_nomor($rows)
 *
 * Pembantu murni (huruf_sisip, huruf_berikut, format_no_surat) tetap dari
 * application/helpers/sinos_helper.php.
 *
 * CATATAN PERUBAHAN: sebelumnya titik awal selalu `last_no_urut($tahun)`
 * sehingga sisip nomor bertanggal mundur ikut melanjutkan deret tahun dan
 * melompati nomor yang seharusnya berlaku pada tanggal itu. Penentuan nomor
 * sekarang dipusatkan di model agar controller HTML dan API tidak berbeda.
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

        if ($method === 'DELETE') {
            $this->destroy($id);
            return;
        }

        if ($method !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET, PUT, atau DELETE.', 405);
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
     * Alur:
     *   - peringatan "Mohon upload softcopy…" untuk non-admin bila masih ada
     *     berkas berstatus '1' pada tahun berjalan,
     *   - nomor = nomor urut tertinggi tahun + 1, tanpa huruf (mode 'urut'),
     *   - alokasi + penyimpanan dibungkus `dengan_kunci_penomoran()` supaya
     *     dua request bersamaan tidak mendapat nomor yang sama.
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

        $diagnostik = array();

        // Alokasi nomor + simpan harus atomik terhadap request lain.
        $rows = $this->M_nosur->dengan_kunci_penomoran($tahun, function () use ($input, $penandatangan, $tanggal, &$diagnostik) {
            $rows = $this->susun_nomor($input, $penandatangan, $tanggal, 'ambil', $diagnostik);
            $this->M_nosur->insert_batch_nomor($rows);
            return $rows;
        });

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
            'tahun'                => $tahun,
            'tanggal'              => $tanggal,
            'mode'                 => 'ambil',
            'mode_penomoran'       => $diagnostik['mode_penomoran'],
            'tanggal_acuan'        => $diagnostik['tanggal_acuan'],
            'last_no_urut_sebelum' => $diagnostik['last_no_urut_sebelum'],
            'nomor_terpakai'       => $diagnostik['nomor_terpakai'],
        ));
    }

    /**
     * POST api/v1/surat-keluar/sisip
     *
     * Aturan (lihat M_nosur::nomor_berikut()):
     *   - tanggal wajib tanggal mundur (tidak melebihi hari ini),
     *   - bila tanggal itu masih di UJUNG deret (tidak ada nomor bertanggal
     *     setelahnya), deret tahun tetap dilanjutkan: nomor tertinggi + 1;
     *   - bila tanggal itu berada di TENGAH riwayat, dipakai nomor yang
     *     BERLAKU pada tanggal tersebut lalu huruf bebas pertama (A, B, …),
     *     sehingga nomor sisipan tidak pernah mendahului surat aslinya.
     *
     * Meta respons memuat `last_no_urut_sebelum`, `huruf_terpakai`,
     * `nomor_terpakai`, `mode_penomoran`, dan `tanggal_acuan` agar perilaku
     * penomoran dapat diaudit tanpa membaca DB.
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

        // Alokasi nomor + simpan harus atomik terhadap request lain.
        $rows = $this->M_nosur->dengan_kunci_penomoran((int) date('Y', $ts), function () use ($input, $penandatangan, $tanggal, &$diagnostik) {
            $rows = $this->susun_nomor($input, $penandatangan, $tanggal, 'sisip', $diagnostik);
            $this->M_nosur->insert_batch_nomor($rows);
            return $rows;
        });

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
            'tahun'                => (int) date('Y', $ts),
            'tanggal'              => $tanggal,
            'mode'                 => 'sisip',
            'mode_penomoran'       => $diagnostik['mode_penomoran'],
            'tanggal_acuan'        => $diagnostik['tanggal_acuan'],
            'last_no_urut_sebelum' => $diagnostik['last_no_urut_sebelum'],
            'huruf_terpakai'       => $diagnostik['huruf_terpakai'],
            'nomor_terpakai'       => $diagnostik['nomor_terpakai'],
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

    /**
     * DELETE api/v1/surat-keluar/ID — hapus nomor surat (ADMIN saja).
     *
     * Mengapa admin saja: nomor surat adalah bukti administrasi. Pemilik
     * nomor tidak boleh menghapusnya sendiri (ia hanya boleh mengubah data
     * atau mengganti berkas), sedangkan admin perlu menghapus nomor yang
     * salah ambil/sisip.
     *
     * Nomor TIDAK dinomori ulang setelah dihapus: nomor urut yang hilang
     * menjadi celah, dan penomoran berikutnya tetap melanjutkan nomor
     * tertinggi yang ada. Ini disengaja — menggeser nomor lama akan membuat
     * nomor yang sudah tercetak di surat fisik tidak lagi cocok.
     *
     * Berkas PDF di disk TIDAK dihapus, sama seperti
     * `Surat_masuk::destroy()` (controller HTML juga tidak menghapusnya).
     *
     * @param  int $id
     * @return void
     */
    protected function destroy($id)
    {
        if (empty($this->user['admin'])) {
            $this->fail('FORBIDDEN', 'Hanya admin yang dapat menghapus nomor surat.', 403);
            return;
        }

        $row = $this->ambil_nomor($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Nomor surat tidak ditemukan.', 404);
            return;
        }

        /**
         * Hapus di bawah kunci penomoran tahun terkait agar tidak berlomba
         * dengan alokasi nomor yang sedang berjalan pada tahun yang sama.
         */
        $tahun = (int) date('Y', strtotime((string) $row->tanggal));

        $terhapus = $this->M_nosur->dengan_kunci_penomoran($tahun, function () use ($row) {
            return $this->M_nosur->delete((int) $row->id);
        });

        if (! $terhapus) {
            $this->fail('DELETE_FAILED', 'Nomor surat gagal dihapus.', 500);
            return;
        }

        $this->ok(array(
            'id'      => (int) $row->id,
            'no'      => (string) $row->no,
            'dihapus' => TRUE,
        ), array(
            'pesan' => 'Nomor surat ' . $row->no . ' berhasil dihapus.',
        ));
    }

    // ------------------------------------------------------------------
    // Penomoran (satu sumber: M_nosur::nomor_berikut())
    // ------------------------------------------------------------------

    /**
     * Susun baris `nosur` untuk ambil/sisip.
     *
     * Seluruh aturan penomoran berada di `M_nosur::nomor_berikut()` supaya
     * tidak ada dua salinan logika. Method ini hanya:
     *   1. meminta nomor (urut + huruf) ke model,
     *   2. menyusun string nomor lengkap lewat `format_no_surat()`,
     *   3. merangkai baris siap `insert_batch_nomor()`.
     *
     * Ringkasnya aturan yang berlaku (detail di model):
     *   - mode 'ambil' → nomor urut tertinggi tahun + 1, tanpa huruf;
     *   - mode 'sisip' → nomor TERAKHIR pada tanggal <= tanggal sisip,
     *                    lalu huruf berikutnya (Z → AA → AB, …).
     *
     * `jumlah` selalu 1, sama seperti form saat ini.
     *
     * @param  array      $input
     * @param  array      $penandatangan ['nip','nama','kode','label']
     * @param  string     $tanggal       Y-m-d
     * @param  string     $mode          'ambil' atau 'sisip'
     * @param  array|null $diagnostik    Keluar: nilai antara yang dipakai
     * @return array baris siap insert_batch_nomor()
     */
    protected function susun_nomor(array $input, array $penandatangan, $tanggal, $mode, &$diagnostik = NULL)
    {
        $ts    = strtotime($tanggal);
        $tahun = (int) date('Y', $ts);
        $bulan = getRomawi((int) date('n', $ts));
        $kode  = trim((string) $input['ks']);
        $penetapan = isset($input['kode_penetapan']) ? trim((string) $input['kode_penetapan']) : '';

        /**
         * Satu sumber aturan penomoran (lihat M_nosur::nomor_berikut()).
         * `$mode` WAJIB dibedakan:
         *   'ambil' → nomor tertinggi tahun + 1 (tanpa huruf)
         *   'sisip' → nomor terakhir s.d. tanggal itu + huruf berikutnya
         */
        $nomor   = $this->M_nosur->nomor_berikut($tanggal, $tahun, $mode);
        $no_urut = (int) $nomor['no_urut'];
        $huruf   = (string) $nomor['huruf'];

        $jumlah   = 1; // form tidak lagi menampilkan field jumlah
        $tercatat = array(array('no_urut' => $no_urut, 'huruf' => $huruf));

        $rows = array(
            array(
                'no'                 => format_no_surat(
                    $no_urut,
                    $huruf,
                    $penandatangan['kode'],
                    $this->config->item('instansi_kode') ?: 'W23-A1',
                    $penetapan,
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
                'kode_penetapan'     => $penetapan,
                'tanggal'            => $tanggal,
                'hal'                => (string) $input['hal'],
                'tujuan'             => (string) $input['tujuan'],
                'jumlah'             => $jumlah,
            ),
        );

        if (is_array($diagnostik)) {
            /**
             * Bentuk `huruf_terpakai` dipertahankan {no_urut: [huruf, …]}
             * karena `summarizeSisipMeta()` di frontend membacanya demikian.
             * `mode`/`acuan`/`dasar` adalah tambahan audit: 'urut' berarti
             * deret dilanjutkan, 'sisip' berarti nomor dipinjam + huruf.
             */
            $diagnostik = array(
                'last_no_urut_sebelum' => $nomor['dasar'],
                'huruf_terpakai'       => array((string) $no_urut => $nomor['terpakai']),
                'nomor_terpakai'       => $tercatat,
                'mode_penomoran'       => $nomor['mode'],
                'tanggal_acuan'        => $nomor['acuan'],
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
                // Hapus nomor surat HANYA admin (lihat destroy()).
                'can_delete' => (bool) $is_admin,
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

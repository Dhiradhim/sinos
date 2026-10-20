<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Api_Controller.php';

/**
 * API Surat Masuk - baca daftar + detail, lalu tulis (Fase 2).
 *
 *   GET    api/v1/surat-masuk?tahun=&page=&per_page=&q=&arsip=&disposisi=
 *   GET    api/v1/surat-masuk/<id>
 *   POST   api/v1/surat-masuk                 (multipart: field + berkas)
 *   PUT    api/v1/surat-masuk/<id>            (berkas opsional)
 *   DELETE api/v1/surat-masuk/<id>
 *   POST   api/v1/surat-masuk/<id>/arsip
 *   POST   api/v1/surat-masuk/<id>/batal-arsip
 *   GET    api/v1/surat-masuk/<id>/disposisi  (payload lembar disposisi)
 *
 * Daftar memakai M_surmas::api_daftar() dan M_surmas::api_count() yang
 * bentuk query-nya sama dengan daftar_tahun() (WHERE YEAR(tgl_surat), ORDER BY
 * tgl_surat DESC), dengan LIMIT/OFFSET di server dan filter q/arsip/disposisi.
 *
 * Endpoint tulis meniru Surat_masuk::simpan() / update() / hapus() /
 * arsipkan() / batal_arsip() / disposisi():
 *   - field wajib sama (lihat api_validation_helper.php),
 *   - direktori upload sama (file/sm), tipe pdf, maks 10 MB,
 *   - pola nama berkas sama: {Y}_{kode}_{perihal30}.pdf,
 *   - no_agenda dibuat server lewat M_surmas::next_no_agenda(),
 *   - nomor agenda lama tidak berubah saat update,
 *   - kolom `file` hanya berubah bila ada berkas baru,
 *   - pesan kesalahan sama kata per kata.
 *
 * CATATAN METODE: PHP tidak mengisi $_FILES pada request PUT, sehingga
 * multipart PUT tidak dapat dibaca. Klien boleh mengirim POST dengan
 * `_method=PUT` (body atau query) atau header `X-HTTP-Method-Override: PUT`;
 * keduanya diperlakukan sebagai PUT. `_method` tidak pernah disimpan.
 */
class Surat_masuk extends MY_Api_Controller
{
    /** Batas per_page agar satu request tidak menarik seluruh tabel. */
    const MAX_PER_PAGE = 100;

    /** per_page default (sama dengan rencana Fase 1). */
    const DEFAULT_PER_PAGE = 25;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper(array('api_present', 'api_validation', 'api_berkas'));
        // M_user dipakai lembar disposisi cetak untuk mengambil nama Ketua
        // dari data pegawai (blok tanda tangan).
        $this->load->model(array('M_surmas', 'M_disposisi', 'M_user'));
    }

    /**
     * GET  api/v1/surat-masuk   daftar terpaginasi
     * POST api/v1/surat-masuk   simpan surat masuk baru
     */
    public function index()
    {
        $method = $this->method_efektif();

        if ($method === 'POST') {
            $this->store();
            return;
        }

        if ($method !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET atau POST.', 405);
            return;
        }

        $tahun     = (int) $this->input->get('tahun');
        $tahun     = $tahun ? $tahun : (int) date('Y');
        $page      = max(1, (int) $this->input->get('page'));
        $per_page  = $this->per_page();
        $q         = trim((string) $this->input->get('q'));
        $arsip     = $this->filter_choice($this->input->get('arsip'), array('diarsipkan', 'aktif'));
        $disposisi = $this->filter_choice($this->input->get('disposisi'), array('sudah', 'belum'));

        $total  = (int) $this->M_surmas->api_count($tahun, $q, $arsip, $disposisi);
        $offset = ($page - 1) * $per_page;
        $rows   = $this->M_surmas->api_daftar($tahun, $q, $arsip, $disposisi, $per_page, $offset);

        // Status disposisi seluruh baris halaman ini dalam satu query.
        $ids = array();
        foreach ($rows as $r) {
            $ids[] = (int) $r->id;
        }
        $status_map = $this->M_disposisi->status_by_surmas($ids);

        $nip         = (string) $this->user['sub'];
        $is_operator = ! empty($this->user['operator']);

        $data = array();
        foreach ($rows as $r) {
            $sid         = (int) $r->id;
            $diarsipkan  = (isset($r->status) && $r->status === 'diarsipkan');
            $status_dis  = isset($status_map[$sid]) ? $status_map[$sid] : NULL;

            /**
             * Hak mengirim/meneruskan disposisi.
             *
             * Aturannya SAMA dengan detail & daftar disposisi: yang berhak
             * adalah PEMILIK POSISI SAAT INI.
             *
             * - Belum pernah didisposisi       -> operator (disposisi awal).
             * - Disposisi terakhir DIKEMBALIKAN -> operator (surat kembali ke
             *   meja operator dan siap diarsipkan / didisposisikan ulang).
             * - Disposisi terakhir ke pegawai  -> hanya `nip_tujuan` terakhir.
             * - Tujuan "Arsip" (nip kosong)    -> tidak ada pemilik.
             */
            $nip_pemilik = ($status_dis && ! empty($status_dis->nip_tujuan))
                ? (string) $status_dis->nip_tujuan
                : '';

            $dikembalikan = ($status_dis && ! empty($status_dis->dikembalikan));
            $belum        = ($status_dis === NULL);

            // Posisi di meja operator: belum didisposisi atau sudah dikembalikan.
            $di_operator = ($belum || $dikembalikan);

            $is_pemilik = ($nip_pemilik !== '' && $nip_pemilik === $nip);

            $can_send = ! $diarsipkan && (
                ($di_operator && $is_operator) || $is_pemilik
            );

            $data[] = array(
                'id'          => $sid,
                'kode'        => isset($r->kode) ? (string) $r->kode : '',
                'no_surat'    => (string) $r->no_surat,
                'no_agenda'   => (string) $r->no_agenda,
                'tgl_surat'   => api_tanggal(isset($r->tgl_surat) ? $r->tgl_surat : NULL),
                'tgl_diterima' => api_tanggal(isset($r->tgl_diterima) ? $r->tgl_diterima : NULL),
                'pengirim'    => (string) $r->pengirim,
                'perihal'     => (string) $r->perihal,
                'pengolah'    => isset($r->pengolah) ? (string) $r->pengolah : '',
                'status'      => isset($r->status) ? (string) $r->status : '',
                'diarsipkan'  => $diarsipkan,
                'file'        => api_file_meta(isset($r->file) ? $r->file : NULL),
                'disposisi'   => api_status_disposisi_surat($status_dis),
                'can_send'    => (bool) $can_send,
            );
        }

        $this->ok($data, $this->paginate($page, $per_page, $total));
    }

    /**
     * GET    api/v1/surat-masuk/<id>  detail
     * PUT    api/v1/surat-masuk/<id>  simpan perubahan
     * DELETE api/v1/surat-masuk/<id>  hapus
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

        $row = $this->ambil_surat($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Surat masuk tidak ditemukan.', 404);
            return;
        }

        $this->ok($this->payload_detail($row));
    }

    /**
     * POST api/v1/surat-masuk
     *
     * Meniru Surat_masuk::simpan(): operator saja, dokumen wajib, nomor
     * agenda otomatis.
     */
    protected function store()
    {
        if (empty($this->user['operator'])) {
            $this->fail('FORBIDDEN', 'Hanya operator surat yang dapat menambah surat masuk.', 403);
            return;
        }

        $input = $this->input_json();
        $input['file'] = isset($_FILES['file']['name']) ? (string) $_FILES['file']['name'] : '';

        $fields = api_apply_rules(api_rules_surat_masuk(FALSE), $input);
        if (! empty($fields)) {
            $this->fail('VALIDATION_FAILED', api_first_error($fields), 422, $fields);
            return;
        }

        // Tahun agenda mengikuti tgl_diterima, sama seperti controller HTML.
        $ts_diterima  = strtotime((string) $input['tgl_diterima']);
        $tahun_agenda = $ts_diterima ? (int) date('Y', $ts_diterima) : (int) date('Y');

        $data = array(
            'kode'         => (string) $input['kode'],
            'no_agenda'    => $this->M_surmas->next_no_agenda($tahun_agenda),
            'no_surat'     => (string) $input['no_surat'],
            'tgl_surat'    => (string) $input['tgl_surat'],
            'pengirim'     => (string) $input['pengirim'],
            'perihal'      => (string) $input['perihal'],
            'tgl_diterima' => (string) $input['tgl_diterima'],
            'pengolah'     => (string) $input['pengolah'],
            'keterangan'   => isset($input['keterangan']) ? (string) $input['keterangan'] : '',
            'file'         => '',
        );

        $upload = api_upload_pdf('file', 'file/sm', $this->nama_berkas($data));
        if (! $upload['ok']) {
            $pesan = $upload['error'] !== '' ? $upload['error'] : 'Dokumen surat (PDF) wajib diunggah.';
            $this->fail('UPLOAD_FAILED', $pesan, 422, array('file' => $pesan));
            return;
        }
        $data['file'] = $upload['path'];

        $id = $this->M_surmas->insert($data);
        if (! $id) {
            // Jangan tinggalkan berkas yatim bila penyimpanan gagal.
            if (! empty($upload['full'])) {
                @unlink($upload['full']);
            }
            $this->fail('SERVER_ERROR', 'Surat masuk gagal disimpan.', 500);
            return;
        }

        $row = $this->M_surmas->get($id);

        $this->created($this->payload_detail($row));
    }

    /**
     * PUT api/v1/surat-masuk/<id>
     *
     * Meniru Surat_masuk::update():
     *   - no_agenda TIDAK ikut diubah,
     *   - berkas opsional; bila ada, kolom `file` diganti,
     *   - berkas lama tidak dihapus (perilaku controller HTML).
     *
     * @param int $id
     */
    protected function update($id)
    {
        $row = $this->ambil_surat($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Surat masuk tidak ditemukan.', 404);
            return;
        }

        // Surat_masuk::update() tidak memasang penjaga peran; endpoint GET
        // detail sudah mengiklankan can_edit = admin/operator. API menegakkan
        // nilai yang diiklankan itu (lihat laporan Fase 2).
        if (empty($this->user['operator']) && empty($this->user['admin'])) {
            $this->fail('FORBIDDEN', 'Hanya operator surat yang dapat mengubah surat masuk.', 403);
            return;
        }

        $input = $this->input_json();
        $input['file'] = isset($_FILES['file']['name']) ? (string) $_FILES['file']['name'] : '';

        $fields = api_apply_rules(api_rules_surat_masuk(TRUE), $input);
        if (! empty($fields)) {
            $this->fail('VALIDATION_FAILED', api_first_error($fields), 422, $fields);
            return;
        }

        $data = array(
            'kode'         => (string) $input['kode'],
            'no_surat'     => (string) $input['no_surat'],
            'tgl_surat'    => (string) $input['tgl_surat'],
            'pengirim'     => (string) $input['pengirim'],
            'perihal'      => (string) $input['perihal'],
            'tgl_diterima' => (string) $input['tgl_diterima'],
            'pengolah'     => (string) $input['pengolah'],
            'keterangan'   => isset($input['keterangan']) ? (string) $input['keterangan'] : '',
        );

        if (! empty($_FILES['file']['name'])) {
            $upload = api_upload_pdf('file', 'file/sm', $this->nama_berkas($data));
            if (! $upload['ok']) {
                $pesan = $upload['error'] !== '' ? $upload['error'] : 'Gagal upload dokumen surat.';
                $this->fail('UPLOAD_FAILED', $pesan, 422, array('file' => $pesan));
                return;
            }
            $data['file'] = $upload['path'];
        }

        $this->M_surmas->update((int) $row->id, $data);

        $this->ok($this->payload_detail($this->M_surmas->get((int) $row->id)));
    }

    /**
     * DELETE api/v1/surat-masuk/<id>
     *
     * Meniru Surat_masuk::hapus(): hanya admin. Berkas di disk TIDAK dihapus
     * karena controller HTML juga tidak menghapusnya.
     *
     * @param int $id
     */
    protected function destroy($id)
    {
        $row = $this->ambil_surat($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Surat masuk tidak ditemukan.', 404);
            return;
        }

        if (empty($this->user['admin'])) {
            $this->fail('FORBIDDEN', 'Hanya admin yang dapat menghapus data.', 403);
            return;
        }

        $this->M_surmas->delete((int) $row->id);

        $this->ok(array(
            'id'      => (int) $row->id,
            'dihapus' => TRUE,
        ));
    }

    /**
     * POST api/v1/surat-masuk/<id>/arsip
     *
     * Meniru Surat_masuk::arsipkan(): operator saja; surat yang sudah pernah
     * didisposisi hanya boleh diarsipkan setelah semua disposisi dikembalikan.
     *
     * @param int $id
     */
    public function arsip($id = 0)
    {
        if ($this->method_efektif() !== 'POST') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode POST.', 405);
            return;
        }

        if (empty($this->user['operator'])) {
            $this->fail('FORBIDDEN', 'Hanya operator surat yang dapat mengarsipkan surat.', 403);
            return;
        }

        $row = $this->ambil_surat($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Surat masuk tidak ditemukan.', 404);
            return;
        }

        /**
         * Surat hanya dapat diarsipkan ketika POSISINYA berada di operator.
         *
         * `pemilik_posisi()` memusatkan aturan:
         *   - belum pernah didisposisi          -> operator
         *   - disposisi terakhir DIKEMBALIKAN   -> operator (surat kembali ke
         *     meja operator, siap diarsipkan)
         *   - disposisi terakhir ke pegawai     -> bukan posisi operator
         *
         * Sebelumnya dipakai `semua_dikembalikan()`, yang menuntut SELURUH
         * baris disposisi dikembalikan. Pada surat yang pernah diteruskan
         * (A -> B -> C), pengembalian oleh C saja tidak pernah cukup sehingga
         * surat tak dapat diarsipkan meski sudah kembali ke operator.
         */
        $pemilik = $this->M_disposisi->pemilik_posisi((int) $row->id);
        if (! $pemilik['di_operator']) {
            $this->fail(
                'ARSIP_DITOLAK',
                'Surat belum dapat diarsipkan. Menunggu disposisi dikembalikan ke operator.',
                409
            );
            return;
        }

        $this->M_surmas->set_status((int) $row->id, 'diarsipkan');

        $this->ok($this->payload_detail($this->M_surmas->get((int) $row->id)));
    }

    /**
     * POST api/v1/surat-masuk/<id>/batal-arsip
     *
     * Meniru Surat_masuk::batal_arsip(): status dikembalikan ke 'didiposisi'
     * bila surat pernah didisposisikan, selain itu 'aktif'.
     *
     * @param int $id
     */
    public function batal_arsip($id = 0)
    {
        if ($this->method_efektif() !== 'POST') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode POST.', 405);
            return;
        }

        if (empty($this->user['operator'])) {
            $this->fail('FORBIDDEN', 'Hanya operator surat yang dapat mengubah status arsip.', 403);
            return;
        }

        $row = $this->ambil_surat($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Surat masuk tidak ditemukan.', 404);
            return;
        }

        $ada    = $this->db->where('surmas_id', (int) $row->id)->count_all_results('disposisi');
        $status = ($ada > 0) ? 'didiposisi' : 'aktif';

        $this->M_surmas->set_status((int) $row->id, $status);

        $this->ok($this->payload_detail($this->M_surmas->get((int) $row->id)));
    }

    /**
     * GET api/v1/surat-masuk/<id>/disposisi
     *
     * Menggantikan halaman cetak surat_masuk/disposisi.php dengan JSON.
     * Memuat seluruh nilai yang dirender view tersebut (termasuk teks tanggal
     * Indonesia) plus URL kop surat, sehingga Astro dapat mencetaknya sendiri.
     *
     * @param int $id
     */
    public function disposisi($id = 0)
    {
        if ($this->method_efektif() !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET.', 405);
            return;
        }

        $row = $this->ambil_surat($id);
        if (! $row) {
            $this->fail('NOT_FOUND', 'Surat masuk tidak ditemukan.', 404);
            return;
        }

        /**
         * Riwayat disposisi surat ini, urut naik (terlama -> terbaru).
         *
         * Lembar cetak menampilkan riwayat tersebut; urutan naik dipilih agar
         * alur disposisi terbaca kronologis dari atas ke bawah, sama dengan
         * blok "Riwayat" pada halaman detail disposisi.
         */
        $riwayat_rows = $this->M_disposisi->riwayat_surmas((int) $row->id);
        $riwayat      = array();

        foreach ($riwayat_rows as $rw) {
            $riwayat[] = array(
                'id'           => (int) $rw->id,
                'kepada'       => (string) $rw->kepada,
                'kategori'     => (string) $rw->kategori,
                'nip_tujuan'   => isset($rw->nip_tujuan) && $rw->nip_tujuan !== ''
                    ? (string) $rw->nip_tujuan
                    : NULL,
                'instruksi'    => isset($rw->instruksi) ? $rw->instruksi : NULL,
                'catatan'      => isset($rw->catatan) ? $rw->catatan : NULL,
                'dari'         => isset($rw->dari) ? (string) $rw->dari : '',
                'tanggal'      => api_datetime(isset($rw->tanggal) ? $rw->tanggal : NULL),
                'dibaca'       => ! empty($rw->dibaca),
                'dikembalikan' => ! empty($rw->dikembalikan),
            );
        }

        // Disposisi terakhir (tanggalnya dipakai sebagai tanggal lembar cetak).
        $terakhir = ! empty($riwayat) ? $riwayat[count($riwayat) - 1] : NULL;

        /**
         * Penanda tangan lembar disposisi: Ketua Pengadilan Agama.
         *
         * Diambil dari database pegawai (jabatan `Ketua`) supaya nama pejabat
         * selalu mengikuti data kepegawaian, bukan teks tetap di view.
         */
        $ketua = $this->M_user->ketua();

        $this->ok(array(
            'surat' => array(
                'id'           => (int) $row->id,
                'no_surat'     => (string) $row->no_surat,
                'no_agenda'    => (string) $row->no_agenda,
                'kode'         => isset($row->kode) ? (string) $row->kode : '',
                'pengirim'     => (string) $row->pengirim,
                'perihal'      => (string) $row->perihal,
                'pengolah'     => isset($row->pengolah) ? (string) $row->pengolah : '',
                'keterangan'   => isset($row->keterangan) ? (string) $row->keterangan : '',
                'tgl_surat'    => api_tanggal(isset($row->tgl_surat) ? $row->tgl_surat : NULL),
                'tgl_diterima' => api_tanggal(isset($row->tgl_diterima) ? $row->tgl_diterima : NULL),
                'status'       => isset($row->status) ? (string) $row->status : '',
                'file'         => api_file_meta(isset($row->file) ? $row->file : NULL),
            ),
            // View mencetak kop instansi di bagian atas lembar disposisi.
            'kop'   => base_url('assets/img/KOP.jpg'),
            'judul' => 'Lembar Disposisi',
            // Baris kosong untuk diisi tangan pada lembar cetak.
            'kolom_isi_tangan' => array(
                'Diteruskan kepada',
                'Instruksi',
                'Catatan',
            ),
            'riwayat'   => $riwayat,
            'terakhir'  => $terakhir,
            // Blok tanda tangan: jabatan tetap + nama dari data pegawai.
            'penandatangan' => array(
                'jabatan' => 'Ketua Pengadilan Agama Kupang',
                'nama'    => $ketua ? (string) $ketua->nama : '',
                'nip'     => $ketua && isset($ketua->nip) ? (string) $ketua->nip : '',
            ),
        ));
    }

    // ------------------------------------------------------------------
    // Bantuan
    // ------------------------------------------------------------------

    /**
     * Payload detail satu surat, dipakai oleh GET, POST, PUT, dan aksi status
     * agar bentuk respons selalu sama.
     *
     * @param  object $row
     * @return array
     */
    protected function payload_detail($row)
    {
        $id          = (int) $row->id;
        $nip         = (string) $this->user['sub'];
        $is_admin    = ! empty($this->user['admin']);
        $is_operator = ! empty($this->user['operator']);
        $diarsipkan  = (isset($row->status) && $row->status === 'diarsipkan');

        // Riwayat memakai method model yang sama dengan Surat_masuk::detail().
        $riwayat = $this->M_disposisi->riwayat_surmas($id);
        $posisi  = ! empty($riwayat) ? $riwayat[count($riwayat) - 1] : NULL;

        $riwayat_out = array();
        foreach ($riwayat as $rw) {
            $riwayat_out[] = array(
                'id'           => (int) $rw->id,
                'kepada'       => (string) $rw->kepada,
                'kategori'     => (string) $rw->kategori,
                'nip_tujuan'   => isset($rw->nip_tujuan) && $rw->nip_tujuan !== ''
                    ? (string) $rw->nip_tujuan
                    : NULL,
                'instruksi'    => isset($rw->instruksi) ? $rw->instruksi : NULL,
                'catatan'      => isset($rw->catatan) ? $rw->catatan : NULL,
                'dari'         => isset($rw->dari) ? (string) $rw->dari : '',
                'tanggal'      => api_datetime(isset($rw->tanggal) ? $rw->tanggal : NULL),
                'dibaca'       => ! empty($rw->dibaca),
                'dikembalikan' => ! empty($rw->dikembalikan),
            );
        }

        $posisi_out = NULL;
        if ($posisi) {
            // Tone mengikuti badge pada surat_masuk/detail.php.
            if (! empty($posisi->dikembalikan)) {
                $status_posisi = 'Dikembalikan ke operator';
                $tone_posisi   = 'ok';
            } elseif (! empty($posisi->dibaca)) {
                $status_posisi = 'Sudah dibaca';
                $tone_posisi   = 'neutral';
            } else {
                $status_posisi = 'Belum dibaca';
                $tone_posisi   = 'warning';
            }

            $posisi_out = array(
                'id'           => (int) $posisi->id,
                'kepada'       => (string) $posisi->kepada,
                'kategori'     => (string) $posisi->kategori,
                'instruksi'    => isset($posisi->instruksi) ? $posisi->instruksi : NULL,
                'tanggal'      => api_datetime(isset($posisi->tanggal) ? $posisi->tanggal : NULL),
                'dibaca'       => ! empty($posisi->dibaca),
                'dikembalikan' => ! empty($posisi->dikembalikan),
                'status'       => $status_posisi,
                'tone'         => $tone_posisi,
            );
        }

        // Hak aksi mengikuti view + penjaga di Surat_masuk::arsipkan() /
        // batal_arsip() / hapus(). Astro tidak perlu menurunkan ulang.
        /**
         * Hak mengirim/meneruskan disposisi — aturan yang sama dengan daftar:
         * hanya PEMILIK POSISI SAAT INI yang berhak. `pemilik_posisi()`
         * memusatkan aturannya: surat yang belum didisposisi atau sudah
         * DIKEMBALIKAN berada di meja operator, selain itu pada penerima
         * disposisi terakhir.
         */
        $pemilik_posisi = $this->M_disposisi->pemilik_posisi($id);

        $can_send = ! $diarsipkan && (
            ($pemilik_posisi['di_operator'] && $is_operator)
            || ($pemilik_posisi['nip'] !== NULL && $pemilik_posisi['nip'] === $nip)
        );
        // Arsip hanya bila posisinya di operator (belum didisposisi atau
        // sudah dikembalikan) — sama dengan penjaga di arsipkan().
        $can_arsipkan = $is_operator && ! $diarsipkan && $pemilik_posisi['di_operator'];

        return array(
            'id'           => $id,
            'kode'         => isset($row->kode) ? (string) $row->kode : '',
            'no_surat'     => (string) $row->no_surat,
            'no_agenda'    => (string) $row->no_agenda,
            'tgl_surat'    => api_tanggal(isset($row->tgl_surat) ? $row->tgl_surat : NULL),
            'tgl_diterima' => api_tanggal(isset($row->tgl_diterima) ? $row->tgl_diterima : NULL),
            'pengirim'     => (string) $row->pengirim,
            'perihal'      => (string) $row->perihal,
            'pengolah'     => isset($row->pengolah) ? (string) $row->pengolah : '',
            'keterangan'   => isset($row->keterangan) ? (string) $row->keterangan : '',
            'status'       => isset($row->status) ? (string) $row->status : '',
            'diarsipkan'   => $diarsipkan,
            'file'         => api_file_meta(isset($row->file) ? $row->file : NULL),
            'posisi'       => $posisi_out,
            'riwayat'      => $riwayat_out,
            'aksi'         => array(
                'can_view'           => TRUE,
                'can_send'           => (bool) $can_send,
                'can_edit'           => (bool) ($is_admin || $is_operator),
                'can_print_disposisi' => (bool) ($is_admin || $is_operator),
                'can_arsipkan'       => (bool) $can_arsipkan,
                'can_batal_arsip'    => (bool) ($is_operator && $diarsipkan),
                'can_delete'         => (bool) $is_admin,
            ),
        );
    }

    /**
     * Ambil satu surat, atau NULL bila id tidak valid.
     *
     * @param  mixed $id
     * @return object|null
     */
    protected function ambil_surat($id)
    {
        $id = (int) $id;

        return $id > 0 ? $this->M_surmas->get($id) : NULL;
    }

    /**
     * Nama berkas tujuan, menyalin Surat_masuk::simpan()/update():
     *   {tahun berjalan}_{kode}_{perihal 30 karakter}.pdf
     *
     * `date('Y')` (bukan tahun surat) dipakai agar sama dengan controller HTML.
     *
     * @param  array $data
     * @return string
     */
    protected function nama_berkas(array $data)
    {
        $tahun         = date('Y');
        $perihal_short = substr(preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $data['perihal']), 0, 30);

        return $tahun . '_' . $data['kode'] . '_' . $perihal_short . '.pdf';
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

    /**
     * Nilai filter yang hanya boleh salah satu dari daftar; selain itu NULL.
     *
     * @param  mixed $value
     * @param  array $allowed
     * @return string|null
     */
    protected function filter_choice($value, array $allowed)
    {
        $value = is_string($value) ? trim($value) : '';

        return in_array($value, $allowed, TRUE) ? $value : NULL;
    }
}

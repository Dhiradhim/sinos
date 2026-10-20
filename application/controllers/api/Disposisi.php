<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Api_Controller.php';

/**
 * API Disposisi - baca daftar + detail, lalu tulis (Fase 2).
 *
 *   GET    api/v1/disposisi?page=&per_page=&q=&arsip=
 *   GET    api/v1/disposisi/ID                (menandai dibaca, lihat di bawah)
 *   POST   api/v1/disposisi                   kirim disposisi
 *   GET    api/v1/disposisi/form/SURMAS_ID    data form (JSON, bukan HTML)
 *   POST   api/v1/disposisi/ID/arsip          arsipkan surat
 *   DELETE api/v1/disposisi/ID                hapus disposisi
 *
 * Cakupan daftar mengikuti Disposisi::index():
 *   - operator (atau admin) melihat seluruh surat yang didisposisikan
 *     (M_disposisi::api_daftar_operator()), termasuk yang sudah diarsipkan
 *     bila `arsip=diarsipkan`;
 *   - user lain hanya melihat surat yang posisi disposisinya ada padanya
 *     (M_disposisi::api_daftar_atas_nama()).
 *
 * PERUBAHAN PERILAKU YANG DISENGAJA (Fase 2)
 * `GET /disposisi/{id}` sekarang menandai disposisi sebagai dibaca bila
 * ditujukan kepada user pemanggil, persis seperti `Disposisi::detail()`.
 * Sebelumnya (Fase 1) endpoint ini baca-saja dan melaporkan status apa
 * adanya. Respons memuat `dibaca: true` setelah penandaan agar klien tahu
 * baris berubah. Ini satu-satunya efek samping pada endpoint GET.
 *
 * `GET /disposisi/form/{surmas_id}` menggantikan `Disposisi::form_ajax()`
 * yang mengembalikan fragmen HTML. Endpoint baru mengembalikan JSON berisi
 * data yang sama: ringkasan surat, daftar penerima aktif, riwayat disposisi,
 * pilihan instruksi, dan flag peran user saat ini. Tidak ada HTML di JSON.
 */
class Disposisi extends MY_Api_Controller
{
    /** Batas per_page agar satu request tidak menarik seluruh tabel. */
    const MAX_PER_PAGE = 100;

    /** per_page default. */
    const DEFAULT_PER_PAGE = 25;

    /**
     * Pilihan instruksi disposisi, disalin dari disposisi/_form_ajax.php.
     *
     * @var array
     */
    protected $instruksi = array(
        'Untuk Ditindaklanjuti',
        'Untuk Diketahui',
        'Untuk Diproses Sesuai Ketentuan',
        'Untuk Dijawab',
        'Untuk Dikoordinasikan',
        'Untuk Dipelajari dan Dikaji',
        'Untuk Diarsipkan',
    );

    public function __construct()
    {
        parent::__construct();

        $this->load->helper(array('api_present', 'api_validation'));
        $this->load->model(array('M_disposisi', 'M_surmas', 'M_user'));
    }

    /**
     * GET  api/v1/disposisi   daftar
     * POST api/v1/disposisi   kirim disposisi
     */
    public function index()
    {
        $method = $this->input->method(TRUE);

        if ($method === 'POST') {
            $this->kirim();
            return;
        }

        if ($method !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET atau POST.', 405);
            return;
        }

        $nip        = (string) $this->user['sub'];
        $is_admin   = ! empty($this->user['admin']);
        $is_operator = ! empty($this->user['operator']);

        $page     = max(1, (int) $this->input->get('page'));
        $per_page = $this->per_page();
        $q        = trim((string) $this->input->get('q'));
        $arsip    = $this->filter_choice($this->input->get('arsip'), array('diarsipkan', 'aktif'));
        $offset   = ($page - 1) * $per_page;

        if ($is_operator) {
            $total = (int) $this->M_disposisi->api_count_operator($q, $arsip);
            $rows  = $this->M_disposisi->api_daftar_operator($q, $arsip, $per_page, $offset);
            $lingkup = 'operator';
        } else {
            $total = (int) $this->M_disposisi->api_count_atas_nama($nip, $q, $arsip);
            $rows  = $this->M_disposisi->api_daftar_atas_nama($nip, $q, $arsip, $per_page, $offset);
            $lingkup = 'atas_nama';
        }

        // Daftar NIP operator dipakai untuk menentukan posisi_di_operator,
        // sama seperti Disposisi::index().
        $operator_nips = array();
        $rows_op = $this->db
            ->select('nip')
            ->where('is_operator', 1)
            ->get('pegawai_db.pegawai')
            ->result();

        foreach ($rows_op as $ou) {
            $operator_nips[(string) $ou->nip] = TRUE;
        }

        $data = array();
        foreach ($rows as $r) {
            $status_surat = isset($r->status_surat) ? (string) $r->status_surat : '';
            $diarsipkan   = ($status_surat === 'diarsipkan');

            // Posisi surat berada di operator (penerima disposisi terakhir
            // adalah pegawai bertag operator, belum dikembalikan/diarsip).
            $posisi_di_operator = ($status_surat !== 'diarsipkan'
                && (int) $r->dikembalikan === 0
                && ! empty($r->nip_tujuan)
                && isset($operator_nips[(string) $r->nip_tujuan]));

            $status_posisi = api_status_posisi_disposisi($r);

            $data[] = array(
                'id'          => (int) $r->id,
                'surmas_id'   => (int) $r->surmas_id,
                'no_surat'    => (string) $r->no_surat,
                'no_agenda'   => (string) $r->no_agenda,
                'tgl_surat'   => api_tanggal(isset($r->tgl_surat) ? $r->tgl_surat : NULL),
                'tgl_diterima' => api_tanggal(isset($r->tgl_diterima) ? $r->tgl_diterima : NULL),
                'pengirim'    => (string) $r->pengirim,
                'perihal'     => (string) $r->perihal,
                'kepada'      => (string) $r->kepada,
                'kategori'    => (string) $r->kategori,
                'nip_tujuan'  => isset($r->nip_tujuan) && $r->nip_tujuan !== ''
                    ? (string) $r->nip_tujuan
                    : NULL,
                'lokasi'      => api_lokasi_disposisi($r),
                'instruksi'   => isset($r->instruksi) && $r->instruksi !== ''
                    ? (string) $r->instruksi
                    : NULL,
                'catatan'     => isset($r->catatan) ? $r->catatan : NULL,
                'dari'        => isset($r->dari) ? (string) $r->dari : '',
                'pengirim_nama' => isset($r->pengirim_nama) ? (string) $r->pengirim_nama : '',
                'tanggal'     => api_datetime(isset($r->tanggal) ? $r->tanggal : NULL),
                'dibaca'      => ! empty($r->dibaca),
                'dikembalikan' => ! empty($r->dikembalikan),
                'status_surat' => $status_surat,
                'diarsipkan'  => $diarsipkan,
                'posisi_di_operator' => (bool) $posisi_di_operator,
                'status'      => $status_posisi['status'],
                'tone'        => $status_posisi['tone'],
                'file'        => api_file_meta(isset($r->file) ? $r->file : NULL),
                'aksi'        => array(
                    'can_view'    => TRUE,
                    'can_archive' => (bool) ($is_operator && $posisi_di_operator),
                    'can_delete'  => (bool) ($is_admin
                        || (isset($r->dari) && (string) $r->dari === $nip)),
                    'can_forward' => (bool) ($is_operator
                        || $this->M_disposisi->can_send((int) $r->surmas_id, $nip, FALSE)),
                ),
            );
        }

        $meta           = $this->paginate($page, $per_page, $total);
        $meta['lingkup'] = $lingkup;
        $meta['arsip']   = $arsip;

        $this->ok($data, $meta);
    }

    /**
     * GET    api/v1/disposisi/ID   detail (menandai dibaca)
     * DELETE api/v1/disposisi/ID   hapus
     *
     * @param int $id
     */
    public function show($id = 0)
    {
        if ($this->input->method(TRUE) === 'DELETE') {
            $this->destroy($id);
            return;
        }

        if ($this->input->method(TRUE) !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET atau DELETE.', 405);
            return;
        }

        $id  = (int) $id;
        $row = $id > 0 ? $this->M_disposisi->get($id) : NULL;

        if (! $row) {
            $this->fail('NOT_FOUND', 'Disposisi tidak ditemukan.', 404);
            return;
        }

        $nip         = (string) $this->user['sub'];
        $is_admin    = ! empty($this->user['admin']);
        $is_operator = ! empty($this->user['operator']);

        // Disposisi::detail() menandai dibaca bila ditujukan ke user ini.
        // Fase 2 mengikuti perilaku tersebut; `dibaca` pada respons sudah
        // mencerminkan nilai setelah penandaan.
        $ditandai = FALSE;
        if ((string) $row->nip_tujuan === $nip && empty($row->dibaca)) {
            $this->M_disposisi->tandai_dibaca((int) $row->id);
            $row->dibaca = 1;
            $ditandai = TRUE;
        }

        $dibaca = ! empty($row->dibaca);

        $surat = $this->M_surmas->get((int) $row->surmas_id);

        /**
         * PEMILIK DISPOSISI SAAT INI.
         *
         * `$row` adalah disposisi yang sedang dibuka, yang bisa saja sudah
         * diteruskan ke orang lain. Pemilik yang sah untuk meneruskan adalah
         * penerima disposisi TERAKHIR surat ini (posisi surat saat ini),
         * bukan penerima baris yang sedang dilihat.
         *
         * Sebelumnya `can_forward` hanya memakai `$is_operator`, sehingga
         * semua operator — termasuk yang sudah meneruskan surat ke pihak lain
         * — tetap dapat meneruskan. Itu keliru.
         */
        $posisi_saat_ini = $this->M_disposisi->posisi_terakhir(array((int) $row->surmas_id));
        $terakhir        = isset($posisi_saat_ini[(int) $row->surmas_id])
            ? $posisi_saat_ini[(int) $row->surmas_id]
            : NULL;

        $nip_pemilik = ($terakhir && ! empty($terakhir->nip_tujuan))
            ? (string) $terakhir->nip_tujuan
            : '';

        // Pemilik saat ini, ATAU operator pada surat yang belum pernah
        // didisposisikan (tidak terjadi di endpoint ini, tetapi dijaga agar
        // logika tetap konsisten dengan can_send()).
        $is_pemilik = ($nip_pemilik !== '' && $nip_pemilik === $nip)
            || ($terakhir === NULL && $is_operator);

        $can_forward = $is_pemilik
            && ($is_operator || $this->M_disposisi->can_send((int) $row->surmas_id, $nip, FALSE));

        $this->ok(array(
            'id'           => (int) $row->id,
            'surmas_id'    => (int) $row->surmas_id,
            'kepada'       => (string) $row->kepada,
            'kategori'     => (string) $row->kategori,
            'nip_tujuan'   => isset($row->nip_tujuan) && $row->nip_tujuan !== ''
                ? (string) $row->nip_tujuan
                : NULL,
            'instruksi'    => isset($row->instruksi) ? $row->instruksi : NULL,
            'catatan'      => isset($row->catatan) ? $row->catatan : NULL,
            'dari'         => isset($row->dari) ? (string) $row->dari : '',
            'tanggal'      => api_datetime(isset($row->tanggal) ? $row->tanggal : NULL),
            'dibaca'       => $dibaca,
            // Dokumentasi efek samping: TRUE bila request ini yang menandai.
            'ditandai_dibaca' => $ditandai,
            'dikembalikan' => ! empty($row->dikembalikan),
            'tgl_dikembalikan' => api_datetime(isset($row->tgl_dikembalikan) ? $row->tgl_dikembalikan : NULL),
            'status'       => $dibaca ? 'Sudah dibaca' : 'Belum dibaca',
            'tone'         => $dibaca ? 'ok' : 'warning',
            // Posisi disposisi SAAT INI untuk surat ini (penerima terakhir).
            // Dipakai UI untuk menjelaskan siapa yang berhak meneruskan.
            'posisi'       => $terakhir ? array(
                'nip'      => $nip_pemilik !== '' ? $nip_pemilik : NULL,
                'kepada'   => isset($terakhir->kepada) ? (string) $terakhir->kepada : '',
                'kategori' => isset($terakhir->kategori) ? (string) $terakhir->kategori : '',
                'tanggal'  => api_datetime(isset($terakhir->tanggal) ? $terakhir->tanggal : NULL),
                'is_pemilik' => (bool) ($nip_pemilik !== '' && $nip_pemilik === $nip),
            ) : NULL,
            'surat'        => $surat ? array(
                'id'           => (int) $surat->id,
                'kode'         => isset($surat->kode) ? (string) $surat->kode : '',
                'no_surat'     => (string) $surat->no_surat,
                'no_agenda'    => (string) $surat->no_agenda,
                'tgl_surat'    => api_tanggal(isset($surat->tgl_surat) ? $surat->tgl_surat : NULL),
                'tgl_diterima' => api_tanggal(isset($surat->tgl_diterima) ? $surat->tgl_diterima : NULL),
                'pengirim'     => (string) $surat->pengirim,
                'perihal'      => (string) $surat->perihal,
                'pengolah'     => isset($surat->pengolah) ? (string) $surat->pengolah : '',
                'status'       => isset($surat->status) ? (string) $surat->status : '',
                'file'         => api_file_meta(isset($surat->file) ? $surat->file : NULL),
            ) : NULL,
            'aksi'         => array(
                'can_view'    => TRUE,
                'can_forward' => (bool) $can_forward,
                'can_archive' => (bool) ($is_operator
                    && $surat
                    && $surat->status !== 'diarsipkan'
                    && (int) $row->dikembalikan === 0
                    && $this->M_disposisi->is_operator_nip($row->nip_tujuan)),
                'can_delete'  => (bool) ($is_admin
                    || (isset($row->dari) && (string) $row->dari === $nip)),
            ),
        ));
    }

    /**
     * POST api/v1/disposisi
     *
     * Meniru Disposisi::kirim():
     *   - operator atau penerima disposisi surat tersebut (can_send),
     *   - target = id user dari M_user::penerima_aktif() atau "Arsip",
     *   - M_disposisi::simpan_batch() lalu status surat 'didiposisi'.
     */
    protected function kirim()
    {
        $input  = $this->input_json();
        $fields = api_apply_rules(api_rules_disposisi(), $input);
        if (! empty($fields)) {
            $this->fail('VALIDATION_FAILED', api_first_error($fields), 422, $fields);
            return;
        }

        $surmas_id = (int) $input['surmas_id'];
        $surat     = $this->M_surmas->get($surmas_id);

        if (! $surat) {
            $this->fail('NOT_FOUND', 'Surat masuk tidak ditemukan.', 404);
            return;
        }

        // Operator surat boleh mengirim disposisi awal; penerima disposisi
        // berhak meneruskan disposisi surat tersebut.
        if (
            empty($this->user['operator'])
            && ! $this->M_disposisi->can_send($surmas_id, (string) $this->user['sub'], FALSE)
        ) {
            $this->fail(
                'FORBIDDEN',
                'Hanya operator surat atau penerima disposisi yang dapat mengirim disposisi.',
                403
            );
            return;
        }

        $target    = trim((string) $input['target']);
        $instruksi = isset($input['instruksi']) ? (string) $input['instruksi'] : NULL;
        $catatan   = isset($input['catatan']) ? (string) $input['catatan'] : NULL;

        if ($instruksi === '') {
            $instruksi = NULL;
        }
        if ($catatan === '') {
            $catatan = NULL;
        }

        // Peta penerima aktif: id -> data user ("Jabatan (Nama)").
        $map = array();
        foreach ($this->M_user->penerima_aktif() as $u) {
            $map[(string) $u->id] = $u;
        }

        $rows = array();
        if ($target === 'Arsip') {
            $rows[] = array(
                'kepada'     => 'Arsip',
                'kategori'   => 'Arsip',
                'nip_tujuan' => NULL,
            );
        } elseif (isset($map[$target])) {
            $u = $map[$target];
            $rows[] = array(
                'kepada'     => $u->nama,
                'kategori'   => $u->jabatan,
                'nip_tujuan' => $u->nip,
            );
        }

        $n = $this->M_disposisi->simpan_batch(
            $surmas_id,
            $rows,
            (string) $this->user['sub'],
            $instruksi,
            $catatan
        );

        if ($n < 1) {
            $pesan = 'Tidak ada penerima valid yang dipilih.';
            $this->fail('VALIDATION_FAILED', $pesan, 422, array('target' => $pesan));
            return;
        }

        // Tandai surat sedang dalam proses disposisi.
        $this->M_surmas->set_status($surmas_id, 'didiposisi');

        // Kembalikan baris yang baru tersimpan agar klien tidak perlu
        // mengambil ulang. Disusun dari nilai yang dikirim (simpan_batch
        // tidak mengembalikan id).
        $this->created(array(
            'surmas_id'  => $surmas_id,
            'jumlah'     => $n,
            'penerima'   => array_map(function ($r) {
                return array(
                    'kepada'     => $r['kepada'],
                    'kategori'   => $r['kategori'],
                    'nip_tujuan' => $r['nip_tujuan'],
                );
            }, $rows),
            'instruksi'  => $instruksi,
            'catatan'    => $catatan,
            'dari'       => (string) $this->user['sub'],
            'status_surat' => 'didiposisi',
            'pesan'      => 'Disposisi berhasil dikirim kepada ' . $n . ' penerima.',
        ));
    }

    /**
     * GET api/v1/disposisi/form/SURMAS_ID
     *
     * Pengganti JSON untuk `Disposisi::form_ajax()`. Memuat:
     *   - ringkasan surat yang dirender di modal (no surat, agenda, tanggal
     *     teks Indonesia, pengirim, perihal),
     *   - daftar penerima aktif (id, nip, nama, jabatan, label) + opsi Arsip,
     *   - riwayat disposisi (urut terbaru dulu, seperti controller),
     *   - pilihan instruksi,
     *   - flag peran user saat ini (is_admin, is_operator, can_send).
     *
     * @param int $surmas_id
     */
    public function form($surmas_id = 0)
    {
        if ($this->input->method(TRUE) !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET.', 405);
            return;
        }

        $surmas_id = (int) $surmas_id;
        $surat     = $surmas_id > 0 ? $this->M_surmas->get($surmas_id) : NULL;

        if (! $surat) {
            $this->fail('NOT_FOUND', 'Surat masuk tidak ditemukan.', 404);
            return;
        }

        $nip         = (string) $this->user['sub'];
        $is_operator = ! empty($this->user['operator']);
        $can_send    = $is_operator || $this->M_disposisi->can_send($surmas_id, $nip, FALSE);

        if (! $can_send) {
            $this->fail('FORBIDDEN', 'Anda tidak berhak mengirim disposisi surat ini.', 403);
            return;
        }

        $penerima = array();
        foreach ($this->M_user->penerima_aktif() as $u) {
            $penerima[] = array(
                'id'      => (int) $u->id,
                'nip'     => (string) $u->nip,
                'nama'    => (string) $u->nama,
                'jabatan' => (string) $u->jabatan,
                'label'   => (string) $u->label,
            );
        }

        // Riwayat memakai urutan yang sama dengan form_ajax: tanggal DESC.
        $riwayat = array();
        $rows = $this->db->order_by('tanggal', 'DESC')
            ->get_where('disposisi', array('surmas_id' => $surmas_id))
            ->result();

        foreach ($rows as $rw) {
            $riwayat[] = array(
                'id'         => (int) $rw->id,
                'kepada'     => (string) $rw->kepada,
                'kategori'   => (string) $rw->kategori,
                'nip_tujuan' => isset($rw->nip_tujuan) && $rw->nip_tujuan !== ''
                    ? (string) $rw->nip_tujuan
                    : NULL,
                'instruksi'  => isset($rw->instruksi) ? $rw->instruksi : NULL,
                'catatan'    => isset($rw->catatan) ? $rw->catatan : NULL,
                'dari'       => isset($rw->dari) ? (string) $rw->dari : '',
                'tanggal'    => api_datetime(isset($rw->tanggal) ? $rw->tanggal : NULL),
                'dibaca'     => ! empty($rw->dibaca),
                'dikembalikan' => ! empty($rw->dikembalikan),
            );
        }

        $instruksi = array();
        foreach ($this->instruksi as $opt) {
            $instruksi[] = array('value' => $opt, 'label' => $opt);
        }

        $this->ok(array(
            'surat'    => array(
                'id'           => (int) $surat->id,
                'no_surat'     => (string) $surat->no_surat,
                'no_agenda'    => (string) $surat->no_agenda,
                'kode'         => isset($surat->kode) ? (string) $surat->kode : '',
                'tgl_surat'    => api_tanggal(isset($surat->tgl_surat) ? $surat->tgl_surat : NULL),
                'tgl_diterima' => api_tanggal(isset($surat->tgl_diterima) ? $surat->tgl_diterima : NULL),
                'pengirim'     => (string) $surat->pengirim,
                'perihal'      => (string) $surat->perihal,
                'pengolah'     => isset($surat->pengolah) ? (string) $surat->pengolah : '',
                'status'       => isset($surat->status) ? (string) $surat->status : '',
                'file'         => api_file_meta(isset($surat->file) ? $surat->file : NULL),
            ),
            'penerima' => $penerima,
            // Opsi statis yang selalu tersedia di view.
            'arsip'    => array('value' => 'Arsip', 'label' => 'Arsip'),
            'instruksi' => $instruksi,
            'riwayat'  => $riwayat,
            'peran'    => array(
                'nip'         => $nip,
                'is_admin'    => ! empty($this->user['admin']),
                'is_operator' => $is_operator,
                'can_send'    => (bool) $can_send,
            ),
        ));
    }

    /**
     * POST api/v1/disposisi/ID/arsip
     *
     * Meniru Disposisi::arsipkan(): parameter rute adalah ID SURAT MASUK
     * (sama seperti `disposisi/arsipkan/{surmas_id}`), bukan id disposisi.
     * Syarat: operator, surat belum diarsipkan, sudah pernah didisposisikan,
     * dan posisi disposisi terakhir berada pada seorang operator.
     *
     * @param int $surmas_id
     */
    public function arsip($surmas_id = 0)
    {
        if ($this->method_efektif() !== 'POST') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode POST.', 405);
            return;
        }

        if (empty($this->user['operator'])) {
            $this->fail('FORBIDDEN', 'Hanya operator surat yang dapat mengarsipkan surat.', 403);
            return;
        }

        $surmas_id = (int) $surmas_id;
        $surat     = $surmas_id > 0 ? $this->M_surmas->get($surmas_id) : NULL;

        if (! $surat) {
            $this->fail('NOT_FOUND', 'Surat masuk tidak ditemukan.', 404);
            return;
        }

        if ($surat->status === 'diarsipkan') {
            $this->fail('SUDAH_DIARSIPKAN', 'Surat sudah diarsipkan.', 409);
            return;
        }

        $ada = $this->db->where('surmas_id', $surmas_id)->count_all_results('disposisi');
        if ($ada === 0) {
            $this->fail('BELUM_DIDISPOSISI', 'Surat belum pernah didisposisikan.', 409);
            return;
        }

        $posisi = $this->M_disposisi->posisi_terakhir(array($surmas_id));
        $last   = isset($posisi[$surmas_id]) ? $posisi[$surmas_id] : NULL;

        $posisi_di_operator = ($last
            && (int) $last->dikembalikan === 0
            && ! empty($last->nip_tujuan)
            && $this->M_disposisi->is_operator_nip($last->nip_tujuan));

        if (! $posisi_di_operator) {
            $this->fail(
                'POSISI_BUKAN_OPERATOR',
                'Surat hanya dapat diarsipkan ketika posisinya berada di operator.',
                409
            );
            return;
        }

        $this->M_surmas->set_status($surmas_id, 'diarsipkan');

        $this->ok(array(
            'surmas_id' => $surmas_id,
            'status'    => 'diarsipkan',
            'pesan'     => 'Surat berhasil diarsipkan.',
        ));
    }

    /**
     * DELETE api/v1/disposisi/ID
     *
     * Meniru Disposisi::hapus(): admin atau pengirim disposisi.
     *
     * @param int $id
     */
    protected function destroy($id)
    {
        $id  = (int) $id;
        $row = $id > 0 ? $this->M_disposisi->get($id) : NULL;

        if (! $row) {
            $this->fail('NOT_FOUND', 'Disposisi tidak ditemukan.', 404);
            return;
        }

        if (
            empty($this->user['admin'])
            && (string) $row->dari !== (string) $this->user['sub']
        ) {
            $this->fail('FORBIDDEN', 'Anda tidak berhak menghapus disposisi ini.', 403);
            return;
        }

        $this->M_disposisi->delete($id);

        $this->ok(array(
            'id'      => $id,
            'dihapus' => TRUE,
            'pesan'   => 'Disposisi berhasil dihapus.',
        ));
    }

    // ------------------------------------------------------------------
    // Bantuan
    // ------------------------------------------------------------------

    /**
     * Metode HTTP efektif, dengan dukungan override untuk multipart.
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

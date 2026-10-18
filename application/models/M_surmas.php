<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * M_surmas - model tabel `surmas` (Surat Masuk)
 * Kolom: id, kode, no_surat, tgl_surat, pengirim, perihal,
 *        tgl_diterima, pengolah, keterangan, file
 */
class M_surmas extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'surmas';
        $this->primary_key = 'id';
    }

    public function daftar_tahun($tahun)
    {
        $this->db->where('YEAR(tgl_surat)', $tahun);
        $this->db->order_by('tgl_surat', 'DESC');
        return $this->db->get('surmas')->result();
    }

    /**
     * Query dasar daftar surat masuk untuk API baca (paginasi server-side).
     *
     * Bentuk dasarnya sama dengan `daftar_tahun()`: WHERE YEAR(tgl_surat) dan
     * ORDER BY tgl_surat DESC, tanpa kolom turunan. Filter `q`, `arsip`, dan
     * `disposisi` adalah tambahan khusus API.
     *
     * Method ini BARU; `daftar_tahun()` tidak diubah.
     *
     * @param  int         $tahun
     * @param  string|null $q
     * @param  string|null $arsip     'diarsipkan' | 'aktif' | NULL (semua)
     * @param  string|null $disposisi 'sudah' | 'belum' | NULL (semua)
     * @return void
     */
    private function _api_query($tahun, $q = NULL, $arsip = NULL, $disposisi = NULL)
    {
        $this->db->from('surmas');
        $this->db->where('YEAR(tgl_surat)', (int) $tahun);

        if ($q !== NULL && $q !== '') {
            $this->db->group_start();
            $this->db->like('no_surat', $q);
            $this->db->or_like('no_agenda', $q);
            $this->db->or_like('perihal', $q);
            $this->db->or_like('pengirim', $q);
            $this->db->or_like('pengolah', $q);
            $this->db->group_end();
        }

        if ($arsip === 'diarsipkan') {
            $this->db->where('status', 'diarsipkan');
        } elseif ($arsip === 'aktif') {
            $this->db->where('status !=', 'diarsipkan');
        }

        if ($disposisi === 'sudah') {
            $this->db->where(
                'EXISTS (SELECT 1 FROM disposisi d WHERE d.surmas_id = surmas.id)',
                NULL,
                FALSE
            );
        } elseif ($disposisi === 'belum') {
            $this->db->where(
                'NOT EXISTS (SELECT 1 FROM disposisi d WHERE d.surmas_id = surmas.id)',
                NULL,
                FALSE
            );
        }
    }

    /**
     * Satu halaman daftar surat masuk (API).
     *
     * @param  int         $tahun
     * @param  string|null $q
     * @param  string|null $arsip
     * @param  string|null $disposisi
     * @param  int         $limit
     * @param  int         $offset
     * @return array of object
     */
    public function api_daftar($tahun, $q = NULL, $arsip = NULL, $disposisi = NULL, $limit = 25, $offset = 0)
    {
        $this->_api_query($tahun, $q, $arsip, $disposisi);

        // Sama dengan daftar_tahun(): tgl_surat DESC. Ditambah `id DESC`
        // sebagai pemecah seri karena beberapa surat dapat bertanggal sama.
        // Tanpa pemecah seri, MySQL bebas mengurutkan baris bertanggal sama,
        // sehingga LIMIT/OFFSET dapat melewatkan atau menggandakan baris antar
        // halaman. Urutan `id DESC` dipilih karena itu urutan yang tampil pada
        // daftar_tahun() (satu query tanpa LIMIT) sehingga JSON dan HTML sama.
        $this->db->order_by('tgl_surat', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(max(1, (int) $limit), max(0, (int) $offset));

        return $this->db->get()->result();
    }

    /**
     * Jumlah baris untuk filter yang sama dengan api_daftar().
     *
     * @param  int         $tahun
     * @param  string|null $q
     * @param  string|null $arsip
     * @param  string|null $disposisi
     * @return int
     */
    public function api_count($tahun, $q = NULL, $arsip = NULL, $disposisi = NULL)
    {
        $this->_api_query($tahun, $q, $arsip, $disposisi);

        return $this->db->count_all_results();
    }

    /**
     * Jumlah seluruh surat masuk pada tahun tertentu.
     */
    public function count_tahun($tahun)
    {
        $this->db->from('surmas');
        $this->db->where('YEAR(tgl_surat)', $tahun);
        return $this->db->count_all_results();
    }

    /**
     * Set status surat (mis. 'diarsipkan').
     */
    public function set_status($id, $status)
    {
        return $this->update($id, array('status' => $status));
    }

    /**
     * Nomor agenda surat masuk berikutnya.
     * Format: {no-urut}/SM/PA.Kp/{Tahun}
     * No. urut diambil dari nilai tertinggi yang pernah tersimpan pada
     * tahun berjalan, lalu ditambah 1.
     *
     * @param int|null $tahun  Tahun acuan (default: tahun berjalan)
     * @return string
     */
    public function next_no_agenda($tahun = NULL)
    {
        $tahun = $tahun ? (int) $tahun : (int) date('Y');

        // Ambil kolom no_agenda tahun terkait
        $this->db->select('no_agenda');
        $this->db->where('YEAR(tgl_diterima)', $tahun);
        $this->db->order_by('id', 'DESC');
        $rows = $this->db->get('surmas')->result();

        $max = 0;
        foreach ($rows as $r) {
            // Ambil angka pertama sebelum '/' sebagai nomor urut
            if (preg_match('/^\s*(\d+)/', (string) $r->no_agenda, $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return ($max + 1) . '/SM/PA.Kp/' . $tahun;
    }
}

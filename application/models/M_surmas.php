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

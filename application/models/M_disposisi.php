<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * M_disposisi - model tabel `disposisi`
 */
class M_disposisi extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'disposisi';
        $this->primary_key = 'id';
    }

    /**
     * Simpan disposisi (bisa banyak penerima sekaligus).
     *
     * @param int   $surmas_id
     * @param array $penerima  array of ['kepada'=>, 'kategori'=>, 'nip_tujuan'=>]
     * @param string $dari     nip pengirim
     * @param string $instruksi
     * @param string $catatan
     */
    public function simpan_batch($surmas_id, array $penerima, $dari, $instruksi = NULL, $catatan = NULL)
    {
        $now = date('Y-m-d H:i:s');
        $rows = array();
        foreach ($penerima as $p) {
            $rows[] = array(
                'surmas_id'  => (int) $surmas_id,
                'kepada'     => $p['kepada'],
                'kategori'   => $p['kategori'],
                'nip_tujuan' => isset($p['nip_tujuan']) ? $p['nip_tujuan'] : NULL,
                'instruksi'  => $instruksi,
                'catatan'    => $catatan,
                'dari'       => $dari,
                'tanggal'    => $now,
                'dibaca'     => 0,
            );
        }
        if (empty($rows)) {
            return 0;
        }
        $this->db->insert_batch('disposisi', $rows);
        return count($rows);
    }

    /**
     * Daftar disposisi pada tahun tertentu (join surat masuk & pengirim).
     */
    public function daftar_tahun($tahun)
    {
        $this->db->select('d.*, s.no_surat, s.no_agenda, s.perihal, s.pengirim, s.tgl_surat, s.tgl_diterima, s.pengolah, s.file, u.nama AS pengirim_nama');
        $this->db->from('disposisi d');
        $this->db->join('surmas s', 'd.surmas_id = s.id', 'inner');
        $this->db->join('user u', 'd.dari = u.nip', 'left');
        $this->db->where('YEAR(s.tgl_surat)', $tahun);
        $this->db->order_by('d.tanggal', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Daftar disposisi yang ditujukan kepada user tertentu.
     */
    public function daftar_untuk($nip, $tahun)
    {
        $this->db->select('d.*, s.no_surat, s.no_agenda, s.perihal, s.pengirim, s.tgl_surat, s.tgl_diterima, s.pengolah, s.file, u.nama AS pengirim_nama');
        $this->db->from('disposisi d');
        $this->db->join('surmas s', 'd.surmas_id = s.id', 'inner');
        $this->db->join('user u', 'd.dari = u.nip', 'left');
        $this->db->where('d.nip_tujuan', $nip);
        $this->db->where('YEAR(s.tgl_surat)', $tahun);
        $this->db->order_by('d.tanggal', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Jumlah disposisi yang belum dibaca untuk user tertentu.
     */
    public function count_belum_dibaca($nip)
    {
        $this->db->from('disposisi');
        $this->db->where('nip_tujuan', $nip);
        $this->db->where('dibaca', 0);
        return $this->db->count_all_results();
    }

    /**
     * Tandai disposisi sebagai sudah dibaca.
     */
    public function tandai_dibaca($id)
    {
        return $this->update($id, array('dibaca' => 1));
    }
}

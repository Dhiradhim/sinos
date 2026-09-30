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
}

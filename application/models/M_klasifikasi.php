<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * M_klasifikasi - model tabel referensi klasifikasi surat.
 * Nama tabel di database: `ref_klasifikasi` (kolom: id, kode, nama, uraian).
 */
class M_klasifikasi extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'ref_klasifikasi';
        $this->primary_key = 'id';
    }

    public function get_all($order_by = NULL, $direction = 'ASC')
    {
        $this->db->order_by('kode', 'ASC');
        return $this->db->get('ref_klasifikasi')->result();
    }

    /**
     * Daftar kode/nama untuk keperluan pelaporan.
     */
    public function daftar_kode()
    {
        $this->db->select('kode, nama');
        $this->db->order_by('kode', 'ASC');
        return $this->db->get('ref_klasifikasi')->result();
    }
}

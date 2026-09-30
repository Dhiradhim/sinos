<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * M_user - model tabel `user`
 */
class M_user extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'user';
        $this->primary_key = 'id';
    }

    /**
     * Ambil user berdasarkan NIP/username.
     */
    public function get_by_nip($nip)
    {
        return $this->db->get_where('user', array('nip' => $nip))->row();
    }

    /**
     * Ambil nama user berdasarkan NIP.
     */
    public function get_nama($nip)
    {
        $row = $this->get_by_nip($nip);
        return $row ? $row->nama : NULL;
    }

    /**
     * Daftar user beserta info jabatan.
     */
    public function daftar()
    {
        $this->db->select('u.id, u.nip, u.nama, u.aktif, j.subbag, j.jabatan');
        $this->db->from('user u');
        $this->db->join('jabatan j', 'u.id_jabatan = j.id', 'left');
        $this->db->order_by('u.aktif', 'ASC');
        $this->db->order_by('j.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * User yang berpotensi mengambil nomor surat (aktif = 0).
     */
    public function pengambil_nomor()
    {
        $this->db->select('nip, nama');
        $this->db->where('aktif', 0);
        $this->db->order_by('id_jabatan', 'ASC');
        return $this->db->get('user')->result();
    }
}

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
        $this->db->select('u.id, u.nip, u.nama, u.aktif, u.operator, j.subbag, j.jabatan');
        $this->db->from('user u');
        $this->db->join('jabatan j', 'u.id_jabatan = j.id', 'left');
        $this->db->order_by('u.aktif', 'ASC');
        $this->db->order_by('j.id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Cek apakah NIP tergolong operator surat.
     */
    public function is_operator($nip)
    {
        if (empty($nip)) {
            return FALSE;
        }
        $this->db->select('operator');
        $this->db->where('nip', $nip);
        $row = $this->db->get('user')->row();
        return ($row && (int) $row->operator === 1);
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

    /**
     * Penerima disposisi berupa daftar user AKTIF, dengan label "Jabatan (Nama)".
     * Hanya user dengan aktif = 1 yang ditampilkan.
     *
     * @return array of object {id, nip, nama, jabatan, label}
     */
    public function penerima_aktif()
    {
        $this->db->select('u.id, u.nip, u.nama, j.jabatan, j.subbag');
        $this->db->from('user u');
        $this->db->join('jabatan j', 'u.id_jabatan = j.id', 'left');
        // Pada aplikasi ini, user AKTIF ditandai dengan kolom aktif = 0.
        $this->db->where('u.aktif', 0);
        $this->db->order_by('j.id', 'ASC');
        $this->db->order_by('u.nama', 'ASC');
        $rows = $this->db->get()->result();

        foreach ($rows as $r) {
            $jabatan = ! empty($r->jabatan) ? $r->jabatan : 'Tanpa Jabatan';
            $r->jabatan = $jabatan;
            $r->label = $jabatan . ' (' . $r->nama . ')';
        }
        return $rows;
    }

    /**
     * Penerima disposisi, dikelompokkan berdasarkan kategori jabatan.
     * Kategori: Ketua, Wakil Ketua, Hakim, Para Kepala Sub Bagian,
     *           Para Panitera Muda, dan Arsip (statis).
     *
     * @return array  ['Ketua' => [obj,...], 'Wakil Ketua' => [...], ...]
     */
    public function penerima_disposisi()
    {
        // id_jabatan -> nama kategori
        $map = array(
            2  => 'Ketua',
            3  => 'Wakil Ketua',
            4  => 'Hakim',
            6  => 'Para Panitera Muda',
            7  => 'Para Panitera Muda',
            8  => 'Para Panitera Muda',
            10 => 'Para Kepala Sub Bagian',
            11 => 'Para Kepala Sub Bagian',
            12 => 'Para Kepala Sub Bagian',
        );

        $urutan = array(
            'Ketua',
            'Wakil Ketua',
            'Hakim',
            'Para Kepala Sub Bagian',
            'Para Panitera Muda',
        );

        $this->db->select('u.nip, u.nama, u.id_jabatan, j.jabatan, j.subbag');
        $this->db->from('user u');
        $this->db->join('jabatan j', 'u.id_jabatan = j.id', 'left');
        $this->db->where_in('u.id_jabatan', array_keys($map));
        $this->db->order_by('u.id_jabatan', 'ASC');
        $this->db->order_by('u.nama', 'ASC');
        $rows = $this->db->get()->result();

        $result = array();
        foreach ($urutan as $kategori) {
            $result[$kategori] = array();
        }
        // Arsip selalu tersedia (pilihan statis, tanpa user)
        $result['Arsip'] = array();

        foreach ($rows as $r) {
            $kategori = $map[(int) $r->id_jabatan];
            $result[$kategori][] = $r;
        }

        return $result;
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * M_nosur - model tabel `nosur` (Surat Keluar)
 * Kolom: id, no, kode, no_urut, huruf, kj, nip, tanggal, hal, tujuan, file
 */
class M_nosur extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'nosur';
        $this->primary_key = 'id';
    }

    /**
     * Daftar nomor surat milik satu user pada tahun tertentu.
     */
    public function daftar_user($nip, $tahun)
    {
        $this->db->select('nosur.*, user.nama');
        $this->db->from('nosur');
        $this->db->join('user', 'nosur.nip = user.nip', 'inner');
        $this->db->where('YEAR(nosur.tanggal)', $tahun);
        $this->db->where('user.nip', $nip);
        $this->db->order_by('nosur.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Daftar seluruh nomor surat (admin) pada tahun tertentu.
     */
    public function daftar_tahun($tahun)
    {
        $this->db->select('nosur.*, user.nama');
        $this->db->from('nosur');
        $this->db->join('user', 'nosur.nip = user.nip', 'inner');
        $this->db->where('YEAR(nosur.tanggal)', $tahun);
        $this->db->order_by('nosur.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Nomor urut terakhir untuk penomoran otomatis.
     */
    public function last_no_urut($tahun)
    {
        $this->db->select('no_urut');
        $this->db->where('YEAR(tanggal)', $tahun);
        $this->db->order_by('no_urut', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get('nosur')->row();
        return $row ? (int) $row->no_urut : 0;
    }

    /**
     * Jumlah surat yang belum di-upload berkasnya.
     */
    public function count_belum_upload($nip, $tahun)
    {
        $this->db->from('nosur');
        $this->db->where('nip', $nip);
        $this->db->where('file', '1');
        $this->db->where('hal !=', 'Belum Diambil');
        $this->db->where('YEAR(tanggal)', $tahun);
        return $this->db->count_all_results();
    }

    /**
     * Data pendukung pelaporan.
     */
    public function for_laporan($tabel, $bulan, $tahun, $kode, $tipelaporan)
    {
        if ($tipelaporan === 'surkel') {
            $col_tgl = 'tanggal';
            $this->db->where('hal !=', 'Belum Diambil');
        } else {
            $col_tgl = 'tgl_diterima';
        }
        if ($bulan !== '00') {
            $this->db->where("MONTH($col_tgl)", $bulan);
        }
        $this->db->where("YEAR($col_tgl)", $tahun);
        if ($kode !== 'all') {
            if ($tipelaporan === 'surmas') {
                $this->db->like('kode', $kode);
            } else {
                $this->db->where('kode', $kode);
            }
        }
        $this->db->order_by($col_tgl, 'ASC');
        return $this->db->get($tabel)->result();
    }
}

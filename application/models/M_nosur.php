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
        $this->db->select('nosur.*, u.nama');
        $this->db->from('nosur');
        $this->db->join('pegawai_db.pegawai u', 'nosur.nip = u.nip', 'inner');
        $this->db->where('YEAR(nosur.tanggal)', $tahun);
        $this->db->where('u.nip', $nip);
        $this->db->order_by('nosur.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Daftar seluruh nomor surat (admin) pada tahun tertentu.
     */
    public function daftar_tahun($tahun)
    {
        $this->db->select('nosur.*, u.nama');
        $this->db->from('nosur');
        $this->db->join('pegawai_db.pegawai u', 'nosur.nip = u.nip', 'inner');
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
     * Nomor urut terakhir pada SATU tanggal tertentu.
     *
     * Dipakai penomoran "ambil" dan "sisip": nomor baru melanjutkan nomor
     * terakhir di tanggal itu, bukan nomor terakhir setahun.
     *
     * @param  string $tanggal Y-m-d
     * @return int
     */
    public function last_no_urut_tanggal($tanggal)
    {
        $row = $this->db
            ->select('no_urut')
            ->where('tanggal', $tanggal)
            ->order_by('no_urut', 'DESC')
            ->limit(1)
            ->get('nosur')
            ->row();

        return $row ? (int) $row->no_urut : 0;
    }

    /**
     * Semua nomor yang sudah terpakai pada satu tanggal, dikelompokkan
     * per nomor urut. Hasil: [no_urut => ['', 'A', 'B'], ...]
     *
     * Dipakai untuk menentukan huruf sisip berikutnya.
     *
     * @param  string $tanggal Y-m-d
     * @return array
     */
    public function huruf_terpakai($tanggal)
    {
        $rows = $this->db
            ->select('no_urut, huruf')
            ->where('tanggal', $tanggal)
            ->get('nosur')
            ->result();

        $map = array();
        foreach ($rows as $r) {
            $map[(int) $r->no_urut][] = (string) $r->huruf;
        }
        return $map;
    }

    /**
     * Nomor urut terakhir sebelum (atau sama dengan) tanggal tertentu.
     *
     * Dipakai bila pada tanggal tersebut belum ada nomor sama sekali, sehingga
     * penomoran melanjutkan tanggal terakhir yang sudah terpakai.
     *
     * @param  string $tanggal Y-m-d
     * @return int
     */
    public function last_no_urut_sebelum($tanggal)
    {
        $row = $this->db
            ->select('no_urut')
            ->where('tanggal <=', $tanggal)
            ->order_by('tanggal', 'DESC')
            ->order_by('no_urut', 'DESC')
            ->limit(1)
            ->get('nosur')
            ->row();

        return $row ? (int) $row->no_urut : 0;
    }

    /**
     * Cek apakah kombinasi tanggal + no_urut + huruf sudah ada.
     *
     * @param  string      $tanggal
     * @param  int         $no_urut
     * @param  string|null $huruf
     * @return bool
     */
    public function nomor_terpakai($tanggal, $no_urut, $huruf = NULL)
    {
        $this->db->where('tanggal', $tanggal);
        $this->db->where('no_urut', (int) $no_urut);

        if ($huruf === NULL || $huruf === '') {
            $this->db->group_start();
            $this->db->where('huruf IS NULL', NULL, FALSE);
            $this->db->or_where('huruf', '');
            $this->db->group_end();
        } else {
            $this->db->where('huruf', $huruf);
        }

        return ($this->db->count_all_results('nosur') > 0);
    }

    /**
     * Simpan banyak nomor sekaligus.
     *
     * @param  array $rows
     * @return int jumlah baris tersimpan
     */
    public function insert_batch_nomor(array $rows)
    {
        if (empty($rows)) {
            return 0;
        }
        $this->db->insert_batch('nosur', $rows);
        return count($rows);
    }

    /**
     * Jumlah seluruh nomor surat keluar pada tahun tertentu.
     */
    public function count_tahun($tahun)
    {
        $this->db->from('nosur');
        $this->db->where('YEAR(tanggal)', $tahun);
        return $this->db->count_all_results();
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

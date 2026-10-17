<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * M_user - akses BACA-SAJAR ke data pegawai.
 *
 * Sumber data: schema `pegawai_db` milik aplikasi `pegawai/`.
 * SINOS TIDAK memiliki tabel pegawai dan TIDAK boleh menulis apa pun ke sana.
 *
 * Batas yang ditegakkan GRANT MySQL (bukan sekadar kesepakatan):
 *   - hanya kolom id, nip, nama, aktif, is_operator, id_jabatan yang terbaca;
 *   - kolom `pass` TIDAK dapat dibaca maupun ditulis.
 * Karena itu setiap query di sini WAJIB menyebut kolom secara eksplisit.
 * `SELECT *` akan gagal dengan "SELECT command denied".
 *
 * Untuk perubahan data pegawai (termasuk ganti password), arahkan pengguna
 * ke aplikasi `pegawai/` (lihat config `pegawai_app_url`).
 *
 * Makna kolom `aktif` pada master: 1 = AKTIF (standar).
 */
class M_user extends MY_Model
{
    /** Nama tabel terkualifikasi (schema pegawai_db). */
    const T_PEGAWAI = 'pegawai_db.pegawai';

    /** Nama tabel role terkualifikasi. */
    const T_ROLE = 'pegawai_db.pegawai_role';

    /** Nama tabel jabatan terkualifikasi. */
    const T_JABATAN = 'pegawai_db.ref_jabatan';

    /**
     * Kolom identitas yang boleh dibaca SINOS.
     * Harus sama dengan daftar pada GRANT SELECT.
     */
    const KOLOM = 'p.id, p.nip, p.nama, p.aktif, p.is_operator, p.id_jabatan';

    public function __construct()
    {
        parent::__construct();

        // `table` dipakai oleh MY_Model::get()/insert()/update(). SINOS tidak
        // pernah memanggil operasi tulis, tetapi nilai ini harus benar agar
        // tidak ada query yang jatuh ke schema lokal.
        $this->table = self::T_PEGAWAI;
        $this->primary_key = 'id';
    }

    /**
     * Ambil pegawai berdasarkan NIP/username.
     *
     * @return object|null
     */
    public function get_by_nip($nip)
    {
        return $this->db
            ->select('p.id, p.nip, p.nama, p.aktif, p.is_operator, p.id_jabatan')
            ->from(self::T_PEGAWAI . ' p')
            ->where('p.nip', $nip)
            ->get()
            ->row();
    }

    /**
     * Ambil nama pegawai berdasarkan NIP.
     *
     * @return string|null
     */
    public function get_nama($nip)
    {
        $row = $this->get_by_nip($nip);
        return $row ? $row->nama : NULL;
    }

    /**
     * Daftar kode role pegawai.
     *
     * Dipertahankan karena dipakai untuk kebutuhan internal (mis. audit),
     * meskipun halaman daftar pegawai sudah dipindah ke aplikasi Pegawai.
     *
     * @return array
     */
    public function roles_of($pegawai_id)
    {
        $rows = $this->db
            ->select('role_kode')
            ->where('pegawai_id', $pegawai_id)
            ->get(self::T_ROLE)
            ->result();

        $out = array();
        foreach ($rows as $r) {
            $out[] = $r->role_kode;
        }
        return $out;
    }

    /**
     * Cek apakah NIP tergolong operator surat.
     */
    public function is_operator($nip)
    {
        if (empty($nip)) {
            return FALSE;
        }

        $row = $this->db
            ->select('is_operator')
            ->where('nip', $nip)
            ->get(self::T_PEGAWAI)
            ->row();

        return ($row && (int) $row->is_operator === 1);
    }

    /**
     * Pegawai yang berpotensi mengambil nomor surat.
     *
     * @return array
     */
    public function pengambil_nomor()
    {
        return $this->db
            ->select('nip, nama')
            ->where('aktif', 1)
            ->order_by('id_jabatan', 'ASC')
            ->get(self::T_PEGAWAI)
            ->result();
    }

    /**
     * Penerima disposisi berupa daftar pegawai AKTIF, label "Jabatan (Nama)".
     *
     * @return array of object {id, nip, nama, jabatan, label}
     */
    public function penerima_aktif()
    {
        $this->db->select('p.id, p.nip, p.nama, j.jabatan, j.subbag');
        $this->db->from(self::T_PEGAWAI . ' p');
        $this->db->join(self::T_JABATAN . ' j', 'p.id_jabatan = j.id', 'left');
        $this->db->where('p.aktif', 1);
        $this->db->order_by('j.id', 'ASC');
        $this->db->order_by('p.nama', 'ASC');
        $rows = $this->db->get()->result();

        foreach ($rows as $r) {
            $jabatan = ! empty($r->jabatan) ? $r->jabatan : 'Tanpa Jabatan';
            $r->jabatan = $jabatan;
            $r->label = $jabatan . ' (' . $r->nama . ')';
        }
        return $rows;
    }

    /**
     * Penerima disposisi dikelompokkan berdasarkan kategori jabatan.
     * Kunci array adalah nama kategori, nilainya daftar objek pegawai.
     *
     * @return array
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

        $this->db->select('p.nip, p.nama, p.id_jabatan, j.jabatan, j.subbag');
        $this->db->from(self::T_PEGAWAI . ' p');
        $this->db->join(self::T_JABATAN . ' j', 'p.id_jabatan = j.id', 'left');
        $this->db->where_in('p.id_jabatan', array_keys($map));
        $this->db->order_by('p.id_jabatan', 'ASC');
        $this->db->order_by('p.nama', 'ASC');
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

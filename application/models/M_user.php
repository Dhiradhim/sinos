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

    /** ID jabatan `Administrator` pada `ref_jabatan`. */
    const ID_JABATAN_ADMIN = 1;

    /**
     * ID jabatan `Ketua` pada `ref_jabatan`.
     *
     * Sumber: `ref_jabatan` id 2 (`Ketua`, kode `KPA`) — sama dengan kunci
     * "Ketua" pada config `penandatangan_jabatan`.
     */
    const ID_JABATAN_KETUA = 2;

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
     * Pegawai aktif yang menjabat Ketua Pengadilan Agama.
     *
     * Dipakai lembar disposisi cetak untuk mengisi blok tanda tangan:
     * jabatan "Ketua Pengadilan Agama Kupang" beserta nama pejabatnya.
     *
     * Memakai `id_jabatan` (bukan nama) karena grant SINOS hanya mencakup
     * kolom id/jabatan/subbag/kode pada `ref_jabatan`, dan ID tidak perlu
     * di-escape. Bila ada lebih dari satu baris aktif, yang dipakai adalah
     * yang pertama menurut `id` (stabil).
     *
     * @return object|null {nip, nama, jabatan}
     */
    public function ketua()
    {
        $row = $this->db
            ->select('p.nip, p.nama, j.jabatan')
            ->from(self::T_PEGAWAI . ' p')
            ->join(self::T_JABATAN . ' j', 'p.id_jabatan = j.id', 'left')
            ->where('p.id_jabatan', self::ID_JABATAN_KETUA)
            ->where('p.aktif', 1)
            ->order_by('p.id', 'ASC')
            ->limit(1)
            ->get()
            ->row();

        return $row ? $row : NULL;
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
     * Hanya pegawai AKTIF, dan akun `admin` (jabatan Administrator) dikecualikan
     * karena bukan pegawai yang mengambil nomor surat.
     *
     * @return array of object {nip, nama, jabatan, kode_jabatan, label}
     */
    public function pengambil_nomor()
    {
        // ID jabatan Administrator pada `ref_jabatan`.
        // Memakai ID (bukan nama) karena grant SINOS hanya mencakup kolom
        // id/jabatan/subbag/kode pada ref_jabatan, dan menghindari masalah
        // escaping string pada Query Builder.
        $id_admin = self::ID_JABATAN_ADMIN;

        $this->db->select('p.nip, p.nama, j.jabatan, j.kode AS kode_jabatan');
        $this->db->from(self::T_PEGAWAI . ' p');
        $this->db->join(self::T_JABATAN . ' j', 'p.id_jabatan = j.id', 'left');
        $this->db->where('p.aktif', 1);
        // Kecualikan akun administrator.
        $this->db->where('p.nip !=', 'admin');
        $this->db->where('p.id_jabatan IS NULL OR p.id_jabatan !=', $id_admin, FALSE);
        $this->db->order_by('j.id', 'ASC');
        $this->db->order_by('p.nama', 'ASC');
        $rows = $this->db->get()->result();

        foreach ($rows as $r) {
            $r->label = ! empty($r->jabatan)
                ? $r->jabatan . ' — ' . $r->nama
                : $r->nama;
        }
        return $rows;
    }

    /**
     * Daftar pejabat penanda tangan, dikelompokkan sesuai config
     * `penandatangan_jabatan`.
     *
     * Setiap pegawai aktif pada jabatan tersebut menjadi satu pilihan dengan
     * label "Nama [KODE_JABATAN]", mis. "Dahron, S.Ag., M.S.I. [KPA]".
     *
     * @param  array $grup  ['Ketua' => [2], 'Wakil Ketua' => [3], ...]
     * @return array        [ ['grup' => 'Ketua', 'items' => [obj, ...]], ... ]
     */
    public function penandatangan_grup(array $grup)
    {
        $ids = array();
        foreach ($grup as $list) {
            foreach ((array) $list as $id) {
                $ids[] = (int) $id;
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));

        if (empty($ids)) {
            return array();
        }

        $this->db->select('p.nip, p.nama, p.id_jabatan, j.jabatan, j.kode AS kode_jabatan');
        $this->db->from(self::T_PEGAWAI . ' p');
        $this->db->join(self::T_JABATAN . ' j', 'p.id_jabatan = j.id', 'left');
        $this->db->where('p.aktif', 1);
        $this->db->where_in('p.id_jabatan', $ids);
        $this->db->order_by('j.id', 'ASC');
        $this->db->order_by('p.nama', 'ASC');
        $rows = $this->db->get()->result();

        // Kelompokkan per id_jabatan.
        $per_jabatan = array();
        foreach ($rows as $r) {
            $r->label = label_penandatangan($r->nama, $r->kode_jabatan);
            $per_jabatan[(int) $r->id_jabatan][] = $r;
        }

        // Susun ulang mengikuti urutan grup pada config.
        $hasil = array();
        foreach ($grup as $nama_grup => $list_id) {
            $items = array();
            foreach ((array) $list_id as $id) {
                if (! empty($per_jabatan[(int) $id])) {
                    foreach ($per_jabatan[(int) $id] as $row) {
                        $items[] = $row;
                    }
                }
            }
            if (! empty($items)) {
                $hasil[] = array('grup' => $nama_grup, 'items' => $items);
            }
        }
        return $hasil;
    }

    /**
     * Penerima disposisi berupa daftar pegawai AKTIF, label "Jabatan (Nama)".
     *
     * Akun administrator DIKECUALIKAN (sama seperti pengambil_nomor()): akun
     * `admin` maupun pegawai dengan jabatan `Administrator` bukan penerima
     * disposisi, sehingga tidak boleh muncul pada dropdown "Disposisi Kepada"
     * maupun daftar pegawai master.
     *
     * @return array of object {id, nip, nama, jabatan, label}
     */
    public function penerima_aktif()
    {
        $id_admin = self::ID_JABATAN_ADMIN;

        $this->db->select('p.id, p.nip, p.nama, j.jabatan, j.subbag');
        $this->db->from(self::T_PEGAWAI . ' p');
        $this->db->join(self::T_JABATAN . ' j', 'p.id_jabatan = j.id', 'left');
        $this->db->where('p.aktif', 1);
        // Kecualikan akun administrator (nip `admin` dan jabatan Administrator).
        $this->db->where('p.nip !=', 'admin');
        $this->db->where('p.id_jabatan IS NULL OR p.id_jabatan !=', $id_admin, FALSE);
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

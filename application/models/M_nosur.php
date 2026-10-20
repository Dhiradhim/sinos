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
     * Query dasar daftar nomor surat untuk API baca (paginasi server-side).
     *
     * MENGULANG bentuk query `daftar_user()` / `daftar_tahun()` apa adanya
     * (join inner ke pegawai_db.pegawai). Bedanya: LIMIT/OFFSET, pencarian
     * `q` di server, dan pengurutan tanggal/no_urut/huruf yang diterapkan
     * oleh `api_daftar()`.
     *
     * Method ini BARU; `daftar_user()` dan `daftar_tahun()` tidak diubah.
     *
     * @param  int         $tahun
     * @param  string|null $nip  NULL = seluruh pegawai (tampilan admin)
     * @param  string|null $q
     * @return void
     */
    private function _api_query($tahun, $nip = NULL, $q = NULL)
    {
        $this->db->select('nosur.*, u.nama');
        $this->db->from('nosur');
        $this->db->join('pegawai_db.pegawai u', 'nosur.nip = u.nip', 'inner');
        $this->db->where('YEAR(nosur.tanggal)', (int) $tahun);

        if ($nip !== NULL && $nip !== '') {
            $this->db->where('nosur.nip', $nip);
        }

        if ($q !== NULL && $q !== '') {
            $this->db->group_start();
            $this->db->like('nosur.no', $q);
            $this->db->or_like('nosur.hal', $q);
            $this->db->or_like('nosur.tujuan', $q);
            $this->db->or_like('u.nama', $q);
            $this->db->group_end();
        }
    }

    /**
     * Satu halaman daftar nomor surat (API).
     *
     * @param  int         $tahun
     * @param  string|null $nip    NULL = semua pegawai
     * @param  string|null $q
     * @param  int         $limit
     * @param  int         $offset
     * @return array of object
     */
    public function api_daftar($tahun, $nip = NULL, $q = NULL, $limit = 25, $offset = 0)
    {
        $this->_api_query($tahun, $nip, $q);

        /**
         * Urutan daftar: TANGGAL → NO_URUT → HURUF.
         *
         * Aturan huruf: entri tanpa huruf lebih dulu, lalu A, B, C, dst.
         * `huruf` disimpan sebagai VARCHAR dan nilai kosong bisa berupa NULL
         * maupun '' (dua-duanya berarti "tanpa huruf"), sehingga pengurutan
         * alfabet biasa akan menempatkan '' sebelum 'A' — tetapi NULL selalu
         * dianggap paling kecil oleh MySQL. Ekspresi `COALESCE(huruf, '')`
         * menyamakan keduanya agar '' (tanpa huruf) benar-benar di depan.
         *
         * Tanggal DESC: nomor terbaru di atas, sesuai kebiasaan daftar surat.
         * no_urut DESC mengikuti tanggal agar deret terbaru tampil lebih dulu;
         * huruf ASC agar 1500 → 1500A → 1500B dalam satu tanggal.
         *
         * `id` ditambahkan sebagai pemecah seri terakhir supaya paginasi
         * deterministik (tanpa ini, dua baris identik dapat berpindah halaman).
         */
        $this->db->order_by('nosur.tanggal', 'DESC');
        $this->db->order_by('nosur.no_urut', 'DESC');
        $this->db->order_by("COALESCE(nosur.huruf, '')", 'ASC', FALSE);
        $this->db->order_by('nosur.id', 'ASC');
        $this->db->limit(max(1, (int) $limit), max(0, (int) $offset));

        return $this->db->get()->result();
    }

    /**
     * Jumlah baris untuk filter yang sama dengan api_daftar().
     *
     * @param  int         $tahun
     * @param  string|null $nip
     * @param  string|null $q
     * @return int
     */
    public function api_count($tahun, $nip = NULL, $q = NULL)
    {
        $this->_api_query($tahun, $nip, $q);

        return $this->db->count_all_results();
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
     * Cek apakah kombinasi no_urut + huruf sudah dipakai.
     *
     * Nomor urut surat keluar bersifat GLOBAL per tahun, bukan per tanggal.
     * Pembandingnya karena itu TIDAK menyertakan `tanggal`: nomor 1500 yang
     * sudah dipakai pada 20 Oktober tetap menghalangi 1500 pada 30 September
     * (tanggal mundur), sehingga bentrok tidak pernah terjadi.
     *
     * Sebelumnya fungsi ini memfilter `tanggal`, akibatnya sisip nomor pada
     * tanggal mundur selalu lolos di nomor urut yang sudah terpakai di tanggal
     * lain — menghasilkan nomor ganda (mis. 1500 pada dua tanggal berbeda)
     * alih-alih memakai huruf berikutnya (1500A).
     *
     * @param  int         $no_urut
     * @param  string|null $huruf
     * @param  int|null    $tahun   Batasi ke satu tahun (NULL = semua tahun)
     * @return bool
     */
    public function nomor_terpakai($no_urut, $huruf = NULL, $tahun = NULL)
    {
        $this->db->where('no_urut', (int) $no_urut);

        if ($tahun !== NULL) {
            $this->db->where('YEAR(tanggal)', (int) $tahun);
        }

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

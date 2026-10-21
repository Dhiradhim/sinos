<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * M_nosur - model tabel `nosur` (Surat Keluar)
 * Kolom: id, no, kode, no_urut, huruf, nip, penandatangan_nip,
 *        penandatangan_nama, kj, kode_penetapan, tanggal, hal, tujuan, file,
 *        jumlah
 *
 * PENOMORAN (lihat nomor_berikut())
 *
 * Dua mode, sengaja dibedakan karena aturannya memang berbeda:
 *
 *   AMBIL (hari ini)
 *     nomor urut tertinggi tahun itu + 1, tanpa huruf    (1500 → 1501)
 *
 *   SISIP (tanggal mundur)
 *     1. ambil nomor TERAKHIR yang sudah diambil pada tanggal
 *        <= tanggal sisip (tanggal terbaru, lalu id terbesar);
 *     2. pakai nomor urut baris itu;
 *     3. naikkan hurufnya: tanpa huruf → A, A → B, …, Z → AA, AA → AB, …
 *
 *     Contoh: sisip 30 Sep 2026, nomor terakhir s.d. 30 Sep = 100
 *             → 100A; bila 100A sudah ada → 100B.
 *
 * Karena huruf selalu dinaikkan, nomor sisipan tidak pernah mendahului surat
 * aslinya pada tanggal tersebut (100 → 100A → 100B).
 *
 * Alokasi nomor WAJIB dibungkus `dengan_kunci_penomoran()` agar dua request
 * bersamaan tidak memperoleh nomor yang sama (lihat method tersebut).
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
         * Urutan daftar: TANGGAL → NO_URUT → HURUF, semuanya menurun.
         *
         * Contoh hasil pada satu tanggal (persis seperti tampilan yang
         * diminta):
         *
         *   1500      (tanpa huruf — paling atas)
         *   1449B
         *   1449A
         *   1449      (tanpa huruf — paling bawah)
         *
         * Mengapa `huruf` DESC memberi urutan itu: kolom `huruf` menyimpan
         * akhiran nomor, dan akhiran tanpa huruf bernilai '' atau NULL —
         * keduanya dianggap paling kecil oleh MySQL. Dengan DESC, huruf
         * terbesar tampil lebih dulu ('B' > 'A' > '') sehingga entri tanpa
         * huruf otomatis berada di PALING BAWAH untuk nomor urut yang sama.
         *
         * `CHAR_LENGTH(huruf) DESC` WAJIB ada sebelum `huruf DESC`: secara
         * leksikal 'Z' > 'AA', padahal urutan penomoran menempatkan 'AA'
         * SETELAH 'Z'. Mengurutkan yang lebih panjang lebih dulu membuat
         * 'AB' > 'AA' > 'Z' > 'A' > '' — sesuai deret huruf_naik().
         *
         * Tanggal DESC: nomor terbaru di atas, sesuai kebiasaan daftar surat.
         *
         * `id` ditambahkan sebagai pemecah seri terakhir supaya paginasi
         * deterministik (tanpa ini, dua baris identik dapat berpindah halaman).
         */
        $this->db->order_by('nosur.tanggal', 'DESC');
        $this->db->order_by('nosur.no_urut', 'DESC');
        $this->db->order_by('CHAR_LENGTH(COALESCE(nosur.huruf, \'\'))', 'DESC', FALSE);
        $this->db->order_by('nosur.huruf', 'DESC');
        $this->db->order_by('nosur.id', 'DESC');
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
     * Batas atas tanggal yang dianggap "sudah terjadi".
     *
     * Padanan legacy `tanggal <= $tgl_hari_ini`: baris bertanggal MASA DEPAN
     * (salah input) tidak boleh ikut menentukan deret nomor hari ini. Untuk
     * tahun yang sudah lewat dipakai 31 Desember tahun itu; untuk tahun
     * berjalan (dan tahun mendatang) dipakai tanggal hari ini.
     *
     * @param  int $tahun
     * @return string Y-m-d
     */
    protected function _batas_tanggal($tahun)
    {
        $tahun   = (int) $tahun;
        $sekarang = (int) date('Y');

        if ($tahun < $sekarang) {
            return $tahun . '-12-31';
        }

        return date('Y-m-d');
    }

    /**
     * Nomor urut terakhir untuk penomoran otomatis.
     *
     * @param  int         $tahun
     * @param  string|null $batas Bila diisi, hanya baris tanggal <= $batas
     */
    public function last_no_urut($tahun, $batas = NULL)
    {
        $this->db->select('no_urut');
        $this->db->where('YEAR(tanggal)', $tahun);
        if ($batas !== NULL) {
            $this->db->where('tanggal <=', $batas);
        }
        $this->db->order_by('no_urut', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get('nosur')->row();
        return $row ? (int) $row->no_urut : 0;
    }

    /**
     * Tanggal terakhir yang sudah tercatat pada satu tahun.
     *
     * Dipakai untuk menentukan apakah sebuah tanggal sisip berada di UJUNG
     * deret (tidak ada nomor bertanggal setelahnya) atau di TENGAH riwayat.
     * Pada ujung deret nomor tetap melanjutkan deret tahun; di tengah riwayat
     * nomor harus "dipinjam" dari nomor yang berlaku pada tanggal itu.
     *
     * @param  int         $tahun
     * @param  string|null $batas Bila diisi, hanya baris tanggal <= $batas
     * @return string|null Y-m-d, atau NULL bila tahun itu belum punya nomor
     */
    public function tanggal_terakhir($tahun, $batas = NULL)
    {
        $this->db->select('tanggal');
        $this->db->where('YEAR(tanggal)', (int) $tahun);
        if ($batas !== NULL) {
            $this->db->where('tanggal <=', $batas);
        }
        $this->db->order_by('tanggal', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get('nosur')->row();

        return $row ? (string) $row->tanggal : NULL;
    }

    /**
     * Baris nomor TERAKHIR yang sudah diambil pada tanggal <= $tanggal.
     *
     * Inilah acuan penomoran sisip: nomor pada baris ini yang dinaikkan
     * hurufnya. "Terakhir" diurutkan menurut tanggal terbaru, lalu `id`
     * terbesar pada tanggal tersebut — sehingga bila satu tanggal punya
     * beberapa nomor (mis. 100, 100A, 100B), yang terambil adalah 100B.
     *
     * @param  string $tanggal Y-m-d (tanggal sisip)
     * @param  int    $tahun
     * @return object|null {id, no_urut, huruf, tanggal}
     */
    public function nomor_terakhir_sampai($tanggal, $tahun)
    {
        return $this->db
            ->select('id, no_urut, huruf, tanggal')
            ->where('tanggal >=', (int) $tahun . '-01-01')
            ->where('tanggal <=', $tanggal)
            ->order_by('tanggal', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('nosur')
            ->row();
    }

    /**
     * Nomor urut tertinggi yang berlaku sampai tanggal tertentu.
     *
     * STATUS: tidak lagi dipakai penomoran (lihat nomor_terakhir_sampai(),
     * yang juga memperhitungkan huruf). Dipertahankan sebagai util baca.
     *
     * @param  string $tanggal Y-m-d
     * @param  int    $tahun
     * @return int 0 bila belum ada nomor pada/selum tanggal itu
     */
    public function no_urut_tertinggi_sampai($tanggal, $tahun)
    {
        $row = $this->db
            ->select('no_urut')
            ->where('tanggal >=', (int) $tahun . '-01-01')
            ->where('tanggal <=', $tanggal)
            ->order_by('no_urut', 'DESC')
            ->limit(1)
            ->get('nosur')
            ->row();

        return $row ? (int) $row->no_urut : 0;
    }

    /**
     * Huruf yang sudah terpakai untuk satu nomor urut pada satu tahun.
     *
     * Hasil: ['', 'A', 'B', ...] — '' berarti bentuk tanpa huruf sudah
     * dipakai. Dipakai untuk audit penomoran sisip: huruf baru adalah huruf
     * pertama yang belum ada di daftar ini.
     *
     * @param  int $no_urut
     * @param  int $tahun
     * @return array
     */
    public function huruf_terpakai_nomor($no_urut, $tahun)
    {
        $rows = $this->db
            ->select('huruf')
            ->where('no_urut', (int) $no_urut)
            ->where('tanggal >=', (int) $tahun . '-01-01')
            ->where('tanggal <=', (int) $tahun . '-12-31')
            ->order_by('huruf', 'ASC')
            ->get('nosur')
            ->result();

        $out = array();
        foreach ($rows as $r) {
            $out[] = (string) $r->huruf;
        }

        return $out;
    }

    /**
     * Nomor urut terakhir pada SATU tanggal tertentu.
     *
     * STATUS: tidak lagi dipakai penomoran. Sempat menjadi titik awal
     * "ambil"/"sisip", tetapi nomor urut bersifat global per tahun sehingga
     * titik awal harus `last_no_urut()` / `no_urut_tertinggi_sampai()`.
     * Dipertahankan sebagai util baca.
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
     * STATUS: tidak lagi dipakai penomoran (lihat nomor_berikut()). Sempat
     * menjadi cadangan titik awal, tetapi hanya melihat `no_urut` baris
     * terakhir sehingga dapat menunjuk nomor yang tidak berlaku pada tanggal
     * itu. Dipertahankan sebagai util baca.
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
     * Tentukan nomor urut + huruf untuk sebuah tanggal surat.
     *
     * Inti aturan penomoran, dinyatakan di satu tempat agar controller dan
     * API tidak berbeda. Perilakunya bergantung pada JENIS pengambilan.
     *
     * MODE 'ambil' (nomor baru hari ini)
     *   Nomor urut tertinggi pada tahun itu + 1, tanpa huruf.
     *   Contoh: terakhir 1500 → 1501.
     *
     * MODE 'sisip' (menyisipkan nomor pada tanggal mundur)
     *   Langkah persis seperti aturan yang diminta:
     *
     *     1. Cari nomor TERAKHIR yang sudah diambil pada tanggal
     *        <= tanggal sisip (tahun yang sama). "Terakhir" = urutan
     *        tanggal terbaru, lalu id terbesar pada tanggal itu.
     *     2. Pakai nomor urut dari baris tersebut.
     *     3. Tambahkan huruf berikutnya pada nomor itu:
     *          tanpa huruf → A, A → B, …, Z → AA, AA → AB, …, ZZ habis.
     *
     *   Contoh: sisip pada 30 Sep 2026, nomor terakhir s.d. 30 Sep adalah
     *   100 (atau 100B) → hasilnya 100A (atau 100C).
     *
     *   Bila pada/selum tanggal itu belum ada nomor sama sekali, dipakai
     *   nomor 1 tanpa huruf.
     *
     * Bila huruf sudah mencapai 'ZZ' (batas kolom varchar(2)), pencarian
     * berlanjut ke nomor urut berikutnya mulai dari 'A' agar kombinasi tidak
     * pernah berulang.
     *
     * @param  string $tanggal Y-m-d (tanggal surat yang diminta)
     * @param  int    $tahun
     * @param  string $mode    'sisip' (default) atau 'ambil'
     * @return array{no_urut:int,huruf:string,mode:string,acuan:string|null,dasar:int|null,terpakai:array}
     */
    public function nomor_berikut($tanggal, $tahun, $mode = 'sisip')
    {
        $tanggal = (string) $tanggal;
        $tahun   = (int) $tahun;
        $batas   = $this->_batas_tanggal($tahun);

        // ---------------------------------------------------------- ambil
        if ($mode !== 'sisip') {
            $tertinggi = $this->last_no_urut($tahun, $batas);

            return array(
                'no_urut'  => $tertinggi + 1,
                'huruf'    => '',
                'mode'     => $tertinggi === 0 ? 'awal' : 'urut',
                'acuan'    => $this->tanggal_terakhir($tahun, $batas),
                'dasar'    => $tertinggi === 0 ? NULL : $tertinggi,
                'terpakai' => array(),
            );
        }

        // ---------------------------------------------------------- sisip
        // (1) Nomor terakhir pada tanggal <= tanggal sisip.
        $terakhir = $this->nomor_terakhir_sampai($tanggal, $tahun);

        // (2) Belum ada nomor pada/selum tanggal itu → nomor pertama.
        if (! $terakhir) {
            return array(
                'no_urut'  => 1,
                'huruf'    => '',
                'mode'     => 'awal',
                'acuan'    => NULL,
                'dasar'    => NULL,
                'terpakai' => array(),
            );
        }

        $dasar = (int) $terakhir->no_urut;
        $huruf = (string) $terakhir->huruf;

        // (3) Tambahkan huruf berikutnya (Z → AA, AA → AB, …). Bila huruf
        //     sudah habis ('ZZ'), pindah ke nomor urut berikutnya mulai 'A'.
        $pengaman = 0;
        while ($pengaman < 100) {
            $baru = huruf_naik($huruf);

            if ($baru !== NULL && ! $this->nomor_terpakai($dasar, $baru, $tahun)) {
                return array(
                    'no_urut'  => $dasar,
                    'huruf'    => $baru,
                    'mode'     => 'sisip',
                    'acuan'    => (string) $terakhir->tanggal,
                    'dasar'    => $dasar,
                    'terpakai' => $this->huruf_terpakai_nomor($dasar, $tahun),
                );
            }

            // Kombinasi sudah dipakai (mis. ada celah data) atau huruf habis:
            // coba huruf setelahnya; bila tidak ada, naik nomor urut.
            if ($baru !== NULL) {
                $huruf = $baru;
            } else {
                $dasar++;
                $huruf = '';
            }
            $pengaman++;
        }

        // Pengaman terakhir: deret tahun + 1 tanpa huruf.
        $tertinggi = $this->last_no_urut($tahun, $batas);
        return array(
            'no_urut'  => $tertinggi + 1,
            'huruf'    => '',
            'mode'     => 'sisip',
            'acuan'    => (string) $terakhir->tanggal,
            'dasar'    => $tertinggi,
            'terpakai' => array(),
        );
    }

    /**
     * Jalankan blok kerja di bawah kunci penomoran per tahun.
     *
     * MASALAH YANG DICEGAH: alokasi nomor adalah baca-lalu-tulis (baca nomor
     * terakhir, hitung nomor berikutnya, simpan). Dua request yang berjalan
     * hampir bersamaan dapat membaca nomor terakhir yang SAMA lalu menyimpan
     * nomor yang sama. Data lama menunjukkan hal ini memang pernah terjadi
     * (ada beberapa tanggal dengan kombinasi no_urut+huruf ganda).
     *
     * SOLUSI: MySQL named lock per tahun (`GET_LOCK`). Kunci berlaku pada
     * level koneksi, sehingga request lain menunggu sampai request pertama
     * selesai menyimpan. Tanpa izin khusus dan otomatis dilepas saat koneksi
     * tertutup, jadi tidak ada kunci "menggantung".
     *
     * Bila GET_LOCK tidak tersedia/gagal, blok tetap dijalankan tanpa kunci
     * (perilaku lama) agar penomoran tidak pernah mati total.
     *
     * @param  int      $tahun
     * @param  callable $kerja  Menerima argumen $terkunci (bool)
     * @return mixed    Nilai kembalian $kerja()
     */
    public function dengan_kunci_penomoran($tahun, callable $kerja)
    {
        $nama    = 'sinos_nomor_' . (int) $tahun;
        $terkunci = FALSE;

        $hasil = $this->db->query('SELECT GET_LOCK(?, 10) AS ok', array($nama));
        if ($hasil) {
            $row      = $hasil->row();
            $terkunci = ($row && (int) $row->ok === 1);
        }

        try {
            return $kerja($terkunci);
        } finally {
            if ($terkunci) {
                $this->db->query('SELECT RELEASE_LOCK(?)', array($nama));
            }
        }
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

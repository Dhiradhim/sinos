<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Surat_keluar - modul Surat Keluar (tabel `nosur`)
 * Menangani: ambil nomor, sisip nomor, daftar, edit, upload berkas.
 */
class Surat_keluar extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('M_nosur', 'M_user', 'M_klasifikasi'));
    }

    /**
     * Bangun kode klasifikasi dari komponen kp/ks/kd1/kd2/kd3.
     * Mengikuti logika aplikasi lama.
     */
    private function build_kode($kp, $ks, $kd1, $kd2, $kd3)
    {
        $kd2 = ($kd2 === '' || $kd2 === NULL) ? '-' : $kd2;
        $kd3 = ($kd3 === '' || $kd3 === NULL) ? '-' : $kd3;

        if ($kp === '-') {
            if ($kd2 === '-') {
                return $ks . $kd1;
            } elseif ($kd3 === '-') {
                return $ks . $kd1 . '.' . $kd2;
            }
            return $ks . $kd1 . '.' . $kd2 . '.' . $kd3;
        }

        if ($kd2 === '-') {
            return $kp . '.' . $ks . $kd1;
        } elseif ($kd3 === '-') {
            return $kp . '.' . $ks . $kd1 . '.' . $kd2;
        }
        return $kp . '.' . $ks . $kd1 . '.' . $kd2 . '.' . $kd3;
    }

    /**
     * Data pendukung form ambil/sisip nomor.
     *
     * @return array
     */
    private function data_form_nomor()
    {
        $grup_penandatangan = $this->config->item('penandatangan_jabatan');
        if (empty($grup_penandatangan)) {
            $grup_penandatangan = array();
        }

        return array(
            'pengambil'      => $this->M_user->pengambil_nomor(),
            'penandatangan'  => $this->M_user->penandatangan_grup($grup_penandatangan),
            'klasifikasi'    => $this->M_klasifikasi->get_all(),
            'kode_penetapan' => $this->config->item('kode_penetapan'),
            'max_jumlah'     => (int) $this->config->item('surat_keluar_max_jumlah'),
            'instansi_kode'  => 'W23-A1',
        );
    }

    /**
     * Form ambil nomor surat.
     */
    public function ambil()
    {
        $tahun = date('Y');

        // Peringatan bila masih ada berkas yang belum diunggah
        $belum = $this->M_nosur->count_belum_upload($this->user['nip'], $tahun);
        if ($belum > 0 && ! $this->user['is_admin']) {
            $this->flash('warning', 'Mohon upload softcopy nomor surat sebelumnya!');
            redirect('surat-keluar/daftar?tahun=' . $tahun);
            return;
        }

        $data = $this->data_form_nomor();
        $data['tahun'] = $tahun;

        $this->render('surat_keluar/ambil', $data, array(
            'title'    => 'Ambil Nomor Surat',
            'subtitle' => 'Buat nomor surat keluar baru.',
            'active'   => 'surat-keluar',
        ));
    }

    /**
     * Validasi input form nomor surat.
     *
     * @param  bool $butuh_tanggal TRUE untuk form sisip
     * @return array [string|null $error, array $input]
     */
    private function validasi_input_nomor($butuh_tanggal = FALSE)
    {
        $input = array(
            'nip'              => trim((string) $this->input->post('nip')),
            'penandatangan_nip' => trim((string) $this->input->post('penandatangan_nip')),
            'kode_penetapan'   => trim((string) $this->input->post('kode_penetapan')),
            'ks'               => trim((string) $this->input->post('ks')),
            'hal'              => trim((string) $this->input->post('hal')),
            'tujuan'           => trim((string) $this->input->post('tujuan')),
            // Field jumlah tidak lagi ditampilkan di form; selalu 1.
            'jumlah'           => 1,
            'tanggal'          => trim((string) $this->input->post('tanggal')),
        );

        if ($input['nip'] === '') {
            return array('Pengambil nomor wajib dipilih.', $input);
        }
        if ($input['penandatangan_nip'] === '') {
            return array('Penandatangan surat wajib dipilih.', $input);
        }
        if ($input['ks'] === '') {
            return array('Kode surat wajib dipilih.', $input);
        }
        if ($input['hal'] === '') {
            return array('Perihal wajib diisi.', $input);
        }
        if ($input['tujuan'] === '') {
            return array('Tujuan wajib diisi.', $input);
        }
        if ($butuh_tanggal) {
            if ($input['tanggal'] === '' || strtotime($input['tanggal']) === FALSE) {
                return array('Tanggal surat wajib diisi dengan format yang benar.', $input);
            }
            if (strtotime($input['tanggal']) > strtotime(date('Y-m-d'))) {
                return array('Sisip nomor hanya untuk tanggal mundur (tidak boleh melebihi hari ini).', $input);
            }
        }

        return array(NULL, $input);
    }

    /**
     * Ambil data penandatangan dari NIP (nama + kode jabatan).
     *
     * @param  string $nip
     * @return array|null ['nama' => ..., 'label' => ..., 'kode' => ...]
     */
    private function ambil_penandatangan($nip)
    {
        if ($nip === '') {
            return NULL;
        }

        $row = $this->db
            ->select('p.nip, p.nama, j.kode AS kode_jabatan, j.jabatan')
            ->from('pegawai_db.pegawai p')
            ->join('pegawai_db.ref_jabatan j', 'p.id_jabatan = j.id', 'left')
            ->where('p.nip', $nip)
            ->where('p.aktif', 1)
            ->get()
            ->row();

        if (! $row) {
            return NULL;
        }

        return array(
            'nip'   => $row->nip,
            'nama'  => $row->nama,
            'kode'  => (string) $row->kode_jabatan,
            'label' => label_penandatangan($row->nama, $row->kode_jabatan),
        );
    }

    /**
     * Simpan nomor surat baru.
     *
     * Nomor melanjutkan nomor terakhir pada tanggal hari ini. Bila ada lebih
     * dari satu nomor yang diambil, nomor berikutnya tetap memakai nomor urut
     * yang sama dengan akhiran huruf A, B, C, dst. agar tidak duplikat.
     */
    public function ambil_simpan()
    {
        list($error, $input) = $this->validasi_input_nomor(FALSE);

        if ($error !== NULL) {
            $this->flash('error', $error);
            redirect('surat-keluar/ambil');
            return;
        }

        $penandatangan = $this->ambil_penandatangan($input['penandatangan_nip']);
        if (! $penandatangan) {
            $this->flash('error', 'Penandatangan tidak ditemukan atau tidak aktif.');
            redirect('surat-keluar/ambil');
            return;
        }

        $tanggal = date('Y-m-d');
        $tahun   = date('Y', strtotime($tanggal));
        $bulan   = getRomawi(date('n', strtotime($tanggal)));
        $kode    = $input['ks'];
        $kj      = $penandatangan['kode'];

        // Lanjutkan nomor terakhir pada tanggal tersebut.
        $dasar = $this->M_nosur->last_no_urut_tanggal($tanggal);

        if ($dasar === 0) {
            // Belum ada nomor hari ini: lanjutkan dari tanggal terakhir.
            $dasar = $this->M_nosur->last_no_urut_sebelum($tanggal);
        }

        $no_urut = $dasar + 1;

        $terpakai = $this->M_nosur->huruf_terpakai($tanggal);
        $dipakai  = isset($terpakai[$no_urut]) ? $terpakai[$no_urut] : array();

        $rows = array();
        $daftar_no = array();

        for ($i = 0; $i < $input['jumlah']; $i++) {
            // Nomor pertama tanpa huruf, berikutnya A, B, C, ...
            $index = $i;
            $huruf = huruf_sisip($index);

            // Hindari bentrok dengan nomor yang sudah ada.
            $pengaman = 0;
            while ($this->M_nosur->nomor_terpakai($tanggal, $no_urut, $huruf) && $pengaman < 200) {
                $index++;
                $huruf = huruf_sisip($index);
                $pengaman++;
            }

            $no = format_no_surat(
                $no_urut,
                $huruf,
                $penandatangan['kode'],
                $this->config->item('instansi_kode') ?: 'W23-A1',
                $input['kode_penetapan'],
                $kode,
                $bulan,
                $tahun
            );

            $rows[] = array(
                'no'                 => $no,
                'kode'               => $kode,
                'no_urut'            => $no_urut,
                'huruf'              => $huruf,
                'nip'                => $input['nip'],
                'penandatangan_nip'  => $penandatangan['nip'],
                'penandatangan_nama' => $penandatangan['label'],
                'kj'                 => $kj,
                'kode_penetapan'     => $input['kode_penetapan'],
                'tanggal'            => $tanggal,
                'hal'                => $input['hal'],
                'tujuan'             => $input['tujuan'],
                'jumlah'             => $input['jumlah'],
            );

            $daftar_no[] = $no;
        }

        $this->M_nosur->insert_batch_nomor($rows);

        $pesan = count($daftar_no) === 1
            ? 'Nomor surat dibuat: ' . $daftar_no[0]
            : count($daftar_no) . ' nomor surat dibuat: ' . implode(', ', $daftar_no);

        $this->flash('success', $pesan . ' | Perihal: ' . $input['hal']);

        redirect($this->user['is_admin']
            ? 'surat-keluar/daftar-semua?tahun=' . $tahun
            : 'surat-keluar/daftar?tahun=' . $tahun);
    }

    /**
     * Form sisip nomor surat (menyisipkan nomor berdasarkan tanggal tertentu).
     */
    public function sisip()
    {
        $data = $this->data_form_nomor();
        $data['tanggal_hari_ini'] = date('Y-m-d');

        $this->render('surat_keluar/sisip', $data, array(
            'title'    => 'Sisip Nomor Surat',
            'subtitle' => 'Menyisipkan nomor surat pada tanggal mundur.',
            'active'   => 'surat-keluar',
        ));
    }

    /**
     * Simpan sisip nomor surat.
     *
     * Aturan:
     *   - Tanggal wajib tanggal mundur (tidak boleh melebihi hari ini).
     *   - Nomor melanjutkan nomor terakhir pada tanggal tersebut.
     *   - Bila nomor tersebut sudah terpakai, ditambahkan huruf A, B, C, dst.
     *     agar tidak terjadi duplikasi.
     */
    public function sisip_simpan()
    {
        list($error, $input) = $this->validasi_input_nomor(TRUE);

        if ($error !== NULL) {
            $this->flash('error', $error);
            redirect('surat-keluar/sisip');
            return;
        }

        $penandatangan = $this->ambil_penandatangan($input['penandatangan_nip']);
        if (! $penandatangan) {
            $this->flash('error', 'Penandatangan tidak ditemukan atau tidak aktif.');
            redirect('surat-keluar/sisip');
            return;
        }

        $tanggal = $input['tanggal'];
        $ts      = strtotime($tanggal);
        $tahun   = date('Y', $ts);
        $bulan   = getRomawi(date('n', $ts));
        $kode    = $input['ks'];
        $kj      = $penandatangan['kode'];

        // Nomor terakhir pada tanggal tersebut; bila belum ada, lanjutkan dari
        // tanggal terakhir sebelum tanggal sisip.
        $dasar = $this->M_nosur->last_no_urut_tanggal($tanggal);
        if ($dasar === 0) {
            $dasar = $this->M_nosur->last_no_urut_sebelum($tanggal);
        }

        $no_urut = $dasar + 1;

        $rows      = array();
        $daftar_no = array();

        for ($i = 0; $i < $input['jumlah']; $i++) {
            $index = $i;
            $huruf = huruf_sisip($index);

            // Bila nomor urut ini sudah dipakai pada tanggal tsb, beri huruf
            // berikutnya sampai menemukan yang kosong.
            $pengaman = 0;
            while ($this->M_nosur->nomor_terpakai($tanggal, $no_urut, $huruf) && $pengaman < 200) {
                $index++;
                $huruf = huruf_sisip($index);
                $pengaman++;
            }

            $no = format_no_surat(
                $no_urut,
                $huruf,
                $penandatangan['kode'],
                $this->config->item('instansi_kode') ?: 'W23-A1',
                $input['kode_penetapan'],
                $kode,
                $bulan,
                $tahun
            );

            $rows[] = array(
                'no'                 => $no,
                'kode'               => $kode,
                'no_urut'            => $no_urut,
                'huruf'              => $huruf,
                'nip'                => $input['nip'],
                'penandatangan_nip'  => $penandatangan['nip'],
                'penandatangan_nama' => $penandatangan['label'],
                'kj'                 => $kj,
                'kode_penetapan'     => $input['kode_penetapan'],
                'tanggal'            => $tanggal,
                'hal'                => $input['hal'],
                'tujuan'             => $input['tujuan'],
                'jumlah'             => $input['jumlah'],
            );

            $daftar_no[] = $no;
        }

        $this->M_nosur->insert_batch_nomor($rows);

        $pesan = count($daftar_no) === 1
            ? 'Nomor surat disisipkan: ' . $daftar_no[0]
            : count($daftar_no) . ' nomor surat disisipkan: ' . implode(', ', $daftar_no);

        $this->flash('success', $pesan);

        redirect($this->user['is_admin']
            ? 'surat-keluar/daftar-semua?tahun=' . $tahun
            : 'surat-keluar/daftar?tahun=' . $tahun);
    }

    /**
     * Daftar nomor surat milik user yang login.
     */
    public function daftar()
    {
        $tahun = (int) $this->input->get('tahun');
        $tahun = $tahun ? $tahun : date('Y');

        $data = array(
            'tahun'         => $tahun,
            'tahun_list'    => tahun_tersedia(),
            'use_datatables' => TRUE,
            'rows'          => $this->M_nosur->daftar_user($this->user['nip'], $tahun),
        );

        $this->render('surat_keluar/daftar', $data, array(
            'title'    => 'Daftar Nomor Surat',
            'subtitle' => 'Nomor surat milik saya tahun ' . $tahun,
            'active'   => 'surat-keluar',
        ));
    }

    /**
     * Daftar seluruh nomor surat (admin).
     */
    public function daftar_semua()
    {
        $tahun = (int) $this->input->get('tahun');
        $tahun = $tahun ? $tahun : date('Y');

        $data = array(
            'tahun'          => $tahun,
            'tahun_list'     => tahun_tersedia(),
            'use_datatables' => TRUE,
            'rows'           => $this->M_nosur->daftar_tahun($tahun),
        );

        $this->render('surat_keluar/daftar_all', $data, array(
            'title'    => 'Daftar Surat Keluar',
            'subtitle' => 'Seluruh nomor surat tahun ' . $tahun,
            'active'   => 'surat-keluar',
        ));
    }

    /**
     * Form edit nomor surat.
     */
    public function edit($id)
    {
        $row = $this->M_nosur->get($id);
        if (! $row) {
            show_404();
        }

        $data = $this->data_form_nomor();
        $data['row'] = $row;
        $data['nilai'] = array(
            'nip'               => $row->nip,
            'penandatangan_nip' => $row->penandatangan_nip,
            'kode_penetapan'    => $row->kode_penetapan,
            'ks'                => $row->kode,
            'hal'               => $row->hal,
            'tujuan'            => $row->tujuan,
            'jumlah'            => 1,
        );

        $this->render('surat_keluar/edit', $data, array(
            'title'    => 'Edit Nomor Surat',
            'subtitle' => $row->no,
            'active'   => 'surat-keluar',
        ));
    }

    /**
     * Simpan perubahan nomor surat.
     */
    public function update($id)
    {
        $row = $this->M_nosur->get($id);
        if (! $row) {
            show_404();
        }

        $tanggal = $this->input->post('tanggal');
        $ts      = strtotime($tanggal);
        $no_urut = (int) $row->no_urut;
        $huruf   = (string) $row->huruf;
        $ks      = trim((string) $this->input->post('ks'));
        $kode_penetapan = trim((string) $this->input->post('kode_penetapan'));

        // Penandatangan bisa diubah; simpan snapshot nama + kode jabatan.
        $penandatangan = $this->ambil_penandatangan(trim((string) $this->input->post('penandatangan_nip')));
        if (! $penandatangan) {
            $this->flash('error', 'Penandatangan tidak ditemukan atau tidak aktif.');
            redirect('surat-keluar/edit/' . $id);
            return;
        }

        $bulan = getRomawi(date('n', $ts));
        $tahun = date('Y', $ts);
        $no    = format_no_surat(
            $no_urut,
            $huruf,
            $penandatangan['kode'],
            $this->config->item('instansi_kode') ?: 'W23-A1',
            $kode_penetapan,
            $ks,
            $bulan,
            $tahun
        );

        $this->M_nosur->update($id, array(
            'nip'                => $this->input->post('nip'),
            'penandatangan_nip'  => $penandatangan['nip'],
            'penandatangan_nama' => $penandatangan['label'],
            'no'                 => $no,
            'kode'               => $ks,
            'no_urut'            => $no_urut,
            'huruf'              => $huruf,
            'kode_penetapan'     => $kode_penetapan,
            'hal'                => $this->input->post('hal'),
            'kj'                 => $penandatangan['kode'],
            'tujuan'             => $this->input->post('tujuan'),
            'tanggal'            => $tanggal,
        ));

        $this->flash('success', 'Data nomor surat berhasil diperbarui.');
        redirect($this->user['is_admin'] ? 'surat-keluar/daftar-semua?tahun=' . $tahun : 'surat-keluar/daftar?tahun=' . $tahun);
    }

    /**
     * Form upload berkas surat.
     */
    public function upload($id)
    {
        $row = $this->M_nosur->get($id);
        if (! $row) {
            show_404();
        }

        $this->render('surat_keluar/upload', array('row' => $row), array(
            'title'    => 'Upload Berkas',
            'subtitle' => $row->no,
            'active'   => 'surat-keluar',
        ));
    }

    /**
     * Proses upload berkas PDF.
     */
    public function upload_simpan()
    {
        $id  = $this->input->post('id_surat');
        $row = $this->M_nosur->get($id);
        if (! $row) {
            show_404();
        }

        $this->load->library('upload', array(
            'upload_path'   => FCPATH . 'file',
            'allowed_types' => 'pdf',
            'max_size'      => 10240,
        ));

        if (! $this->upload->do_upload('file')) {
            $this->flash('error', 'Gagal upload: ' . $this->upload->display_errors('', ''));
            redirect('surat-keluar/upload/' . $id);
            return;
        }

        $info = $this->upload->data();
        $tahun = date('Y');
        $suffix = $row->huruf ? $row->huruf : '';
        $new_name = $tahun . '_' . $row->no_urut . $suffix . '_file' . $info['file_ext'];
        rename($info['full_path'], FCPATH . 'file/' . $new_name);

        $this->M_nosur->update($id, array('file' => 'file/' . $new_name));

        $this->flash('success', 'Berkas berhasil diunggah.');
        redirect('dashboard');
    }
}

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

        $data = array(
            'pengambil' => $this->M_user->pengambil_nomor(),
            'klasifikasi' => $this->M_klasifikasi->get_all(),
            'tahun' => $tahun,
        );

        $this->render('surat_keluar/ambil', $data, array(
            'title'    => 'Ambil Nomor Surat',
            'subtitle' => 'Buat nomor surat keluar baru.',
            'active'   => 'surat-keluar',
        ));
    }

    /**
     * Simpan nomor surat baru.
     */
    public function ambil_simpan()
    {
        $nip_x = $this->user['is_admin'] ? $this->input->post('nip') : $this->user['nip'];
        $kj    = $this->input->post('kj');
        $kp    = $this->input->post('kp');
        $ks    = $this->input->post('ks');
        $kd1   = $this->input->post('kd1');
        $kd2   = $this->input->post('kd2');
        $kd3   = $this->input->post('kd3');
        $hal   = $this->input->post('hal');
        $tujuan = $this->input->post('tujuan');

        $tahun     = date('Y');
        $no_urut   = $this->M_nosur->last_no_urut($tahun) + 1;
        $kode      = $this->build_kode($kp, $ks, $kd1, $kd2, $kd3);
        $bulan     = getRomawi(date('n'));
        $no        = $no_urut . '/' . $kj . '.W23-A1/' . $kode . '/' . $bulan . '/' . $tahun;
        $tanggal   = date('Y-m-d');

        $this->M_nosur->insert(array(
            'no'       => $no,
            'kode'     => $ks,
            'no_urut'  => $no_urut,
            'nip'      => $nip_x,
            'kj'       => $kj,
            'tanggal'  => $tanggal,
            'hal'      => $hal,
            'tujuan'   => $tujuan,
        ));

        $this->flash('success', 'Nomor surat dibuat: ' . $no . ' | Perihal: ' . $hal);

        if ($this->user['is_admin']) {
            redirect('surat-keluar/daftar-semua?tahun=' . $tahun);
        } else {
            redirect('surat-keluar/daftar?tahun=' . $tahun);
        }
    }

    /**
     * Form sisip nomor surat (menyisipkan nomor berdasarkan tanggal tertentu).
     */
    public function sisip()
    {
        $data = array(
            'pengambil'   => $this->M_user->pengambil_nomor(),
            'klasifikasi' => $this->M_klasifikasi->get_all(),
        );

        $this->render('surat_keluar/sisip', $data, array(
            'title'    => 'Sisip Nomor Surat',
            'subtitle' => 'Menyisipkan nomor surat pada tanggal tertentu.',
            'active'   => 'surat-keluar',
        ));
    }

    /**
     * Simpan sisip nomor surat.
     */
    public function sisip_simpan()
    {
        $nip_x   = $this->user['is_admin'] ? $this->input->post('nip') : $this->user['nip'];
        $kj      = $this->input->post('kj');
        $kp      = $this->input->post('kp');
        $ks      = $this->input->post('ks');
        $kd1     = $this->input->post('kd1');
        $kd2     = $this->input->post('kd2');
        $kd3     = $this->input->post('kd3');
        $tanggal = $this->input->post('tanggal');
        $hal     = $this->input->post('hal');
        $tujuan  = $this->input->post('tujuan');

        $ts      = strtotime($tanggal);
        $tahun   = date('Y', $ts);
        $bulan   = getRomawi(date('n', $ts));
        $kode    = $this->build_kode($kp, $ks, $kd1, $kd2, $kd3);

        // Cari nomor urut pada tanggal yang sama, jika tidak ada ambil terakhir sebelum tanggal tersebut
        $this->db->select('no_urut');
        $this->db->where('tanggal', $tanggal);
        $this->db->order_by('no_urut', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get('nosur')->row();

        if ($row) {
            $no_urut = (int) $row->no_urut + 1;
        } else {
            $this->db->select('no_urut');
            $this->db->where('tanggal <=', $tanggal);
            $this->db->order_by('tanggal', 'DESC');
            $this->db->order_by('id', 'DESC');
            $this->db->limit(1);
            $prev = $this->db->get('nosur')->row();
            $no_urut = $prev ? ((int) $prev->no_urut + 1) : 1;
        }

        $no = $no_urut . '/' . $kj . '.W23-A1/' . $kode . '/' . $bulan . '/' . $tahun;

        $this->M_nosur->insert(array(
            'no'      => $no,
            'kode'    => $ks,
            'no_urut' => $no_urut,
            'nip'     => $nip_x,
            'kj'      => $kj,
            'tanggal' => $tanggal,
            'hal'     => $hal,
            'tujuan'  => $tujuan,
        ));

        $this->flash('success', 'Nomor surat disisipkan: ' . $no);
        redirect($this->user['is_admin'] ? 'surat-keluar/daftar-semua?tahun=' . $tahun : 'surat-keluar/daftar?tahun=' . $tahun);
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

        $data = array(
            'row'         => $row,
            'pengambil'   => $this->M_user->pengambil_nomor(),
            'klasifikasi' => $this->M_klasifikasi->get_all(),
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
        $no_urut = $this->input->post('no_urut');
        $huruf   = $this->input->post('huruf');
        $kj      = $this->input->post('kj');
        $kp      = $this->input->post('kp');
        $ks      = $this->input->post('ks');
        $kode    = $this->build_kode($kp, $ks, $this->input->post('kd1'), $this->input->post('kd2'), $this->input->post('kd3'));

        $bulan = getRomawi(date('n', $ts));
        $tahun = date('Y', $ts);
        $no    = $no_urut . $huruf . '/' . $kj . '.W23-A1/' . $kode . '/' . $bulan . '/' . $tahun;

        $this->M_nosur->update($id, array(
            'nip'     => $this->input->post('nip'),
            'no'      => $no,
            'kode'    => $ks,
            'no_urut' => $no_urut,
            'huruf'   => $huruf,
            'hal'     => $this->input->post('hal'),
            'kj'      => $kj,
            'tujuan'  => $this->input->post('tujuan'),
            'tanggal' => $tanggal,
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

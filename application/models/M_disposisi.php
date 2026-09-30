<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * M_disposisi - model tabel `disposisi`
 */
class M_disposisi extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'disposisi';
        $this->primary_key = 'id';
    }

    /**
     * Simpan disposisi (bisa banyak penerima sekaligus).
     *
     * @param int   $surmas_id
     * @param array $penerima  array of ['kepada'=>, 'kategori'=>, 'nip_tujuan'=>]
     * @param string $dari     nip pengirim
     * @param string $instruksi
     * @param string $catatan
     */
    public function simpan_batch($surmas_id, array $penerima, $dari, $instruksi = NULL, $catatan = NULL)
    {
        $now = date('Y-m-d H:i:s');
        $rows = array();
        foreach ($penerima as $p) {
            $rows[] = array(
                'surmas_id'  => (int) $surmas_id,
                'kepada'     => $p['kepada'],
                'kategori'   => $p['kategori'],
                'nip_tujuan' => isset($p['nip_tujuan']) ? $p['nip_tujuan'] : NULL,
                'instruksi'  => $instruksi,
                'catatan'    => $catatan,
                'dari'       => $dari,
                'tanggal'    => $now,
                'dibaca'     => 0,
            );
        }
        if (empty($rows)) {
            return 0;
        }
        $this->db->insert_batch('disposisi', $rows);
        return count($rows);
    }

    /**
     * Cek apakah NIP tergolong operator surat (kolom `operator` = 1).
     */
    public function is_operator_nip($nip)
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
     * Posisi disposisi TERAKHIR untuk tiap surat.
     * Mengembalikan array: surmas_id => object baris disposisi terakhir.
     *
     * @param array $surmas_ids
     * @return array
     */
    public function posisi_terakhir(array $surmas_ids)
    {
        if (empty($surmas_ids)) {
            return array();
        }

        $this->db->select('d.surmas_id, d.kepada, d.kategori, d.nip_tujuan, d.instruksi, d.tanggal, d.dibaca, d.dikembalikan');
        $this->db->from('disposisi d');
        $this->db->where_in('d.surmas_id', $surmas_ids);
        $this->db->order_by('d.tanggal', 'ASC');
        $this->db->order_by('d.id', 'ASC');
        $rows = $this->db->get()->result();

        $latest = array();
        foreach ($rows as $r) {
            $latest[(int) $r->surmas_id] = $r;
        }
        return $latest;
    }

    /**
     * Daftar disposisi pada tahun tertentu (join surat masuk & pengirim).
     */
    public function daftar_tahun($tahun)
    {
        $this->db->select('d.*, s.no_surat, s.no_agenda, s.perihal, s.pengirim, s.tgl_surat, s.tgl_diterima, s.pengolah, s.file, s.status AS status_surat, u.nama AS pengirim_nama');
        $this->db->from('disposisi d');
        $this->db->join('surmas s', 'd.surmas_id = s.id', 'inner');
        $this->db->join('user u', 'd.dari = u.nip', 'left');
        $this->db->where('YEAR(s.tgl_surat)', $tahun);
        $this->db->order_by('d.tanggal', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Cek apakah user berhak mengirim/meneruskan disposisi untuk sebuah surat.
     *  - Operator surat (atau admin) berhak mengirim disposisi awal.
     *  - Penerima disposisi berhak meneruskan disposisi surat tersebut.
     *  - Jika user sudah pernah menerima disposisi surat ini, tetap boleh meneruskan.
     *
     * @param int    $surmas_id
     * @param string $nip
     * @param bool   $is_operator
     * @return bool
     */
    public function can_send($surmas_id, $nip, $is_operator = FALSE)
    {
        if ($is_operator) {
            return TRUE;
        }
        if (empty($nip)) {
            return FALSE;
        }
        $this->db->from('disposisi');
        $this->db->where('surmas_id', $surmas_id);
        $this->db->where('nip_tujuan', $nip);
        return ($this->db->count_all_results() > 0);
    }

    /**
     * Cek apakah user adalah penerima disposisi aktif (belum dikembalikan).
     */
    public function is_recipient($surmas_id, $nip)
    {
        if (empty($nip)) {
            return FALSE;
        }
        $this->db->from('disposisi');
        $this->db->where('surmas_id', $surmas_id);
        $this->db->where('nip_tujuan', $nip);
        $this->db->where('dikembalikan', 0);
        return ($this->db->count_all_results() > 0);
    }

    /**
     * Daftar disposisi yang POSISINYA sedang berada di user tertentu.
     * Hanya menampilkan surat yang disposisi TERAKHIR-nya ditujukan ke user
     * tersebut dan belum dikembalikan ke operator. Bila sudah diteruskan ke
     * orang lain, surat tidak lagi tampil di daftar user ini.
     */
    public function daftar_atas_nama($nip)
    {
        $this->db->select('d.*, s.no_surat, s.no_agenda, s.perihal, s.pengirim, s.tgl_surat, s.tgl_diterima, s.pengolah, s.file, s.status AS status_surat, u.nama AS pengirim_nama');
        $this->db->from('disposisi d');
        $this->db->join('surmas s', 'd.surmas_id = s.id', 'inner');
        $this->db->join('user u', 'd.dari = u.nip', 'left');
        $this->db->where('d.nip_tujuan', $nip);
        $this->db->where('d.dikembalikan', 0);
        // Sembunyikan surat yang sudah diarsipkan
        $this->db->where('s.status !=', 'diarsipkan');
        // Hanya baris disposisi terakhir per surat
        $this->db->where('d.id = (
            SELECT MAX(d2.id) FROM disposisi d2 WHERE d2.surmas_id = d.surmas_id
        )', NULL, FALSE);
        $this->db->order_by('d.tanggal', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Daftar seluruh surat yang sedang didisposisi (belum diarsipkan) untuk
     * dilihat operator surat. Surat yang sudah diarsipkan disembunyikan dari
     * menu Disposisi (pembatalan arsip dilakukan dari daftar surat masuk).
     */
    public function daftar_operator()
    {
        $this->db->select('d.*, s.no_surat, s.no_agenda, s.perihal, s.pengirim, s.tgl_surat, s.tgl_diterima, s.pengolah, s.file, s.status AS status_surat, u.nama AS pengirim_nama');
        $this->db->from('surmas s');
        $this->db->join('disposisi d', 'd.id = (
            SELECT MAX(d2.id) FROM disposisi d2 WHERE d2.surmas_id = s.id
        )', 'inner', FALSE);
        $this->db->join('user u', 'd.dari = u.nip', 'left');
        $this->db->where('s.status', 'didiposisi');
        $this->db->order_by('d.tanggal', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Status disposisi per surat masuk (untuk kolom "Status Disposisi").
     * Menampilkan HANYA penerima disposisi terakhir (posisi disposisi saat ini).
     * Mengembalikan array: surmas_id => object {
     *     kepada, kategori, nip_tujuan, terakhir, instruksi, dibaca, dikembalikan,
     *     status (label + tone)
     * }
     *
     * @param array $surmas_ids
     * @return array
     */
    public function status_by_surmas(array $surmas_ids)
    {
        if (empty($surmas_ids)) {
            return array();
        }

        // Status surat (untuk mendeteksi arsip)
        $this->db->select('id, status');
        $this->db->where_in('id', $surmas_ids);
        $surat_rows = $this->db->get('surmas')->result();
        $status_surat = array();
        foreach ($surat_rows as $sr) {
            $status_surat[(int) $sr->id] = $sr->status;
        }

        $this->db->select('d.surmas_id, d.kepada, d.kategori, d.nip_tujuan, d.instruksi, d.tanggal, d.dibaca, d.dikembalikan');
        $this->db->from('disposisi d');
        $this->db->where_in('d.surmas_id', $surmas_ids);
        // Urut naik: entri terakhir per surat = penerima disposisi terbaru
        $this->db->order_by('d.tanggal', 'ASC');
        $this->db->order_by('d.id', 'ASC');
        $rows = $this->db->get()->result();

        // Ambil hanya baris TERAKHIR untuk tiap surat
        $latest = array();
        foreach ($rows as $r) {
            $latest[(int) $r->surmas_id] = $r;
        }

        $out = array();
        foreach ($latest as $sid => $r) {
            $arsip = (isset($status_surat[$sid]) && $status_surat[$sid] === 'diarsipkan');

            if ($arsip) {
                $status = 'Sudah Diarsipkan';
                $tone = 'badge-secondary';
            } elseif ((int) $r->dikembalikan === 1) {
                $status = 'Dikembalikan ke operator';
                $tone = 'badge-default';
            } elseif ($r->nip_tujuan === NULL || $r->kepada === 'Arsip') {
                $status = 'Diarsipkan oleh penerima';
                $tone = 'badge-secondary';
            } elseif ((int) $r->dibaca === 1) {
                $status = 'Sudah dibaca';
                $tone = 'badge-success';
            } else {
                $status = 'Belum dibaca';
                $tone = 'badge-muted';
            }

            // Lokasi surat saat ini (user tempat disposisi berada)
            if ($arsip) {
                $lokasi = 'Arsip';
            } elseif ($r->kepada === 'Arsip' || empty($r->nip_tujuan)) {
                $lokasi = 'Arsip';
            } else {
                $lokasi = $r->kepada;
            }

            $out[$sid] = (object) array(
                'kepada'      => $r->kepada,
                'kategori'    => $r->kategori,
                'nip_tujuan'  => $r->nip_tujuan,
                'terakhir'    => $r->tanggal,
                'instruksi'   => $r->instruksi,
                'dibaca'      => (int) $r->dibaca,
                'dikembalikan' => (int) $r->dikembalikan,
                'diarsipkan'  => $arsip,
                'lokasi'      => $lokasi,
                'status'      => $status,
                'tone'        => $tone,
            );
        }
        return $out;
    }

    /**
     * Jumlah disposisi yang belum dibaca untuk user tertentu.
     */
    public function count_belum_dibaca($nip)
    {
        $this->db->from('disposisi');
        $this->db->where('nip_tujuan', $nip);
        $this->db->where('dibaca', 0);
        return $this->db->count_all_results();
    }

    /**
     * Tandai disposisi sebagai sudah dibaca.
     */
    public function tandai_dibaca($id)
    {
        return $this->update($id, array('dibaca' => 1));
    }

    /**
     * Tandai disposisi sebagai dikembalikan ke operator surat.
     */
    public function kembalikan($id)
    {
        return $this->update($id, array(
            'dikembalikan'     => 1,
            'dibaca'           => 1,
            'tgl_dikembalikan' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Apakah semua disposisi pada sebuah surat sudah dikembalikan.
     */
    public function semua_dikembalikan($surmas_id)
    {
        $this->db->from('disposisi');
        $this->db->where('surmas_id', $surmas_id);
        $total = $this->db->count_all_results();

        if ($total === 0) {
            return FALSE;
        }

        $this->db->from('disposisi');
        $this->db->where('surmas_id', $surmas_id);
        $this->db->where('dikembalikan', 1);
        $kembali = $this->db->count_all_results();

        return ($kembali >= $total);
    }
}

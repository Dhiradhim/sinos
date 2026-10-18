<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API Present Helper
 *
 * Fungsi bantu untuk mengubah nilai tampilan yang saat ini dibangun di view
 * PHP menjadi nilai netral yang dapat dipetakan Astro ke kelas Tailwind.
 *
 * Prinsip (lihat plans/2026-10-17-sinos-astro-preline.md bagian 3.2 dan 5.3):
 *   - JSON TIDAK memuat nama kelas CSS (mis. "badge-success", "bg-sky-50").
 *   - JSON memuat "tone" netral: brand | sky | amber | danger | ok | muted |
 *     neutral | warning | info.
 *   - Status berkas dikirim sebagai enum: none | pending | ready.
 *   - Tanggal dikirim dua bentuk: ISO mentah (untuk sortir/format di Astro)
 *     dan teks Indonesia (untuk paritas dengan view saat ini).
 */

if (! function_exists('api_tone_from_badge')) {
    /**
     * Petakan nama kelas badge v4 menjadi nama tone netral.
     *
     * Dipakai untuk nilai yang dihitung model (mis.
     * M_disposisi::status_by_surmas() mengembalikan 'badge-success') agar API
     * tidak perlu menebak warna di sisi klien.
     *
     * @param  string $badge mis. 'badge-success'
     * @return string
     */
    function api_tone_from_badge($badge)
    {
        $map = array(
            'badge-success'   => 'ok',
            'badge-warning'   => 'warning',
            'badge-danger'    => 'danger',
            'badge-info'      => 'info',
            'badge-primary'   => 'brand',
            'badge-secondary' => 'muted',
            'badge-muted'     => 'muted',
            'badge-outline'   => 'neutral',
            'badge-default'   => 'neutral',
        );

        $key = strtolower(trim((string) $badge));

        return isset($map[$key]) ? $map[$key] : 'neutral';
    }
}

if (! function_exists('api_file_state')) {
    /**
     * Status berkas surat keluar sebagai enum diskret.
     *
     * Aturan mengikuti view (surat_keluar/daftar.php, daftar_all.php,
     * dashboard/index.php):
     *   file === '1'  -> "Belum ada"   => 'pending' (nomor ada, berkas belum)
     *   file kosong   -> "&mdash;"     => 'none'    (tidak ada berkas sama sekali)
     *   selain itu    -> link "Lihat"  => 'ready'
     *
     * @param  string|null $file
     * @return string none|pending|ready
     */
    function api_file_state($file)
    {
        $value = ($file === NULL) ? '' : trim((string) $file);

        if ($value === '1') {
            return 'pending';
        }
        if ($value === '') {
            return 'none';
        }

        return 'ready';
    }

    /**
     * Objek berkas lengkap untuk satu baris.
     *
     * `path` hanya berisi nilai kolom apa adanya (mis. "file/sk/2026_x.pdf");
     * Astro yang membentuk URL (termasuk proxy ber-auth pada Fase 2).
     *
     * @param  string|null $file
     * @return array{state:string,path:string|null,label:string}
     */
    function api_file_meta($file)
    {
        $state = api_file_state($file);
        $path  = ($state === 'ready') ? (string) $file : NULL;

        $label = array(
            'none'    => 'Tidak ada berkas',
            'pending' => 'Belum ada',
            'ready'   => 'Lihat',
        );

        return array(
            'state' => $state,
            'path'  => $path,
            'label' => $label[$state],
        );
    }
}

if (! function_exists('api_tanggal')) {
    /**
     * Tanggal ISO + teks Indonesia.
     *
     * @param  string|null $tanggal Y-m-d
     * @return array{iso:string|null,teks:string}
     */
    function api_tanggal($tanggal)
    {
        $iso = ($tanggal === NULL || $tanggal === '') ? NULL : (string) $tanggal;

        return array(
            'iso'  => $iso,
            'teks' => ($iso === NULL) ? '' : tanggal_indonesia($iso),
        );
    }
}

if (! function_exists('api_datetime')) {
    /**
     * Datetime ISO + teks "d-m-Y H:i" seperti yang dirender view disposisi.
     *
     * @param  string|null $datetime Y-m-d H:i:s
     * @return array{iso:string|null,teks:string}
     */
    function api_datetime($datetime)
    {
        if ($datetime === NULL || $datetime === '') {
            return array('iso' => NULL, 'teks' => '');
        }

        $ts = strtotime((string) $datetime);

        return array(
            'iso'  => (string) $datetime,
            'teks' => ($ts === FALSE) ? '' : date('d-m-Y H:i', $ts),
        );
    }
}

if (! function_exists('api_status_disposisi_surat')) {
    /**
     * Status disposisi untuk kolom "Status & Berkas" di surat_masuk/daftar.php.
     *
     * Sumber utama adalah M_disposisi::status_by_surmas(). Bila surat belum
     * pernah didisposisi, view menampilkan "Belum didisposisi" dengan
     * badge-outline — di sini menjadi tone 'neutral'.
     *
     * @param  object|null $st  Baris dari status_by_surmas()
     * @return array{status:string,tone:string,lokasi:string|null,dibaca:bool,
     *               dikembalikan:bool,diarsipkan:bool,instruksi:string|null}
     */
    function api_status_disposisi_surat($st)
    {
        if (empty($st)) {
            return array(
                'status'       => 'Belum didisposisi',
                'tone'         => 'neutral',
                'lokasi'       => NULL,
                'dibaca'       => FALSE,
                'dikembalikan' => FALSE,
                'diarsipkan'   => FALSE,
                'instruksi'    => NULL,
            );
        }

        return array(
            'status'       => (string) $st->status,
            'tone'         => api_tone_from_badge(isset($st->tone) ? $st->tone : ''),
            'lokasi'       => isset($st->lokasi) ? (string) $st->lokasi : NULL,
            'dibaca'       => ! empty($st->dibaca),
            'dikembalikan' => ! empty($st->dikembalikan),
            'diarsipkan'   => ! empty($st->diarsipkan),
            'instruksi'    => isset($st->instruksi) ? $st->instruksi : NULL,
        );
    }
}

if (! function_exists('api_status_posisi_disposisi')) {
    /**
     * Status baris disposisi di kolom "Instruksi" pada disposisi/index.php.
     *
     * Urutan prioritas mengikuti view:
     *   diarsipkan -> "Sudah diarsipkan" (muted)
     *   dikembalikan=1 -> "Dikembalikan" (ok)
     *   dibaca=1 -> "Dibaca" (neutral)
     *   selain itu -> "Belum dibaca" (warning)
     *
     * @param  object $row baris disposisi (butuh status_surat, dikembalikan, dibaca)
     * @return array{status:string,tone:string}
     */
    function api_status_posisi_disposisi($row)
    {
        $status_surat = isset($row->status_surat) ? (string) $row->status_surat : '';

        if ($status_surat === 'diarsipkan') {
            return array('status' => 'Sudah diarsipkan', 'tone' => 'muted');
        }
        if (! empty($row->dikembalikan)) {
            return array('status' => 'Dikembalikan', 'tone' => 'ok');
        }
        if (! empty($row->dibaca)) {
            return array('status' => 'Dibaca', 'tone' => 'neutral');
        }

        return array('status' => 'Belum dibaca', 'tone' => 'warning');
    }
}

if (! function_exists('api_lokasi_disposisi')) {
    /**
     * Lokasi surat saat ini (penerima terakhir atau "Arsip").
     *
     * Sama dengan logika Disposisi::index().
     *
     * @param  object $row
     * @return string
     */
    function api_lokasi_disposisi($row)
    {
        $kepada = isset($row->kepada) ? (string) $row->kepada : '';
        $nip    = isset($row->nip_tujuan) ? (string) $row->nip_tujuan : '';

        return ($kepada === 'Arsip' || $nip === '') ? 'Arsip' : $kepada;
    }
}

if (! function_exists('api_int_or_null')) {
    /**
     * Cast nilai opsional menjadi int, mempertahankan NULL.
     *
     * @param  mixed $value
     * @return int|null
     */
    function api_int_or_null($value)
    {
        return ($value === NULL || $value === '') ? NULL : (int) $value;
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API Berkas Helper
 *
 * Dua kelompok fungsi:
 *
 * 1. Upload PDF untuk endpoint tulis (Fase 2). Aturan path, tipe, ukuran, dan
 *    pola nama berkas disalin PERSIS dari controller HTML:
 *      Surat_masuk::simpan()/update()   -> file/sm, pdf, 10240 KB
 *      Surat_keluar::upload_simpan()    -> file,    pdf, 10240 KB
 *    Pesan gagal juga sama: 'Gagal upload: ' . display_errors('', '').
 *
 * 2. URL berkas bertanda tangan untuk view HTML dan API:
 *      berkas_url('file/sm/x.pdf') -> http://…/api/v1/berkas/<token>
 *    Dipakai setelah direktori `file/` diblokir langsung di .htaccess
 *    (lihat plans/2026-10-17-sinos-astro-preline.md bagian 4 Fase 2 butir 3).
 *
 * Helper ini tidak menulis ke database.
 */

if (! function_exists('api_upload_pdf')) {
    /**
     * Terima satu berkas PDF dari request multipart, lalu simpan dengan nama
     * yang ditentukan pemanggil.
     *
     * Perilaku yang disengaja (paritas dengan controller HTML):
     *   - `rename()` menimpa berkas tujuan bila namanya sama;
     *   - berkas LAMA TIDAK DIHAPUS saat nama berkas berubah. Controller HTML
     *     hanya mengganti kolom `file` di database, sehingga berkas lama
     *     tertinggal di disk. API mengikuti perilaku itu (lihat laporan Fase 2).
     *
     * @param  string $field        Nama field multipart (biasanya 'file')
     * @param  string $dir_relative Direktori relatif terhadap FCPATH, mis. 'file/sm'
     * @param  string $new_name     Nama berkas tujuan, sudah termasuk ekstensi
     * @return array{ok:bool,missing:bool,error:string,path:string|null,full:string|null}
     */
    function api_upload_pdf($field, $dir_relative, $new_name)
    {
        $CI =& get_instance();

        $hasil = array(
            'ok'      => FALSE,
            'missing' => FALSE,
            'error'   => '',
            'path'    => NULL,
            'full'    => NULL,
        );

        if (empty($_FILES[$field]['name'])) {
            $hasil['missing'] = TRUE;
            return $hasil;
        }

        // `initialize()` dipakai (bukan config pada load) agar konfigurasi
        // benar-benar diterapkan bila library sudah dimuat pada request ini.
        $CI->load->library('upload');
        $CI->upload->initialize(array(
            'upload_path'   => FCPATH . $dir_relative,
            'allowed_types' => 'pdf',
            'max_size'      => 10240,
        ));

        if (! $CI->upload->do_upload($field)) {
            $hasil['error'] = 'Gagal upload: ' . $CI->upload->display_errors('', '');
            return $hasil;
        }

        $info = $CI->upload->data();
        $tujuan = FCPATH . $dir_relative . '/' . $new_name;

        if (! @rename($info['full_path'], $tujuan)) {
            $hasil['error'] = 'Gagal upload: berkas tidak dapat dipindahkan ke direktori tujuan.';
            @unlink($info['full_path']);
            return $hasil;
        }

        $hasil['ok']   = TRUE;
        $hasil['path'] = $dir_relative . '/' . $new_name;
        $hasil['full'] = $tujuan;

        return $hasil;
    }
}

if (! function_exists('api_berkas_token')) {
    /**
     * Token akses berkas (HMAC, berjangka waktu) untuk path relatif.
     *
     * @param  string $relative_path mis. 'file/sm/2026_X.pdf'
     * @param  int    $ttl           Detik (default 1 jam, sama dengan helper token)
     * @return string|null NULL bila path tidak aman
     */
    function api_berkas_token($relative_path, $ttl = 3600)
    {
        if (! function_exists('api_berkas_sign')) {
            get_instance()->load->helper('api_token');
        }

        return api_berkas_sign($relative_path, $ttl);
    }
}

if (! function_exists('berkas_url')) {
    /**
     * URL bertanda tangan untuk sebuah berkas.
     *
     * Dipakai view HTML pengganti `base_url($row->file)` setelah akses
     * langsung ke `file/` diblokir. Mengembalikan '' bila path kosong atau
     * tidak aman, sehingga tautan tidak pernah mengarah ke path mentah.
     *
     * @param  string|null $relative_path
     * @return string
     */
    function berkas_url($relative_path)
    {
        $path = is_string($relative_path) ? trim($relative_path) : '';
        if ($path === '') {
            return '';
        }

        $token = api_berkas_token($path);
        if ($token === NULL) {
            return '';
        }

        return base_url('api/v1/berkas/' . $token);
    }
}

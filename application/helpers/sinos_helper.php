<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * SINOS Helper
 * Kumpulan fungsi bantu yang dipakai lintas controller/view.
 */

if (! function_exists('getRomawi')) {
    /**
     * Konversi angka bulan menjadi angka Romawi.
     */
    function getRomawi($bln)
    {
        $map = array(
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        );
        return isset($map[(int) $bln]) ? $map[(int) $bln] : '';
    }
}

if (! function_exists('tanggal_indonesia')) {
    /**
     * Format tanggal Y-m-d menjadi "d Month Y" versi Indonesia.
     */
    function tanggal_indonesia($tanggal, $with_day = FALSE)
    {
        if (empty($tanggal)) {
            return '';
        }
        $bulan = array(
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );
        $ts  = strtotime($tanggal);
        $out = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
        if ($with_day) {
            $hari = array(
                'Sunday' => 'Minggu',
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu'
            );
            $out = $hari[date('l', $ts)] . ', ' . $out;
        }
        return $out;
    }
}

if (! function_exists('is_admin')) {
    /**
     * Cek apakah user saat ini adalah admin.
     */
    function is_admin()
    {
        $CI = &get_instance();
        return ($CI->session->userdata('nip') === 'admin');
    }
}

if (! function_exists('active_menu')) {
    /**
     * Menghasilkan class Tailwind untuk menu aktif.
     */
    function active_menu($segment, $class = 'bg-slate-800 text-white', $default = 'text-slate-300 hover:bg-slate-800 hover:text-white')
    {
        $CI = &get_instance();
        return ($CI->uri->segment(1) === $segment) ? $class : $default;
    }
}

if (! function_exists('old')) {
    /**
     * Ambil nilai input lama dari flashdata (untuk repopulasi form).
     */
    function old($field, $default = '')
    {
        $CI = &get_instance();
        $old = $CI->session->flashdata('old');
        return (is_array($old) && isset($old[$field])) ? $old[$field] : $default;
    }
}

if (! function_exists('tahun_tersedia')) {
    /**
     * Daftar tahun yang dapat dipilih (2024 s.d. tahun berjalan), urut menurun.
     */
    function tahun_tersedia($mulai = 2024)
    {
        $sekarang = (int) date('Y');
        $tahun = array();
        for ($y = $sekarang; $y >= $mulai; $y--) {
            $tahun[] = $y;
        }
        return $tahun;
    }
}

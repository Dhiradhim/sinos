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

if (! function_exists('svg_icon')) {
    /**
     * Mengembalikan markup inline SVG (Lucide-style, stroke-based) berdasarkan nama ikon.
     *
     * @param string $name    Nama ikon (mis. 'dashboard', 'mail', 'trash')
     * @param string $classes Class Tailwind tambahan untuk elemen svg
     * @param int    $size    Ukuran sisi (px) bila classes tidak mengatur ukuran
     * @return string
     */
    function svg_icon($name, $classes = '', $size = 0)
    {
        static $icons = array(
            'dashboard'      => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
            'menu'           => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
            'mail'           => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
            'mail-open'      => '<path d="M21.2 8.4c.5.38.8.97.8 1.6v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10a2 2 0 0 1 .8-1.6l8-6a2 2 0 0 1 2.4 0Z"/><path d="m22 10-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 10"/>',
            'inbox'          => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
            'send'           => '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/>',
            'archive'        => '<rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>',
            'archive-restore' => '<rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h2"/><path d="M20 8v11a2 2 0 0 1-2 2h-2"/><path d="m9 15 3-3 3 3"/><path d="M12 12v9"/>',
            'edit'           => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
            'trash'          => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
            'eye'            => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
            'upload'         => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
            'download'       => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
            'file'           => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v5h5"/>',
            'file-text'      => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v5h5"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
            'search'         => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
            'chevron-down'   => '<path d="m6 9 6 6 6-6"/>',
            'chevron-right'  => '<path d="m9 18 6-6-6-6"/>',
            'chevron-left'   => '<path d="m15 18-6-6 6-6"/>',
            'chevrons-left'  => '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>',
            'chevrons-right' => '<path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/>',
            'x'              => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
            'plus'           => '<path d="M5 12h14"/><path d="M12 5v14"/>',
            'log-out'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
            'lock'           => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
            'key'            => '<path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/>',
            'calendar'       => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
            'user'           => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'users'          => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'user-plus'      => '<path d="M2 21a8 8 0 0 1 13.292-6"/><circle cx="10" cy="8" r="5"/><path d="M19 16v6"/><path d="M22 19h-6"/>',
            'printer'        => '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v5"/><rect width="12" height="8" x="6" y="14" rx="1"/>',
            'chart'          => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
            'home'           => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
            'alert'          => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>',
            'check'          => '<path d="M20 6 9 17l-5-5"/>',
            'circle-check'   => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
            'info'           => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
            'triangle-alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
            'octagon-x'      => '<path d="m15 9-6 6"/><path d="m9 9 6 6"/><path d="M2.586 16.726A2 2 0 0 1 2 15.312V8.688a2 2 0 0 1 .586-1.414l4.688-4.688A2 2 0 0 1 8.688 2h6.624a2 2 0 0 1 1.414.586l4.688 4.688A2 2 0 0 1 22 8.688v6.624a2 2 0 0 1-.586 1.414l-4.688 4.688a2 2 0 0 1-1.414.586H8.688a2 2 0 0 1-1.414-.586z"/>',
            'hash'           => '<line x1="4" x2="20" y1="9" y2="9"/><line x1="4" x2="20" y1="15" y2="15"/><line x1="10" x2="8" y1="3" y2="21"/><line x1="16" x2="14" y1="3" y2="21"/>',
            'paperclip'      => '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
            'folder'         => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
            'building'       => '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
            'shield'         => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
            'clock'          => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'book'           => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',
            'clipboard'      => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
            'settings'       => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
            'circle-help'    => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
            'ellipsis'       => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
            'arrow-left'     => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
            'arrow-right'    => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
            'refresh'        => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
            'bank'           => '<line x1="3" x2="21" y1="22" y2="22"/><line x1="6" x2="6" y1="18" y2="11"/><line x1="10" x2="10" y1="18" y2="11"/><line x1="14" x2="14" y1="18" y2="11"/><line x1="18" x2="18" y1="18" y2="11"/><polygon points="12 2 20 7 4 7"/>',
            'trending-up'    => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
            'bell'           => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>'
        );

        $name = strtolower(trim((string) $name));
        $body = isset($icons[$name]) ? $icons[$name] : $icons['circle-help'];

        $class_attr = $classes !== '' ? ' class="' . html_escape($classes, FALSE) . '"' : '';
        $size_attr  = ($size > 0) ? ' width="' . (int) $size . '" height="' . (int) $size . '"' : ' width="24" height="24"';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"' . $size_attr . $class_attr . ' aria-hidden="true">'
            . $body . '</svg>';
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

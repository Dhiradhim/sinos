<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Api_Controller.php';

/**
 * Notfound - penangan 404 JSON untuk sisa-sisa frontend HTML yang sudah
 * dihentikan pada Fase 7 (2026-10-18).
 *
 * Controller ini dipakai dua kali:
 *   - `$route['default_controller']` : permintaan ke root aplikasi
 *     (mis. GET /sinos/) tidak lagi merender dashboard HTML.
 *   - `$route['404_override']`       : setiap URI yang tidak cocok dengan
 *     rute mana pun (termasuk rute HTML lama seperti dashboard,
 *     surat-masuk/daftar, laporan) dibalas JSON, BUKAN halaman error PHP
 *     CI3 yang memuat stack trace.
 *
 * PENTING - lokasi berkas: harus di akar `controllers/`, bukan di
 * `controllers/api/`. `404_override` diurai CI3 dengan sscanf '%[^/]/%s'
 * menjadi pasangan class/method, jadi nilai 'api/notfound' akan dibaca
 * sebagai class `Api` method `notfound`, bukan controller di subdirektori.
 *
 * Sengaja TANPA autentikasi: 404 bukan urusan kredensial, dan membalas 401
 * untuk URL yang memang sudah tidak ada akan menyesatkan klien.
 */
class Notfound extends MY_Api_Controller
{
    /**
     * @var bool
     */
    protected $require_auth = FALSE;

    /**
     * Semua segmen URI diterima lalu diabaikan - yang penting responsnya
     * konsisten.
     *
     * @return void
     */
    public function index()
    {
        $this->fail(
            'NOT_FOUND',
            'Endpoint tidak ditemukan. Antarmuka HTML SINOS sudah dihentikan; '
                . 'gunakan frontend Astro (sinos-web) dan API /api/v1/.',
            404
        );
    }
}

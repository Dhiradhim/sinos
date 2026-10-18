<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * MY_Controller
 *
 * STATUS: PENSIUN (Fase 7, 2026-10-18).
 *
 * Kelas ini dulu menjadi basis seluruh controller HTML (Dashboard, Disposisi,
 * Laporan, Surat_keluar, Surat_masuk) dan menyediakan:
 *   - proteksi session + validasi sesi gateway SSO,
 *   - $this->user (nip/nama/is_admin/is_operator),
 *   - render() yang memuat view konten ke dalam layouts/master,
 *   - disposisi_count() dan flash().
 *
 * Seluruh controller HTML sudah DIHAPUS, sehingga tidak ada lagi kelas yang
 * mewarisi MY_Controller:
 *   - controller API memakai MY_Api_Controller, yang sengaja mewarisi
 *     CI_Controller secara langsung (lihat core/MY_Api_Controller.php:22),
 *   - Auth dan Sso mewarisi CI_Controller.
 *
 * render() karena itu tidak pernah dipanggil lagi dan jalur pemuatan view
 * sudah dihapus dari kelas ini. Bila ada kode yang (keliru) membuat instance
 * atau mewarisi kelas ini, respons yang keluar adalah 410 Gone berformat
 * JSON — bukan halaman HTML atau stack trace.
 *
 * Helper session/SSO tidak dipertahankan di sini karena grep membuktikan
 * tidak ada pemanggil yang tersisa. Padanan untuk API ada di
 * MY_Api_Controller::enforce_sso_session_cached().
 */
#[\AllowDynamicProperties]
class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Tidak ada lagi jalur render view. Hentikan request dengan 410.
        $this->gone();
    }

    /**
     * Balas 410 Gone dalam JSON lalu hentikan eksekusi.
     *
     * @return void
     */
    protected function gone()
    {
        if (! headers_sent()) {
            header_remove('Set-Cookie');
            http_response_code(410);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }

        echo json_encode(array(
            'success' => FALSE,
            'data'    => NULL,
            'meta'    => NULL,
            'error'   => array(
                'code'    => 'GONE',
                'message' => 'Antarmuka HTML SINOS sudah dihentikan. '
                    . 'Gunakan frontend Astro (sinos-web) dan API /api/v1/.',
            ),
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        exit;
    }
}

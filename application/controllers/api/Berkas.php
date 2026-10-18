<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Api_Controller.php';

/**
 * API Berkas - stream berkas pengguna dengan token bertanda tangan.
 *
 *   GET api/v1/berkas/<token>
 *
 * Kenapa endpoint ini ada
 * Direktori `file/` menyimpan PDF surat. Setelah `.htaccess` memblokir akses
 * langsung ke `file/` (Fase 2), URL berkas harus melewati endpoint ber-auth
 * agar tidak ada berkas yang dapat diunduh tanpa izin.
 *
 * Token berkas
 * `api_berkas_sign($path, $ttl)` (application/helpers/api_token_helper.php)
 * menandatangani `{typ:'berkas', p:path, iat, exp}` dengan HMAC-SHA256.
 * Token itu sendiri SUDAH cukup untuk mengakses berkas: tidak dapat dipalsukan
 * tanpa `api_secret` dan berumur pendek (default 1 jam). Karena itu endpoint
 * ini menerima salah satu dari:
 *   - token berkas valid pada segmen path, ATAU
 *   - Bearer token sesi valid (mis. Astro yang mem-proxy permintaan).
 * Bearer token tetap diterima agar klien yang sudah login tidak perlu
 * menerbitkan token berkas untuk setiap tampilan.
 *
 * Penjagaan path
 * `api_berkas_verify()` menormalkan path (menolak absolut, backslash, "..",
 * NUL). Endpoint menambahkan pemeriksaan kedua: `realpath()` hasilnya wajib
 * berada di dalam `FCPATH . 'file/'`, sehingga symlink atau karakter aneh
 * tidak dapat keluar dari direktori berkas.
 *
 * Tidak ada HTML di respons; kegagalan memakai envelope JSON standar.
 */
class Berkas extends MY_Api_Controller
{
    /**
     * Endpoint dapat diakses TANPA Bearer token.
     *
     * Nilai ini dibaca MY_Api_Controller::__construct() sebagai nilai
     * properti, sehingga token berkas saja sudah cukup. Method `show()`
     * memverifikasi sendiri (token berkas atau Bearer).
     *
     * @var bool
     */
    protected $require_auth = FALSE;

    /** Direktori akar berkas, relatif terhadap FCPATH. */
    const ROOT = 'file';

    /**
     * GET api/v1/berkas/<token>
     *
     * @param string $token
     */
    public function show($token = '')
    {
        if ($this->input->method(TRUE) !== 'GET') {
            $this->fail('METHOD_NOT_ALLOWED', 'Gunakan metode GET.', 405);
            return;
        }

        $token = is_string($token) ? trim($token) : '';
        if ($token === '') {
            $this->fail('BERKAS_TOKEN_INVALID', 'Token berkas tidak valid atau sudah kedaluwarsa.', 401);
            return;
        }

        $path = api_berkas_verify($token);

        if ($path === NULL) {
            // Token rusak/diubah/kedaluwarsa. Bearer token sesi masih boleh
            // dipakai, tetapi tanpa path tidak ada yang bisa di-stream.
            $this->fail('BERKAS_TOKEN_INVALID', 'Token berkas tidak valid atau sudah kedaluwarsa.', 401);
            return;
        }

        // Path dari token sudah dinormalkan helper; pemeriksaan di bawah
        // adalah lapisan kedua terhadap path di luar akar `file/`.
        if (! $this->dalam_akar($path)) {
            $this->fail('BERKAS_DILARANG', 'Akses berkas di luar direktori yang diizinkan.', 403);
            return;
        }

        // Bearer token sesi bersifat opsional: bila dikirim tetapi tidak
        // valid, permintaan ditolak agar token kedaluwarsa tidak diam-diam
        // diabaikan.
        $bearer = api_token_bearer();
        if ($bearer !== NULL && api_token_verify($bearer) === NULL) {
            $this->fail('UNAUTHENTICATED', 'Sesi tidak valid atau sudah berakhir.', 401);
            return;
        }

        $full = realpath(FCPATH . $path);
        $akar = realpath(FCPATH . self::ROOT);

        if ($full === FALSE || $akar === FALSE) {
            $this->fail('BERKAS_TIDAK_DITEMUKAN', 'Berkas tidak ditemukan.', 404);
            return;
        }

        // realpath() menuntaskan symlink: path final wajib tetap di dalam akar.
        $akar_prefix = rtrim(str_replace('\\', '/', $akar), '/') . '/';
        $full_norm   = str_replace('\\', '/', $full);

        if (strpos($full_norm, $akar_prefix) !== 0) {
            $this->fail('BERKAS_DILARANG', 'Akses berkas di luar direktori yang diizinkan.', 403);
            return;
        }

        if (! is_file($full) || ! is_readable($full)) {
            $this->fail('BERKAS_TIDAK_DITEMUKAN', 'Berkas tidak ditemukan.', 404);
            return;
        }

        $this->stream($full);
    }

    /**
     * Kirim berkas ke klien.
     *
     * MIME selalu `application/pdf`: seluruh berkas pada `file/` adalah PDF
     * (upload dibatasi `allowed_types => pdf`), dan memaksa MIME mencegah
     * browser menafsirkan ulang isi berkas.
     *
     * @param  string $full Path absolut berkas
     * @return void
     */
    protected function stream($full)
    {
        $size = @filesize($full);
        $name = basename($full);

        if (! headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . str_replace('"', '', $name) . '"');
            if ($size !== FALSE) {
                header('Content-Length: ' . $size);
            }
            // Berkas ber-token berumur pendek: cache privat singkat agar
            // pratinjau berulang tidak meminta ulang ke server, tetapi tidak
            // disimpan di cache bersama.
            header('Cache-Control: private, max-age=300');
            header('X-Content-Type-Options: nosniff');
        }

        $this->output->set_content_type('application/pdf');
        $this->output->set_output(file_get_contents($full));
    }

    /**
     * Apakah path relatif berada di dalam direktori akar berkas.
     *
     * Pemeriksaan tekstual (lapisan pertama) sebelum `realpath()`.
     *
     * @param  string $path
     * @return bool
     */
    protected function dalam_akar($path)
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');

        if ($path === '' || strpos($path, '..') !== FALSE) {
            return FALSE;
        }

        return strpos($path, self::ROOT . '/') === 0;
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * MY_Api_Controller
 *
 * Base controller untuk seluruh endpoint `/api/v1/` (frontend headless Astro).
 *
 * Sengaja mewarisi CI_Controller (BUKAN MY_Controller) agar:
 *   - tidak pernah merender view / layout Tailwind,
 *   - tidak pernah melakukan redirect ke halaman login,
 *   - tidak memakai session PHP sebagai sumber identitas.
 *
 * Identitas diambil dari token Bearer stateless (lihat
 * application/helpers/api_token_helper.php).
 *
 * KONTRAK RESPONS (lihat plans/2026-10-17-sinos-astro-preline.md §3.2):
 *   sukses : { "data": ..., "meta": ...|null, "error": null }
 *   gagal  : { "data": null, "meta": null, "error": { code, message, fields? } }
 */
#[\AllowDynamicProperties]
class MY_Api_Controller extends CI_Controller
{
    /**
     * Klaim user hasil verifikasi token.
     *
     * @var array{sub:string,name:string,roles:array,admin:bool,operator:bool,grp:string,iat:int,exp:int}
     */
    protected $user = array();

    /**
     * Apakah SEMUA method pada controller ini wajib membawa Bearer token.
     *
     * Subclass boleh menurunkan menjadi FALSE sebagai NILAI PROPERTI (bukan di
     * dalam method), karena konstruktor induk membaca nilai ini sebelum method
     * apa pun dijalankan. Bila FALSE, method yang tetap butuh login harus
     * memanggil $this->authenticate() sendiri.
     *
     * Contoh: api/auth menurunkan nilai ini karena exchange() bersifat publik,
     * sedangkan me() dan logout() memanggil authenticate() secara eksplisit.
     *
     * @var bool
     */
    protected $require_auth = TRUE;

    /**
     * Penanda bahwa respons gagal sudah disiapkan oleh fail().
     *
     * @var bool
     */
    protected $failed = FALSE;

    /** TTL cache status sesi gateway (detik). */
    const SSO_CACHE_TTL = 60;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('api_token');

        $this->output->set_content_type('application/json');

        // API tidak memakai session PHP. Library session di-autoload secara
        // global (application/config/autoload.php) sehingga CI3 sudah memanggil
        // session_start() sebelum controller ini berjalan. Dua langkah di bawah
        // membuat request API benar-benar bebas cookie:
        //   1. buang header Set-Cookie yang terlanjur disiapkan session library,
        //   2. lepas lock session agar request API tidak saling menunggu.
        // Tidak ada baca/tulis $this->session di seluruh controller API.
        if (! headers_sent()) {
            header_remove('Set-Cookie');
        }
        if (isset($this->session) && is_object($this->session)) {
            $this->release_session();
        }

        if (! $this->require_auth) {
            // Endpoint publik (mis. tukar tiket SSO). Tidak ada klaim user.
            // Method yang tetap butuh login WAJIB memanggil $this->authenticate()
            // sendiri (lihat api/auth::me() dan ::logout()).
            return;
        }

        // Konstruktor tidak dapat "return" untuk membatalkan pemanggilan method,
        // jadi request dihentikan setelah respons 401 disiapkan.
        if (! $this->authenticate()) {
            $this->halt();
        }
    }

    /**
     * Verifikasi Bearer token + status sesi gateway, lalu isi $this->user.
     *
     * Aman dipanggil dari konstruktor maupun dari dalam method. Bila gagal,
     * respons error sudah disiapkan lewat fail(); pemanggil di dalam method
     * WAJIB langsung `return`.
     *
     * @return bool
     */
    protected function authenticate()
    {
        $token  = api_token_bearer();
        $claims = $token ? api_token_verify($token) : NULL;

        if (! $claims) {
            $this->fail('UNAUTHENTICATED', 'Sesi tidak valid atau sudah berakhir.', 401);
            return FALSE;
        }

        $this->user = $claims;

        return $this->enforce_sso_session_cached($claims);
    }

    // ------------------------------------------------------------------
    // Respons
    // ------------------------------------------------------------------

    /**
     * Respons sukses 200 (atau status lain bila diminta).
     *
     * @param  mixed      $data
     * @param  array|null $meta
     * @param  int        $status
     * @return void
     */
    protected function ok($data, $meta = NULL, $status = 200)
    {
        $this->output->set_status_header($status);
        $this->output->set_output($this->envelope($data, $meta, NULL));

        return;
    }

    /**
     * Respons sukses 201 Created.
     *
     * @param  mixed      $data
     * @param  array|null $meta
     * @return void
     */
    protected function created($data, $meta = NULL)
    {
        $this->ok($data, $meta, 201);
    }

    /**
     * Respons gagal.
     *
     * TERMINAL: setelah memanggil fail(), kode pemanggil WAJIB `return`
     * (atau berhenti) agar tidak melanjutkan logika bisnis.
     *
     * @param  string     $code    Kode mesin, mis. VALIDATION_FAILED
     * @param  string     $message Pesan Bahasa Indonesia untuk ditampilkan
     * @param  int        $status  Status HTTP
     * @param  array|null $fields  Error per field (opsional)
     * @return void
     */
    protected function fail($code, $message, $status = 400, $fields = NULL)
    {
        $this->failed = TRUE;

        $error = array(
            'code'    => (string) $code,
            'message' => (string) $message,
        );
        if (! empty($fields)) {
            $error['fields'] = $fields;
        }

        $this->output->set_status_header($status);
        $this->output->set_output($this->envelope(NULL, NULL, $error));

        return;
    }

    /**
     * Bangun envelope JSON seragam.
     *
     * @param  mixed      $data
     * @param  array|null $meta
     * @param  array|null $error
     * @return string
     */
    protected function envelope($data, $meta, $error)
    {
        $payload = array(
            'data'  => $data,
            'meta'  => $meta,
            'error' => $error,
        );

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === FALSE) {
            // Jaga-jaga bila data memuat byte non-UTF8.
            $json = json_encode(array(
                'data'  => NULL,
                'meta'  => NULL,
                'error' => array(
                    'code'    => 'ENCODING_ERROR',
                    'message' => 'Gagal menyusun respons JSON.',
                ),
            ));
        }

        return $json;
    }

    /**
     * Hentikan request API setelah respons gagal disiapkan di konstruktor.
     *
     * Dipakai HANYA dari konstruktor: bila konstruktor berhenti tanpa
     * menghentikan request, CI3 tetap menjalankan method yang diminta.
     *
     * @return void
     */
    protected function halt()
    {
        $this->output->_display();
        exit;
    }

    // ------------------------------------------------------------------
    // Input & paginasi
    // ------------------------------------------------------------------

    /**
     * Baca body request sebagai array.
     *
     * Mendukung JSON (`application/json`) dan form-encoded
     * (`application/x-www-form-urlencoded`), supaya endpoint dapat diuji
     * dengan curl sederhana.
     *
     * @return array
     */
    protected function input_json()
    {
        $raw = (string) $this->input->raw_input_stream;

        if ($raw !== '') {
            $decoded = json_decode($raw, TRUE);
            if (is_array($decoded)) {
                return $decoded;
            }

            // Bukan JSON: coba form-encoded.
            $parsed = array();
            parse_str($raw, $parsed);
            if (is_array($parsed) && ! empty($parsed)) {
                return $parsed;
            }
        }

        // Fallback: $_POST (mis. multipart) lalu query string.
        if (! empty($_POST) && is_array($_POST)) {
            return $_POST;
        }

        return is_array($this->input->get()) ? $this->input->get() : array();
    }

    /**
     * Meta paginasi standar.
     *
     * @param  int $page
     * @param  int $per_page
     * @param  int $total
     * @return array
     */
    protected function paginate($page, $per_page, $total)
    {
        $page     = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);
        $total    = max(0, (int) $total);

        return array(
            'page'      => $page,
            'per_page'  => $per_page,
            'total'     => $total,
            'last_page' => ($total === 0) ? 1 : (int) ceil($total / $per_page),
        );
    }

    // ------------------------------------------------------------------
    // Proteksi sesi gateway (single logout)
    // ------------------------------------------------------------------

    /**
     * Pastikan sesi gateway SSO masih aktif, dengan cache 60 detik.
     *
     * Tanpa cache, setiap request API akan memanggil gateway (latensi +
     * beban). Dengan TTL 60 detik, single logout tetap berlaku maksimum
     * 60 detik setelah pengguna logout di gateway.
     *
     * @param  array $claims
     * @return bool FALSE bila sesi gateway sudah berakhir (respons 401 sudah
     *              disiapkan lewat fail()).
     */
    protected function enforce_sso_session_cached(array $claims)
    {
        $grp = isset($claims['grp']) ? (string) $claims['grp'] : '';

        // Login lokal tanpa grup SSO: tidak ada yang perlu divalidasi.
        if ($grp === '') {
            return TRUE;
        }

        $this->config->load('sso', TRUE);
        if (! $this->config->item('sso_enabled', 'sso')) {
            return TRUE;
        }

        $cache_key = $this->sso_cache_key($grp);

        // Degradasi anggun: bila driver cache tidak tersedia, CI3 memakai
        // adapter "dummy" yang selalu get() = FALSE dan save() = no-op.
        // Artinya status tetap divalidasi ke gateway pada setiap request.
        $cache = $this->sso_cache();
        if ($cache !== NULL && $cache->get($cache_key)) {
            return TRUE;
        }

        $this->load->library('sso_client');
        if (! $this->sso_client->session_alive($grp)) {
            $this->fail('SESSION_REVOKED', 'Sesi gateway sudah berakhir.', 401);
            return FALSE;
        }

        if ($cache !== NULL) {
            $cache->save($cache_key, TRUE, self::SSO_CACHE_TTL);
        }

        return TRUE;
    }

    /**
     * Muat driver cache. NULL bila tidak dapat dimuat (ditolak dengan aman).
     *
     * @return CI_Cache|null
     */
    protected function sso_cache()
    {
        if (isset($this->cache) && is_object($this->cache)) {
            return $this->cache;
        }

        // Adapter file + backup dummy: bila direktori cache tidak dapat
        // ditulis (mis. permission), CI3 otomatis jatuh ke dummy.
        $this->load->driver('cache', array('adapter' => 'file', 'backup' => 'dummy'));

        return isset($this->cache) && is_object($this->cache) ? $this->cache : NULL;
    }

    /**
     * Kunci cache untuk status sesi gateway.
     *
     * Semantiknya `sso_alive:{session_group}`. Nilai grup di-hash agar aman
     * dipakai sebagai nama file (Windows menolak ":" pada nama file) dan agar
     * nilai grup tidak pernah muncul mentah di nama berkas cache.
     *
     * @param  string $grp
     * @return string
     */
    protected function sso_cache_key($grp)
    {
        return 'sso_alive_' . md5((string) $grp);
    }

    /**
     * Lepaskan lock session PHP tanpa menghapus data.
     *
     * @return void
     */
    protected function release_session()
    {
        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}

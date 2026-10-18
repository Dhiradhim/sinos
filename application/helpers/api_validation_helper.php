<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API Validation Helper
 *
 * Satu sumber aturan validasi untuk endpoint tulis `/api/v1/` (Fase 2).
 *
 * Tujuan (lihat plans/2026-10-17-sinos-astro-preline.md bagian 4, Fase 2
 * butir 4): memindahkan aturan `form_validation` ke satu tempat agar API
 * (dan kelak form HTML) memakai pesan yang sama, lalu mengembalikan error
 * per-field sebagai `fields` pada envelope JSON.
 *
 * ATURAN PENTING
 * - Pesan Bahasa Indonesia. Pesan yang sudah ada di controller HTML dipakai
 *   PERSIS (kata per kata), karena klien Astro menampilkannya langsung.
 * - Tidak ada HTML di dalam pesan. `api_apply_rules()` membuang tag hasil
 *   `form_error()` (CI3 membungkusnya dengan <p>…</p>).
 * - Helper ini hanya MENYUSUN aturan; tidak menulis ke database.
 *
 * CATATAN PARITAS DENGAN CONTROLLER HTML
 * `Surat_masuk::simpan()` / `update()` tidak memanggil `form_validation`
 * sama sekali: field wajib hanya ditandai `required` di HTML. Karena itu
 * aturan di sini adalah rekonstruksi dari `required` pada view
 * `surat_masuk/_form_fields.php`, memakai gaya pesan controller.
 *
 * `Surat_keluar::validasi_input_nomor()` memvalidasi manual (bukan
 * form_validation). Pesan di bawah disalin dari method tersebut.
 *
 * Satu aturan TIDAK dapat dinyatakan sebagai rule CI3: "tanggal sisip tidak
 * boleh melebihi hari ini". Aturan itu tetap diperiksa di controller
 * (`api/surat_keluar::sisip()`) agar pesannya sama persis.
 */

if (! function_exists('api_rules_surat_masuk')) {
    /**
     * Aturan validasi surat masuk (create & update).
     *
     * Field mengikuti `surat_masuk/_form_fields.php`. Pada mode edit dokumen
     * tidak wajib (upload opsional), sama seperti view.
     *
     * @param  bool  $is_edit TRUE untuk PUT (berkas opsional)
     * @return array Daftar definisi aturan untuk api_apply_rules()
     */
    function api_rules_surat_masuk($is_edit = FALSE)
    {
        $rules = array(
            array(
                'field'  => 'kode',
                'label'  => 'Kode klasifikasi',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Kode klasifikasi wajib dipilih.'),
            ),
            array(
                'field'  => 'no_surat',
                'label'  => 'Nomor surat',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Nomor surat wajib diisi.'),
            ),
            array(
                'field'  => 'tgl_surat',
                'label'  => 'Tanggal surat',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Tanggal surat wajib diisi.'),
            ),
            array(
                'field'  => 'tgl_diterima',
                'label'  => 'Tanggal diterima',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Tanggal diterima wajib diisi.'),
            ),
            array(
                'field'  => 'pengirim',
                'label'  => 'Pengirim',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Pengirim wajib diisi.'),
            ),
            array(
                'field'  => 'perihal',
                'label'  => 'Perihal',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Perihal wajib diisi.'),
            ),
            array(
                'field'  => 'pengolah',
                'label'  => 'Pengolah',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Pengolah wajib dipilih.'),
            ),
            array(
                'field'  => 'keterangan',
                'label'  => 'Keterangan',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Keterangan wajib diisi.'),
            ),
        );

        // Dokumen wajib hanya pada input baru (pesan dari Surat_masuk::simpan()).
        if (! $is_edit) {
            $rules[] = array(
                'field'  => 'file',
                'label'  => 'Dokumen surat',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Dokumen surat (PDF) wajib diunggah.'),
            );
        }

        return $rules;
    }
}

if (! function_exists('api_rules_surat_keluar_ambil')) {
    /**
     * Aturan validasi "ambil nomor" surat keluar.
     *
     * Disalin dari `Surat_keluar::validasi_input_nomor(FALSE)`.
     * `jumlah` tidak lagi divalidasi karena form tidak menampilkannya
     * (controller selalu memakai 1).
     *
     * @return array
     */
    function api_rules_surat_keluar_ambil()
    {
        return array(
            array(
                'field'  => 'nip',
                'label'  => 'Pengambil nomor',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Pengambil nomor wajib dipilih.'),
            ),
            array(
                'field'  => 'penandatangan_nip',
                'label'  => 'Penandatangan surat',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Penandatangan surat wajib dipilih.'),
            ),
            array(
                'field'  => 'ks',
                'label'  => 'Kode surat',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Kode surat wajib dipilih.'),
            ),
            array(
                'field'  => 'hal',
                'label'  => 'Perihal',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Perihal wajib diisi.'),
            ),
            array(
                'field'  => 'tujuan',
                'label'  => 'Tujuan',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Tujuan wajib diisi.'),
            ),
        );
    }
}

if (! function_exists('api_rules_surat_keluar_sisip')) {
    /**
     * Aturan validasi "sisip nomor" surat keluar.
     *
     * Disalin dari `Surat_keluar::validasi_input_nomor(TRUE)`.
     *
     * Aturan "tanggal tidak boleh melebihi hari ini" TIDAK ada di sini karena
     * tidak dapat dinyatakan sebagai rule CI3; controller memeriksanya
     * setelah validasi ini lolos, dengan pesan yang sama:
     *   "Sisip nomor hanya untuk tanggal mundur (tidak boleh melebihi hari ini)."
     *
     * @return array
     */
    function api_rules_surat_keluar_sisip()
    {
        $rules = api_rules_surat_keluar_ambil();

        $rules[] = array(
            'field'  => 'tanggal',
            'label'  => 'Tanggal surat',
            'rules'  => 'trim|required',
            'errors' => array('required' => 'Tanggal surat wajib diisi dengan format yang benar.'),
        );

        return $rules;
    }
}

if (! function_exists('api_rules_surat_keluar_update')) {
    /**
     * Aturan validasi edit nomor surat (`Surat_keluar::update()`).
     *
     * `Surat_keluar::update()` hanya memeriksa keberadaan penandatangan
     * ("Penandatangan tidak ditemukan atau tidak aktif."). Field lain
     * disimpan apa adanya. API tetap mewajibkan field inti agar PUT parsial
     * tidak menimpa kolom NOT NULL dengan string kosong.
     *
     * @return array
     */
    function api_rules_surat_keluar_update()
    {
        return array(
            array(
                'field'  => 'tanggal',
                'label'  => 'Tanggal surat',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Tanggal surat wajib diisi dengan format yang benar.'),
            ),
            array(
                'field'  => 'ks',
                'label'  => 'Kode surat',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Kode surat wajib dipilih.'),
            ),
            array(
                'field'  => 'penandatangan_nip',
                'label'  => 'Penandatangan surat',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Penandatangan surat wajib dipilih.'),
            ),
            array(
                'field'  => 'nip',
                'label'  => 'Pengambil nomor',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Pengambil nomor wajib dipilih.'),
            ),
            array(
                'field'  => 'hal',
                'label'  => 'Perihal',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Perihal wajib diisi.'),
            ),
            array(
                'field'  => 'tujuan',
                'label'  => 'Tujuan',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Tujuan wajib diisi.'),
            ),
        );
    }
}

if (! function_exists('api_rules_disposisi')) {
    /**
     * Aturan validasi kirim disposisi (`Disposisi::kirim()`).
     *
     * `target` adalah id user (dari M_user::penerima_aktif()) atau literal
     * "Arsip". Keabsahan target diperiksa controller karena bergantung pada
     * daftar penerima aktif di database.
     *
     * @return array
     */
    function api_rules_disposisi()
    {
        return array(
            array(
                'field'  => 'surmas_id',
                'label'  => 'Surat masuk',
                'rules'  => 'trim|required|integer',
                'errors' => array(
                    'required' => 'Surat masuk wajib dipilih.',
                    'integer'  => 'Surat masuk tidak valid.',
                ),
            ),
            array(
                'field'  => 'target',
                'label'  => 'Penerima disposisi',
                'rules'  => 'trim|required',
                'errors' => array('required' => 'Pilih penerima disposisi.'),
            ),
        );
    }
}

if (! function_exists('api_apply_rules')) {
    /**
     * Jalankan daftar aturan dari api_rules_*() terhadap data request.
     *
     * Mengembalikan array error per-field. Array kosong berarti valid.
     * Pesan sudah dibersihkan dari tag HTML (CI3 membungkus dengan <p>).
     *
     * Semua field pada aturan diisi '' bila tidak ada di $data, agar
     * Form_validation::run() tidak mengakses kunci yang tidak ada.
     *
     * @param  array $defs Definisi dari api_rules_*()
     * @param  array $data Data request (JSON / form / multipart)
     * @return array<string,string> field => pesan
     */
    function api_apply_rules(array $defs, array $data)
    {
        $CI =& get_instance();

        // Pastikan nilai awal selalu ada untuk setiap field yang divalidasi.
        foreach ($defs as $def) {
            if (! array_key_exists($def['field'], $data)) {
                $data[$def['field']] = '';
            }
        }

        $CI->form_validation->reset_validation();
        $CI->form_validation->set_data($data);

        foreach ($defs as $def) {
            $CI->form_validation->set_rules(
                $def['field'],
                $def['label'],
                $def['rules'],
                isset($def['errors']) ? $def['errors'] : array()
            );
        }

        $ok = $CI->form_validation->run();

        // reset_validation() di atas menghapus pesan; ambil dari error_array
        // agar tidak bergantung pada status internal setelah run().
        if ($ok) {
            return array();
        }

        $fields = array();
        foreach ($defs as $def) {
            $raw = $CI->form_validation->error($def['field']);
            if ($raw === '') {
                continue;
            }
            $fields[$def['field']] = trim(strip_tags($raw));
        }

        return $fields;
    }
}

if (! function_exists('api_first_error')) {
    /**
     * Pesan pertama dari hasil api_apply_rules(), untuk `error.message`.
     *
     * Urutan mengikuti urutan aturan (sama seperti controller HTML yang
     * berhenti pada kesalahan pertama).
     *
     * @param  array $fields
     * @return string
     */
    function api_first_error(array $fields)
    {
        foreach ($fields as $pesan) {
            if ($pesan !== '') {
                return $pesan;
            }
        }

        return 'Data yang dikirim tidak valid.';
    }
}

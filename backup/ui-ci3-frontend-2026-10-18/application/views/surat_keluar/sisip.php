<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Sisip Nomor Surat</h2>
            <p class="card-description">Sisipkan nomor surat pada tanggal mundur.</p>
        </div>
        <form method="post" action="<?= base_url('surat-keluar/sisip-simpan'); ?>">
            <div class="card-content">
                <?php $this->load->view('surat_keluar/_form_nomor_fields', array(
                    'tanggal_field' => array(
                        'max'   => isset($tanggal_hari_ini) ? $tanggal_hari_ini : date('Y-m-d'),
                        'value' => isset($tanggal_hari_ini) ? $tanggal_hari_ini : date('Y-m-d'),
                    ),
                )); ?>
            </div>

            <div class="card-footer flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 px-5 py-3">
                <p class="text-xs text-gray-500">
                    Huruf ditambahkan otomatis bila nomor pada tanggal tersebut sudah terpakai.
                </p>
                <div class="flex gap-2">
                    <button type="reset" class="btn-outline">Ulang</button>
                    <button type="submit" class="btn-primary">
                        <?= svg_icon('paperclip'); ?>
                        <span>Sisip Nomor</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
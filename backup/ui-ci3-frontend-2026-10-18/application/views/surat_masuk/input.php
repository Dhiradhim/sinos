<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Input Surat Masuk</h2>
            <p class="card-description">Catat surat masuk beserta klasifikasi dan berkasnya.</p>
        </div>
        <form method="post" action="<?= base_url('surat-masuk/simpan'); ?>" enctype="multipart/form-data">
            <div class="card-content">
                <?php $this->load->view('surat_masuk/_form_fields'); ?>
            </div>

            <div class="card-footer flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 px-5 py-3">
                <button type="reset" class="btn-outline">Ulang</button>
                <button type="submit" class="btn-primary">
                    <?= svg_icon('check'); ?>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>
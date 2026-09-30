<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Input Surat Masuk</h2>
            <p class="card-description">Catat surat masuk beserta klasifikasi dan berkasnya.</p>
        </div>
        <div class="card-content">
            <form method="post" action="<?= base_url('surat-masuk/simpan'); ?>" enctype="multipart/form-data" class="space-y-5">
                <?php $this->load->view('surat_masuk/_form_fields'); ?>
                <div class="flex justify-end gap-2 border-t border-border pt-5" style="display:flex; justify-content:flex-end; gap:.5rem; width:100%;">
                    <button type="reset" class="btn-outline">Ulang</button>
                    <button type="submit" class="btn-primary">
                        <?= svg_icon('check'); ?>
                        <span>Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
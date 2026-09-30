<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Edit Surat Masuk</h2>
            <p class="card-description">Perbarui data surat masuk.</p>
        </div>
        <div class="card-content">
            <form method="post" action="<?= base_url('surat-masuk/update/' . $row->id); ?>" enctype="multipart/form-data" class="space-y-5">
                <?php $this->load->view('surat_masuk/_form_fields'); ?>
                <div class="flex justify-end gap-2 border-t border-border pt-5">
                    <a href="<?= base_url('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat))); ?>" class="btn-outline">
                        <?= svg_icon('arrow-left'); ?>
                        <span>Kembali</span>
                    </a>
                    <button type="submit" class="btn-primary">
                        <?= svg_icon('check'); ?>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
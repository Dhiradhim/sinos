<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Edit Surat Masuk</h2>
            <p class="card-description">Perbarui data surat masuk.</p>
        </div>
        <form method="post" action="<?= base_url('surat-masuk/update/' . $row->id); ?>" enctype="multipart/form-data">
            <div class="card-content">
                <?php $this->load->view('surat_masuk/_form_fields'); ?>
            </div>

            <div class="card-footer flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 px-5 py-3">
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
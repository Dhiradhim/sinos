<form method="post" action="<?= base_url('surat-masuk/update/' . $row->id); ?>" enctype="multipart/form-data" class="space-y-5">
    <?php $this->load->view('surat_masuk/_form_fields'); ?>
    <div class="flex justify-end gap-2 pt-2">
        <a href="<?= base_url('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat))); ?>" class="btn-muted">Kembali</a>
        <button type="submit" class="btn-primary">Simpan Perubahan</button>
    </div>
</form>
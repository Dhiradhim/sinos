<form method="post" action="<?= base_url('surat-masuk/simpan'); ?>" enctype="multipart/form-data" class="space-y-5">
    <?php $this->load->view('surat_masuk/_form_fields'); ?>
    <div class="flex justify-end gap-2 pt-2">
        <button type="reset" class="btn-muted">Ulang</button>
        <button type="submit" class="btn-success">Simpan</button>
    </div>
</form>
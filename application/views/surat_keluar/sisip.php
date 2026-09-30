<form method="post" action="<?= base_url('surat-keluar/sisip-simpan'); ?>" class="space-y-5">
    <?php $this->load->view('surat_keluar/_pengambil_field'); ?>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="form-label" for="kj">Derajat Surat (KJ)</label>
            <input class="form-input" type="text" id="kj" name="kj" placeholder="mis. A1" required>
        </div>
        <div>
            <label class="form-label" for="tanggal">Tanggal Surat</label>
            <input class="form-input" type="date" id="tanggal" name="tanggal" required>
        </div>
    </div>

    <?php $this->load->view('surat_keluar/_klasifikasi_fields'); ?>

    <div>
        <label class="form-label" for="hal">Perihal</label>
        <input class="form-input" type="text" id="hal" name="hal" required>
    </div>

    <div>
        <label class="form-label" for="tujuan">Tujuan</label>
        <input class="form-input" type="text" id="tujuan" name="tujuan" required>
    </div>

    <div class="flex justify-end gap-2 pt-2">
        <button type="reset" class="btn-muted">Ulang</button>
        <button type="submit" class="btn-primary">Sisip Nomor</button>
    </div>
</form>
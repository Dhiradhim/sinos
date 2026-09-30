<form method="post" action="<?= base_url('surat-keluar/update/' . $row->id); ?>" class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="form-label" for="nip">Pengambil Nomor</label>
            <select class="form-select" id="nip" name="nip" required>
                <?php foreach ($pengambil as $p): ?>
                    <option value="<?= html_escape($p->nip); ?>" <?= ($p->nip === $row->nip) ? 'selected' : ''; ?>>
                        <?= html_escape($p->nama); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="tanggal">Tanggal Surat</label>
            <input class="form-input" type="date" id="tanggal" name="tanggal" value="<?= html_escape($row->tanggal); ?>" required>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="form-label" for="no_urut">No. Urut</label>
            <input class="form-input" type="number" id="no_urut" name="no_urut" value="<?= html_escape($row->no_urut); ?>" required>
        </div>
        <div>
            <label class="form-label" for="huruf">Huruf</label>
            <input class="form-input" type="text" id="huruf" name="huruf" value="<?= html_escape($row->huruf); ?>" placeholder="mis. A">
        </div>
        <div>
            <label class="form-label" for="kj">Derajat (KJ)</label>
            <input class="form-input" type="text" id="kj" name="kj" value="<?= html_escape($row->kj); ?>" required>
        </div>
    </div>

    <?php $this->load->view('surat_keluar/_klasifikasi_fields'); ?>

    <div>
        <label class="form-label" for="hal">Perihal</label>
        <input class="form-input" type="text" id="hal" name="hal" value="<?= html_escape($row->hal); ?>" required>
    </div>
    <div>
        <label class="form-label" for="tujuan">Tujuan</label>
        <input class="form-input" type="text" id="tujuan" name="tujuan" value="<?= html_escape($row->tujuan); ?>" required>
    </div>

    <div class="flex justify-end gap-2 pt-2">
        <a href="<?= base_url('surat-keluar/daftar?tahun=' . date('Y', strtotime($row->tanggal))); ?>" class="btn-muted">Kembali</a>
        <button type="submit" class="btn-primary">Simpan Perubahan</button>
    </div>
</form>
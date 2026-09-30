<?php

/**
 * Partial form fields surat masuk.
 * Variabel: $klasifikasi; opsional $row (mode edit)
 */
$row = isset($row) ? $row : NULL;
$val = function ($field) use ($row) {
    return $row && isset($row->$field) ? html_escape($row->$field) : '';
};
?>
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="form-label" for="kode">Kode Klasifikasi</label>
        <select class="form-select" id="kode" name="kode" required>
            <option value="" hidden>Pilih Salah Satu</option>
            <?php foreach ($klasifikasi as $k): ?>
                <option value="<?= html_escape($k->kode); ?>" <?= ($row && $row->kode === $k->kode) ? 'selected' : ''; ?>>
                    <?= html_escape($k->kode . ' - ' . (isset($k->nama) ? $k->nama : '')); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label" for="no_agenda">No. Agenda</label>
        <input class="form-input" type="text" id="no_agenda" name="no_agenda" value="<?= $row ? $val('no_agenda') : '-'; ?>" required>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="form-label" for="no_surat">Nomor Surat</label>
        <input class="form-input" type="text" id="no_surat" name="no_surat" value="<?= $val('no_surat'); ?>" required>
    </div>
    <div>
        <label class="form-label" for="tgl_surat">Tanggal Surat</label>
        <input class="form-input" type="date" id="tgl_surat" name="tgl_surat" value="<?= $val('tgl_surat'); ?>" required>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="form-label" for="tgl_surat">Tanggal Surat</label>
        <input class="form-input" type="date" id="tgl_surat" name="tgl_surat" value="<?= $val('tgl_surat'); ?>" required>
    </div>
    <div>
        <label class="form-label" for="tgl_diterima">Tanggal Diterima</label>
        <input class="form-input" type="date" id="tgl_diterima" name="tgl_diterima" value="<?= $val('tgl_diterima'); ?>" required>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="form-label" for="pengirim">Pengirim</label>
        <input class="form-input" type="text" id="pengirim" name="pengirim" value="<?= $val('pengirim'); ?>" required>
    </div>
    <div>
        <label class="form-label" for="perihal">Perihal</label>
        <input class="form-input" type="text" id="perihal" name="perihal" value="<?= $val('perihal'); ?>" required>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="form-label" for="pengolah">Pengolah</label>
        <select class="form-select" id="pengolah" name="pengolah" required>
            <option value="" hidden>Pilih Pengolah</option>
            <?php foreach (array('Kepaniteraan', 'Umum Keuangan', 'PTIP', 'Kepegawaian') as $opt): ?>
                <option value="<?= $opt; ?>" <?= ($row && $row->pengolah === $opt) ? 'selected' : ''; ?>><?= $opt; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="form-label" for="keterangan">Keterangan</label>
        <input class="form-input" type="text" id="keterangan" name="keterangan" value="<?= $val('keterangan'); ?>" required>
    </div>
</div>

<div>
    <label class="form-label" for="file">Upload Dokumen (PDF)</label>
    <input class="form-input" type="file" id="file" name="file" accept="application/pdf">
    <?php if ($row && ! empty($row->file)): ?>
        <p class="mt-1 text-xs text-slate-400">Berkas saat ini: <a class="text-brand-600" href="<?= base_url($row->file); ?>" target="_blank">lihat</a></p>
    <?php endif; ?>
</div>
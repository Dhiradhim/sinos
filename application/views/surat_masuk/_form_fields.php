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
<!-- Pengirim & Pengolah di paling atas -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="label" for="pengirim">Pengirim</label>
        <input class="input" type="text" id="pengirim" name="pengirim" value="<?= $val('pengirim'); ?>" required>
    </div>
    <div>
        <label class="label" for="pengolah">Pengolah</label>
        <select class="select" id="pengolah" name="pengolah" required>
            <option value="" hidden>Pilih Pengolah</option>
            <?php foreach (array('Kepaniteraan', 'Umum Keuangan', 'PTIP', 'Kepegawaian') as $opt): ?>
                <option value="<?= $opt; ?>" <?= ($row && $row->pengolah === $opt) ? 'selected' : ''; ?>><?= $opt; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Nomor Surat & No. Agenda -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="label" for="no_surat">Nomor Surat</label>
        <input class="input" type="text" id="no_surat" name="no_surat" value="<?= $val('no_surat'); ?>" required>
    </div>
    <div>
        <label class="label" for="no_agenda">No. Agenda</label>
        <?php $agenda = $row ? $val('no_agenda') : (isset($next_agenda) ? html_escape($next_agenda) : '-'); ?>
        <input class="input bg-muted text-muted-foreground" type="text" id="no_agenda" name="no_agenda"
            value="<?= $agenda; ?>" disabled readonly>
        <p class="mt-1.5 text-xs text-muted-foreground">Nomor agenda dibuat otomatis (tidak dapat diubah).</p>
    </div>
</div>

<!-- Tanggal Surat & Tanggal Diterima -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="label" for="tgl_surat">Tanggal Surat</label>
        <input class="input" type="date" id="tgl_surat" name="tgl_surat" value="<?= $val('tgl_surat'); ?>" required>
    </div>
    <div>
        <label class="label" for="tgl_diterima">Tanggal Diterima</label>
        <input class="input" type="date" id="tgl_diterima" name="tgl_diterima" value="<?= $val('tgl_diterima'); ?>" required>
    </div>
</div>

<!-- Kode Klasifikasi full width -->
<div>
    <label class="label" for="kode">Kode Klasifikasi</label>
    <select class="select" id="kode" name="kode" required
        data-searchable-select data-placeholder="Pilih Salah Satu" data-search-placeholder="Cari kode / nama klasifikasi…">
        <option value="" hidden>Pilih Salah Satu</option>
        <?php foreach ($klasifikasi as $k): ?>
            <option value="<?= html_escape($k->kode); ?>" <?= ($row && $row->kode === $k->kode) ? 'selected' : ''; ?>>
                <?= html_escape($k->kode . ' - ' . (isset($k->nama) ? $k->nama : '')); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Perihal full width -->
<div>
    <label class="label" for="perihal">Perihal</label>
    <input class="input" type="text" id="perihal" name="perihal" value="<?= $val('perihal'); ?>" required>
</div>

<!-- Keterangan & Upload Dokumen -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="label" for="keterangan">Keterangan</label>
        <input class="input" type="text" id="keterangan" name="keterangan" value="<?= $val('keterangan'); ?>" required>
    </div>
    <div>
        <label class="label" for="file">Upload Dokumen (PDF)<?= $row ? '' : ' <span class="text-destructive">*</span>'; ?></label>
        <input class="input file:mr-3 file:rounded file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-secondary-foreground"
            type="file" id="file" name="file" accept="application/pdf" <?= $row ? '' : ' required'; ?>>
        <?php if ($row && ! empty($row->file)): ?>
            <p class="mt-1.5 text-xs text-muted-foreground">
                Berkas saat ini:
                <a class="font-medium text-primary underline-offset-2 hover:underline" href="<?= base_url($row->file); ?>" target="_blank" rel="noopener">lihat</a>
            </p>
        <?php endif; ?>
    </div>
</div>
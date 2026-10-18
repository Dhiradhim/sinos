<?php

/**
 * Partial form fields surat masuk.
 * Variabel: $klasifikasi; opsional $row (mode edit)
 */
$this->load->helper('api_berkas'); // tautan berkas bertanda tangan (Fase 2)

$row = isset($row) ? $row : NULL;
$val = function ($field) use ($row) {
    return $row && isset($row->$field) ? html_escape($row->$field) : '';
};
$attr_klas = hs_select_attr('Pilih salah satu', 'Cari kode / nama klasifikasi…');
?>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="pengirim">Pengirim</label>
        <input class="input" type="text" id="pengirim" name="pengirim" placeholder="Instansi / perorangan pengirim"
            value="<?= $val('pengirim'); ?>" required>
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

    <div>
        <label class="label" for="no_surat">Nomor Surat</label>
        <input class="input" type="text" id="no_surat" name="no_surat" placeholder="Nomor surat dari pengirim"
            value="<?= $val('no_surat'); ?>" required>
    </div>
    <div>
        <label class="label" for="no_agenda">No. Agenda</label>
        <?php $agenda = $row ? $val('no_agenda') : (isset($next_agenda) ? html_escape($next_agenda) : '-'); ?>
        <input class="input bg-gray-50 text-gray-500" type="text" id="no_agenda" name="no_agenda"
            value="<?= $agenda; ?>" disabled readonly>
        <p class="mt-1.5 text-xs text-gray-500">Nomor agenda dibuat otomatis (tidak dapat diubah).</p>
    </div>

    <div>
        <label class="label" for="tgl_surat">Tanggal Surat</label>
        <input class="input" type="date" id="tgl_surat" name="tgl_surat" value="<?= $val('tgl_surat'); ?>" required>
    </div>
    <div>
        <label class="label" for="tgl_diterima">Tanggal Diterima</label>
        <input class="input" type="date" id="tgl_diterima" name="tgl_diterima" value="<?= $val('tgl_diterima'); ?>" required>
    </div>

    <div class="sm:col-span-2">
        <label class="label" for="kode">Kode Klasifikasi</label>
        <select class="select" id="kode" name="kode" required <?= $attr_klas; ?>>
            <option value="" hidden>Pilih salah satu</option>
            <?php foreach ($klasifikasi as $k): ?>
                <option value="<?= html_escape($k->kode); ?>" <?= ($row && $row->kode === $k->kode) ? 'selected' : ''; ?>>
                    <?= html_escape($k->kode . ' - ' . (isset($k->nama) ? $k->nama : '')); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="sm:col-span-2">
        <label class="label" for="perihal">Perihal</label>
        <input class="input" type="text" id="perihal" name="perihal" placeholder="Ringkasan isi surat"
            value="<?= $val('perihal'); ?>" required>
    </div>

    <div>
        <label class="label" for="keterangan">Keterangan</label>
        <input class="input" type="text" id="keterangan" name="keterangan" placeholder="Catatan tambahan"
            value="<?= $val('keterangan'); ?>" required>
    </div>
    <div>
        <label class="label" for="file">
            Upload Dokumen (PDF)<?= $row ? '' : ' <span class="text-red-600">*</span>'; ?>
        </label>
        <input class="input" type="file" id="file" name="file" accept="application/pdf" <?= $row ? '' : 'required'; ?>>
        <p class="mt-1.5 text-xs text-gray-500">Hanya file PDF.</p>
        <?php if ($row && ! empty($row->file)): ?>
            <p class="mt-1 text-xs text-gray-500">
                Berkas saat ini:
                <a class="font-medium text-brand-900 underline-offset-2 hover:underline" href="<?= berkas_url($row->file); ?>" target="_blank" rel="noopener">lihat</a>
            </p>
        <?php endif; ?>
    </div>
</div>
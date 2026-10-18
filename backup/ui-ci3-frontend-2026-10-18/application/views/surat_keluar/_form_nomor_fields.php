<?php

/**
 * Partial: field utama form nomor surat keluar (ambil & sisip).
 *
 * Variabel:
 *   $pengambil      array objek {nip, nama}
 *   $penandatangan  array grup [ ['grup' => ..., 'items' => [obj, ...]], ... ]
 *   $klasifikasi    array objek {kode, nama}
 *   $kode_penetapan array kode => label
 *   $nilai          array nilai awal (opsional, untuk repopulate)
 *   $tanggal_field  opsional ['max' => ..., 'value' => ...] untuk form sisip
 */

$nilai = isset($nilai) ? $nilai : array();
$val = function ($key, $default = '') use ($nilai) {
    return isset($nilai[$key]) ? $nilai[$key] : $default;
};

$tanggal_field = isset($tanggal_field) ? $tanggal_field : NULL;

$attr_pegawai = hs_select_attr('Pilih pengambil nomor', 'Cari nama pegawai…');
$attr_pejabat = hs_select_attr('Pilih penandatangan', 'Cari nama pejabat…');
$attr_klas    = hs_select_attr('Pilih kode klasifikasi', 'Cari kode / nama klasifikasi…');
?>

<!-- A. Penanggung jawab nomor -->
<fieldset>
    <legend class="fieldset-legend"><?= svg_icon('user', 'h-3.5 w-3.5'); ?>Penanggung Jawab</legend>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="nip">Pengambil Nomor</label>
            <select class="select" id="nip" name="nip" required <?= $attr_pegawai; ?>>
                <option value="" hidden>Pilih Pengambil Nomor</option>
                <?php foreach ($pengambil as $p): ?>
                    <option value="<?= html_escape($p->nip); ?>" <?= $val('nip') === $p->nip ? 'selected' : ''; ?>>
                        <?= html_escape($p->nama); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label" for="penandatangan_nip">Penandatangan Surat</label>
            <select class="select" id="penandatangan_nip" name="penandatangan_nip" required <?= $attr_pejabat; ?>>
                <option value="" hidden>Pilih Penandatangan</option>
                <?php foreach ($penandatangan as $grup): ?>
                    <?php foreach ($grup['items'] as $t): ?>
                        <option value="<?= html_escape($t->nip); ?>" <?= $val('penandatangan_nip') === $t->nip ? 'selected' : ''; ?>>
                            <?= html_escape($t->label); ?> &middot; <?= html_escape($grup['grup']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</fieldset>

<div class="separator"></div>

<!-- B. Identitas surat -->
<fieldset>
    <legend class="fieldset-legend"><?= svg_icon('file-text', 'h-3.5 w-3.5'); ?>Identitas Surat</legend>

    <?php if ($tanggal_field): ?>
        <!-- Sisip: tanggal bersebelahan dengan kode penetapan -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label class="label" for="kode_penetapan">Kode Penetapan</label>
                <select class="select" id="kode_penetapan" name="kode_penetapan" required>
                    <?php foreach ($kode_penetapan as $kode => $label): ?>
                        <option value="<?= html_escape($kode); ?>" <?= $val('kode_penetapan', '-') === $kode ? 'selected' : ''; ?>>
                            <?= html_escape($label); ?> [<?= html_escape($kode); ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label" for="tanggal">Tanggal</label>
                <input class="input" type="date" id="tanggal" name="tanggal"
                    max="<?= html_escape($tanggal_field['max']); ?>"
                    value="<?= html_escape($tanggal_field['value']); ?>"
                    required>
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="ks">Kode Surat</label>
                <select class="select" id="ks" name="ks" required <?= $attr_klas; ?>>
                    <option value="" hidden>Pilih Kode Surat</option>
                    <?php foreach ($klasifikasi as $k): ?>
                        <option value="<?= html_escape($k->kode); ?>" <?= $val('ks') === $k->kode ? 'selected' : ''; ?>>
                            <?= html_escape($k->kode . ' - ' . (isset($k->nama) ? $k->nama : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    <?php else: ?>
        <!-- Ambil: kode penetapan (1/4) dan kode surat (3/4) -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label class="label" for="kode_penetapan">Kode Penetapan</label>
                <select class="select" id="kode_penetapan" name="kode_penetapan" required>
                    <?php foreach ($kode_penetapan as $kode => $label): ?>
                        <option value="<?= html_escape($kode); ?>" <?= $val('kode_penetapan', '-') === $kode ? 'selected' : ''; ?>>
                            <?= html_escape($label); ?> [<?= html_escape($kode); ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-3">
                <label class="label" for="ks">Kode Surat</label>
                <select class="select" id="ks" name="ks" required <?= $attr_klas; ?>>
                    <option value="" hidden>Pilih Kode Surat</option>
                    <?php foreach ($klasifikasi as $k): ?>
                        <option value="<?= html_escape($k->kode); ?>" <?= $val('ks') === $k->kode ? 'selected' : ''; ?>>
                            <?= html_escape($k->kode . ' - ' . (isset($k->nama) ? $k->nama : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="hal">Perihal</label>
            <input class="input" type="text" id="hal" name="hal" placeholder="Perihal surat"
                value="<?= html_escape($val('hal')); ?>" required>
        </div>
        <div>
            <label class="label" for="tujuan">Tujuan</label>
            <input class="input" type="text" id="tujuan" name="tujuan" placeholder="Tujuan surat"
                value="<?= html_escape($val('tujuan')); ?>" required>
        </div>
    </div>
</fieldset>
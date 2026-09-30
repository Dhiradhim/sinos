<?php

/**
 * Partial: baris pemilihan klasifikasi surat.
 * Variabel: $klasifikasi
 * Menghasilkan komponen kp (kode pokok), ks (kode sub dari select), kd1..kd3.
 */
$klasifikasi = isset($klasifikasi) ? $klasifikasi : array();
?>
<div class="grid grid-cols-1 gap-4 md:grid-cols-4">
    <div>
        <label class="form-label" for="kp">Kode Pokok</label>
        <input class="form-input" type="text" id="kp" name="kp" placeholder="mis. W23" value="<?= set_value('kp', '-'); ?>">
    </div>
    <div class="md:col-span-3">
        <label class="form-label" for="ks">Kode Klasifikasi</label>
        <select class="form-select" id="ks" name="ks" required>
            <option value="" hidden>Pilih Kode Klasifikasi</option>
            <?php foreach ($klasifikasi as $k): ?>
                <option value="<?= html_escape($k->kode); ?>">
                    <?= html_escape($k->kode . ' - ' . (isset($k->nama) ? $k->nama : '')); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div>
        <label class="form-label" for="kd1">Kode 1</label>
        <input class="form-input" type="text" id="kd1" name="kd1" placeholder="-">
    </div>
    <div>
        <label class="form-label" for="kd2">Kode 2</label>
        <input class="form-input" type="text" id="kd2" name="kd2" placeholder="-">
    </div>
    <div>
        <label class="form-label" for="kd3">Kode 3</label>
        <input class="form-input" type="text" id="kd3" name="kd3" placeholder="-">
    </div>
</div>
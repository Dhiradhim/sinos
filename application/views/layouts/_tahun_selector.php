<?php

/**
 * Partial: pemilih tahun (dropdown) untuk daftar surat.
 * Variabel: $tahun (tahun aktif), $tahun_list (array tahun), $tahun_action (URL tujuan)
 */
$tahun_list   = isset($tahun_list) ? $tahun_list : tahun_tersedia();
$tahun        = isset($tahun) ? (int) $tahun : (int) date('Y');
$tahun_action = isset($tahun_action) ? $tahun_action : current_url();
?>
<form method="get" action="<?= html_escape($tahun_action); ?>" class="inline-flex items-center gap-2">
    <input type="hidden" name="tahun_submit" value="1">
    <label for="tahun" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
        <?= svg_icon('calendar', 'h-4 w-4'); ?>
        <span class="hidden sm:inline">Tahun</span>
    </label>
    <select id="tahun" name="tahun" class="select h-9 w-[6.5rem] text-sm" onchange="this.form.submit()">
        <?php foreach ($tahun_list as $y): ?>
            <option value="<?= (int) $y; ?>" <?= ((int) $y === $tahun) ? 'selected' : ''; ?>><?= (int) $y; ?></option>
        <?php endforeach; ?>
    </select>
</form>
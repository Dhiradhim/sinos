<?php

/**
 * Partial: pemilih tahun (dropdown) untuk daftar surat.
 * Variabel: $tahun (tahun aktif), $tahun_list (array tahun), $tahun_action (URL tujuan)
 */
$this->load->helper('ui');
$ui_v4 = ui_variant() === 'v4';

$tahun_list   = isset($tahun_list) ? $tahun_list : tahun_tersedia();
$tahun        = isset($tahun) ? (int) $tahun : (int) date('Y');
$tahun_action = isset($tahun_action) ? $tahun_action : current_url();

$label_ink = $ui_v4 ? 'text-[var(--color-ink-subtle,#64748b)]' : 'text-gray-500';
?>
<form method="get" action="<?= html_escape($tahun_action); ?>" class="inline-flex items-center gap-2">
    <input type="hidden" name="tahun_submit" value="1">
    <label for="tahun" class="inline-flex items-center gap-1.5 text-sm <?= $label_ink; ?>">
        <?= svg_icon('calendar', 'h-4 w-4'); ?>
        <span class="hidden sm:inline">Tahun</span>
        <span class="sr-only sm:hidden">Tahun</span>
    </label>
    <select id="tahun" name="tahun" class="select h-9 w-[6.5rem] py-1 text-sm" onchange="this.form.submit()">
        <?php foreach ($tahun_list as $y): ?>
            <option value="<?= (int) $y; ?>" <?= ((int) $y === $tahun) ? 'selected' : ''; ?>><?= (int) $y; ?></option>
        <?php endforeach; ?>
    </select>
</form>
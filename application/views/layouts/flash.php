<?php
$tipe  = $this->session->flashdata('flash_tipe');
$pesan = $this->session->flashdata('flash_pesan');
if (! $pesan) {
    return;
}
$styles = array(
    'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    'error'   => 'bg-rose-50 text-rose-700 ring-rose-200',
    'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
    'info'    => 'bg-brand-50 text-brand-700 ring-brand-200',
);
$icon = array('success' => '✅', 'error' => '⛔', 'warning' => '⚠️', 'info' => 'ℹ️');
$cls = isset($styles[$tipe]) ? $styles[$tipe] : $styles['info'];
$ic  = isset($icon[$tipe]) ? $icon[$tipe] : $icon['info'];
?>
<div data-flash class="mb-4 flex items-center gap-3 rounded-xl px-4 py-3 text-sm ring-1 <?= $cls; ?>">
    <span><?= $ic; ?></span>
    <span><?= html_escape($pesan); ?></span>
</div>
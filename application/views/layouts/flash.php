<?php
$tipe  = $this->session->flashdata('flash_tipe');
$pesan = $this->session->flashdata('flash_pesan');
if (! $pesan) {
    return;
}
$styles = array(
    'success' => array('alert-success', 'circle-check', 'Berhasil'),
    'error'   => array('alert-destructive', 'octagon-x', 'Gagal'),
    'warning' => array('alert-warning', 'triangle-alert', 'Perhatian'),
    'info'    => array('alert-info', 'info', 'Informasi'),
);
$cfg = isset($styles[$tipe]) ? $styles[$tipe] : $styles['info'];
?>
<div data-flash class="<?= $cfg[0]; ?> mb-4 animate-fade-in" role="alert">
    <span class="mt-0.5 shrink-0"><?= svg_icon($cfg[1], 'h-4 w-4'); ?></span>
    <div class="flex-1">
        <p class="alert-title"><?= $cfg[2]; ?></p>
        <p class="alert-description"><?= html_escape($pesan); ?></p>
    </div>
</div>
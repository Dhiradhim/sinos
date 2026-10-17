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
<div data-flash class="<?= $cfg[0]; ?> mb-4" role="status" aria-live="polite">
    <span class="mt-0.5 shrink-0"><?= svg_icon($cfg[1], 'h-4 w-4'); ?></span>
    <div class="flex-1">
        <p class="alert-title"><?= $cfg[2]; ?></p>
        <p class="alert-description"><?= html_escape($pesan); ?></p>
    </div>
    <button type="button" data-flash-close
        class="-mr-1 -mt-1 shrink-0 rounded-md p-1 opacity-60 transition-opacity hover:opacity-100 focus:outline-hidden focus-visible:opacity-100"
        aria-label="Tutup notifikasi"
        onclick="var b=this.closest('[data-flash]');if(b){b.style.transition='opacity .2s';b.style.opacity='0';setTimeout(function(){b.remove();},200);}">
        <?= svg_icon('x', 'h-3.5 w-3.5'); ?>
    </button>
</div>
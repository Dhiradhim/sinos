<?php
$this->load->helper('ui');
$ui_v4 = ui_variant() === 'v4';

$footer_line = $ui_v4 ? 'border-[var(--color-line)]' : 'border-gray-200';
$footer_bg   = $ui_v4 ? 'bg-[var(--color-surface)]' : 'bg-white';
$footer_ink  = $ui_v4 ? 'text-[var(--color-ink-subtle,#64748b)]' : 'text-gray-500';
?>
<footer class="border-t <?= $footer_line; ?> <?= $footer_bg; ?> px-4 py-3 sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-[110rem] flex-col items-center justify-between gap-1 text-[0.6875rem] <?= $footer_ink; ?> sm:flex-row">
        <p>&copy; <?= date('Y'); ?> Pengadilan Agama Kupang</p>
        <p class="sm:text-right">SINOS &mdash; Sistem Informasi Nomor Surat</p>
    </div>
</footer>
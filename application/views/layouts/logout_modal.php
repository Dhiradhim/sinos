<?php
$this->load->helper('ui');
$ui_v4 = ui_variant() === 'v4';

$modal_surface = $ui_v4 ? 'bg-[var(--color-surface)]' : 'bg-white';
$modal_line    = $ui_v4 ? 'border-[var(--color-line)]' : 'border-gray-200';
$modal_radius  = $ui_v4 ? 'rounded-[var(--radius-card)]' : 'rounded-xl';
$modal_shadow  = $ui_v4 ? 'shadow-[var(--shadow-pop)]' : 'shadow-lg';
$ink_strong    = $ui_v4 ? 'text-[var(--color-ink,#0f172a)]' : 'text-gray-900';
$ink_muted     = $ui_v4 ? 'text-[var(--color-ink-muted,#475569)]' : 'text-gray-600';
// Scrim berbasis token (bukan bg-gray-900/50 bawaan Preline).
// Preline 5 membaca backdropClasses dari data-hs-overlay-options.
$modal_scrim = $ui_v4
    ? " data-hs-overlay-options='{\"backdropClasses\":\"hs-overlay-backdrop transition duration fixed inset-0 bg-[var(--color-ink)]/40\"}'"
    : '';
?>
<!-- Preline overlay: konfirmasi logout -->
<div id="logoutModal"
    class="hs-overlay pointer-events-none fixed inset-0 z-[80] hidden size-full overflow-y-auto overflow-x-hidden" <?= $modal_scrim; ?>

    role="dialog" aria-modal="true" aria-labelledby="logoutTitle" tabindex="-1">
    <div class="pointer-events-none m-3 mt-0 flex min-h-[calc(100%-1.5rem)] items-center opacity-0 transition-all duration-300 ease-out sm:mx-auto sm:w-full sm:max-w-sm hs-overlay-open:mt-7 hs-overlay-open:opacity-100">
        <div class="pointer-events-auto flex w-full flex-col <?= $modal_radius; ?> border <?= $modal_line; ?> <?= $modal_surface; ?> <?= $modal_shadow; ?>">
            <div class="flex items-center justify-between border-b <?= $modal_line; ?> px-5 py-3">
                <h3 id="logoutTitle" class="flex items-center gap-2 text-sm font-semibold <?= $ink_strong; ?>">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600">
                        <?= svg_icon('log-out', 'h-4 w-4'); ?>
                    </span>
                    Konfirmasi Logout
                </h3>
                <button type="button" class="btn-icon !border-transparent !shadow-none" data-hs-overlay="#logoutModal"
                    aria-label="Tutup">
                    <?= svg_icon('x', 'h-4 w-4'); ?>
                </button>
            </div>
            <div class="px-5 py-4 text-sm <?= $ink_muted; ?>">
                Apakah Anda yakin ingin keluar dari aplikasi SINOS? Anda perlu login kembali untuk mengakses sistem.
            </div>
            <div class="flex items-center justify-end gap-2 border-t <?= $modal_line; ?> px-5 py-3">
                <button type="button" class="btn-outline btn-sm" data-hs-overlay="#logoutModal">Batal</button>
                <a href="<?= base_url('logout'); ?>" class="btn-destructive btn-sm">
                    <?= svg_icon('log-out'); ?>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</div>
<?php

/**
 * Sidebar — Preline style.
 * Variabel: $user (nip, nama, is_admin, is_operator), $disposisi_count
 */
$this->load->helper('ui');
$ui_v4 = ui_variant() === 'v4';

$is_admin = ! empty($user['is_admin']);

$uri = trim((string) $this->uri->uri_string(), '/');

$is = function ($kunci) use ($uri) {
    $kunci = trim($kunci, '/');
    if ($uri === $kunci || strpos($uri, $kunci . '/') === 0) {
        return 'nav-item nav-item-active';
    }
    return 'nav-item';
};

// Token semantik hanya ada di v4; v3 tetap memakai utilitas gray/white-nya.
$sidebar_surface = $ui_v4 ? 'bg-[var(--color-surface)]' : 'bg-white';
$sidebar_line    = $ui_v4 ? 'border-[var(--color-line)]' : 'border-gray-200';
$brand_mark      = $ui_v4 ? 'rounded-[var(--radius-control)]' : 'rounded-lg';
$ink_strong      = $ui_v4 ? 'text-[var(--color-ink,#0f172a)]' : 'text-gray-900';
$ink_subtle      = $ui_v4 ? 'text-[var(--color-ink-subtle,#64748b)]' : 'text-gray-500';
$icon_subtle     = $ui_v4 ? 'text-[var(--color-ink-subtle,#64748b)]' : 'text-gray-400';
// Ikon nav dibungkus [data-nav-icon], jadi aturan `.nav-item > svg` v4 tidak
// menjangkaunya. Ukuran eksplisit hanya untuk v4 agar v3 tetap identik.
$nav_icon        = $ui_v4 ? 'h-4 w-4' : '';
?>
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r <?= $sidebar_line; ?> <?= $sidebar_surface; ?> transition-transform duration-200 lg:translate-x-0">

    <!-- Brand (pinned) -->
    <div class="flex h-16 shrink-0 items-center border-b <?= $sidebar_line; ?> px-4">
        <a href="<?= base_url('dashboard'); ?>" class="flex min-w-0 items-center gap-3 rounded-lg focus-ring">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center <?= $brand_mark; ?> bg-brand-900 text-white">
                <?= svg_icon('bank', 'h-5 w-5 text-white'); ?>
            </span>
            <span class="min-w-0 leading-tight">
                <span class="block truncate text-sm font-semibold tracking-tight <?= $ink_strong; ?>">SINOS</span>
                <span class="block truncate text-[0.6875rem] font-medium uppercase tracking-[0.12em] <?= $ink_subtle; ?>">
                    Pengadilan Agama Kupang
                </span>
            </span>
        </a>
    </div>

    <!-- Navigasi (scroll) -->
    <nav class="flex-1 overflow-y-auto px-2.5 pb-5 pt-1.5">
        <a href="<?= base_url('dashboard'); ?>" class="<?= $is('dashboard'); ?>">
            <span data-nav-icon><?= svg_icon('dashboard', $nav_icon); ?></span>
            <span>Beranda</span>
        </a>

        <p class="nav-group-label">Surat Masuk</p>
        <?php if (! empty($user['is_operator'])): ?>
            <a href="<?= base_url('surat-masuk'); ?>" class="<?= $uri === 'surat-masuk' ? 'nav-item nav-item-active' : 'nav-item'; ?>">
                <span data-nav-icon><?= svg_icon('inbox', $nav_icon); ?></span>
                <span>Input Surat Masuk</span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('surat-masuk/daftar?tahun=' . date('Y')); ?>" class="<?= $is('surat-masuk/daftar'); ?>">
            <span data-nav-icon><?= svg_icon('mail-open', $nav_icon); ?></span>
            <span>Daftar Surat Masuk</span>
        </a>
        <a href="<?= base_url('disposisi'); ?>" class="<?= $is('disposisi'); ?>">
            <span data-nav-icon><?= svg_icon('send', $nav_icon); ?></span>
            <span>Disposisi</span>
            <?php if (! empty($disposisi_count)): ?>
                <span class="badge badge-warning ml-auto tabular-nums">
                    <?= (int) $disposisi_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <p class="nav-group-label">Surat Keluar</p>
        <a href="<?= base_url('surat-keluar/ambil'); ?>" class="<?= $is('surat-keluar/ambil'); ?>">
            <span data-nav-icon><?= svg_icon('hash', $nav_icon); ?></span>
            <span>Ambil Nomor Surat</span>
        </a>
        <a href="<?= base_url('surat-keluar/sisip'); ?>" class="<?= $is('surat-keluar/sisip'); ?>">
            <span data-nav-icon><?= svg_icon('paperclip', $nav_icon); ?></span>
            <span>Sisip Nomor Surat</span>
        </a>
        <a href="<?= base_url('surat-keluar/daftar-semua?tahun=' . date('Y')); ?>" class="<?= $is('surat-keluar/daftar-semua'); ?>">
            <span data-nav-icon><?= svg_icon('folder', $nav_icon); ?></span>
            <span>Daftar Surat Keluar</span>
        </a>
        <?php if (! $is_admin): ?>
            <a href="<?= base_url('surat-keluar/daftar?tahun=' . date('Y')); ?>" class="<?= $is('surat-keluar/daftar'); ?>">
                <span data-nav-icon><?= svg_icon('file-text', $nav_icon); ?></span>
                <span>Daftar Nomor Surat</span>
            </a>
        <?php endif; ?>

        <?php if ($is_admin): ?>
            <p class="nav-group-label">Laporan</p>
            <a href="<?= base_url('laporan'); ?>" class="<?= $is('laporan'); ?>">
                <span data-nav-icon><?= svg_icon('printer', $nav_icon); ?></span>
                <span>Cetak Laporan</span>
            </a>
        <?php endif; ?>

        <p class="nav-group-label">Panduan</p>
        <a href="<?= base_url('assets/klasifikasi.pdf'); ?>" target="_blank" rel="noopener" class="nav-item">
            <span data-nav-icon><?= svg_icon('book', $nav_icon); ?></span>
            <span>Pola Klasifikasi Surat</span>
            <?= svg_icon('external-link', 'ml-auto !h-3.5 !w-3.5 ' . $icon_subtle); ?>
        </a>
    </nav>

    <!-- Footer (pinned) -->
    <div class="shrink-0 border-t <?= $sidebar_line; ?> px-4 py-2.5">
        <p class="text-[0.6875rem] <?= $ink_subtle; ?>">SINOS &middot; <?= date('Y'); ?></p>
    </div>
</aside>
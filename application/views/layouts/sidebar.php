<?php

/**
 * Sidebar shadcn-style.
 * Variabel: $user (nip, nama, is_admin), $active (segment menu aktif), $disposisi_count
 */
$active   = isset($active) ? $active : '';
$is_admin = ! empty($user['is_admin']);

$nav_item = 'nav-item';
$nav_on   = 'nav-item nav-item-active';
?>
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col overflow-y-auto border-r border-border bg-card px-3 py-4 transition-transform duration-200 lg:translate-x-0">

    <!-- Brand -->
    <a href="<?= base_url('dashboard'); ?>" class="mb-2 flex items-center gap-3 rounded-md px-2 py-2 focus-ring">
        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
            <?= svg_icon('bank', 'h-5 w-5 text-primary-foreground'); ?>
        </span>
        <span class="leading-tight">
            <span class="block text-sm font-semibold tracking-tight text-foreground">SINOS</span>
            <span class="block text-xs text-muted-foreground">Pengadilan Agama Kupang</span>
        </span>
    </a>

    <nav class="mt-2 flex-1 space-y-1">
        <a href="<?= base_url('dashboard'); ?>" class="<?= $active === 'dashboard' ? $nav_on : $nav_item; ?>">
            <?= svg_icon('dashboard'); ?>
            <span>Beranda</span>
        </a>

        <?php if ($is_admin): ?>
            <p class="nav-group-label">Manajemen User</p>
            <a href="<?= base_url('user'); ?>" class="<?= $active === 'user' ? $nav_on : $nav_item; ?>">
                <?= svg_icon('users'); ?>
                <span>Daftar User</span>
            </a>
            <a href="<?= base_url('user/tambah'); ?>" class="<?= $nav_item; ?>">
                <?= svg_icon('user-plus'); ?>
                <span>Tambah User</span>
            </a>
        <?php endif; ?>

        <p class="nav-group-label">Surat Masuk</p>
        <?php if (! empty($user['is_operator'])): ?>
            <a href="<?= base_url('surat-masuk'); ?>" class="<?= $nav_item; ?>">
                <?= svg_icon('inbox'); ?>
                <span>Input Surat Masuk</span>
            </a>
        <?php endif; ?>
        <a href="<?= base_url('surat-masuk/daftar?tahun=' . date('Y')); ?>" class="<?= $nav_item; ?>">
            <?= svg_icon('mail-open'); ?>
            <span>Daftar Surat Masuk</span>
        </a>
        <a href="<?= base_url('disposisi'); ?>" class="<?= $active === 'disposisi' ? $nav_on : $nav_item; ?>">
            <?= svg_icon('send'); ?>
            <span>Disposisi</span>
            <?php if (! empty($disposisi_count)): ?>
                <span class="ml-auto inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1.5 py-0.5 text-[0.6875rem] font-semibold text-primary-foreground">
                    <?= (int) $disposisi_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <p class="nav-group-label">Surat Keluar</p>
        <a href="<?= base_url('surat-keluar/ambil'); ?>" class="<?= $nav_item; ?>">
            <?= svg_icon('hash'); ?>
            <span>Ambil Nomor Surat</span>
        </a>
        <a href="<?= base_url('surat-keluar/sisip'); ?>" class="<?= $nav_item; ?>">
            <?= svg_icon('paperclip'); ?>
            <span>Sisip Nomor Surat</span>
        </a>
        <a href="<?= base_url('surat-keluar/daftar-semua?tahun=' . date('Y')); ?>" class="<?= $nav_item; ?>">
            <?= svg_icon('folder'); ?>
            <span>Daftar Surat Keluar</span>
        </a>
        <?php if (! $is_admin): ?>
            <a href="<?= base_url('surat-keluar/daftar?tahun=' . date('Y')); ?>" class="<?= $nav_item; ?>">
                <?= svg_icon('file-text'); ?>
                <span>Daftar Nomor Surat</span>
            </a>
        <?php endif; ?>

        <?php if ($is_admin): ?>
            <p class="nav-group-label">Laporan</p>
            <a href="<?= base_url('laporan'); ?>" class="<?= $active === 'laporan' ? $nav_on : $nav_item; ?>">
                <?= svg_icon('printer'); ?>
                <span>Cetak Laporan</span>
            </a>
        <?php endif; ?>

        <p class="nav-group-label">Panduan</p>
        <a href="<?= base_url('assets/klasifikasi.pdf'); ?>" target="_blank" rel="noopener" class="<?= $nav_item; ?>">
            <?= svg_icon('book'); ?>
            <span>Pola Klasifikasi Surat</span>
        </a>
    </nav>

    <div class="mt-4 border-t border-border pt-3">
        <p class="px-3 text-[0.6875rem] text-muted-foreground/70">SINOS &middot; <?= date('Y'); ?></p>
    </div>
</aside>
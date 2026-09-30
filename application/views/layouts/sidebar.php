<?php

/**
 * Sidebar Tailwind.
 * Variabel: $user (nip, nama, is_admin), $active (segment menu aktif)
 */
$active = isset($active) ? $active : '';
$is_admin = ! empty($user['is_admin']);

$link = 'flex items-center rounded-lg px-3 py-2 text-sm font-medium transition';
$on   = 'bg-brand-600 text-white';
$off  = 'text-slate-300 hover:bg-slate-800 hover:text-white';
?>
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto bg-slate-900 px-3 py-4 shadow-xl transition-transform duration-200 lg:translate-x-0">

    <!-- Brand -->
    <div class="mb-6 flex items-center gap-3 px-2">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-lg font-bold text-white">S</div>
        <div>
            <div class="text-base font-semibold text-white">SINOS</div>
            <div class="text-xs text-slate-400">Sistem Informasi Nomor Surat</div>
        </div>
    </div>

    <nav class="space-y-1">
        <a href="<?= base_url('dashboard'); ?>" class="<?= $link . ' ' . ($active === 'dashboard' ? $on : $off); ?>">
            <span class="mr-2">🏠</span> Beranda
        </a>

        <?php if ($is_admin): ?>
            <p class="mt-5 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">User</p>
            <a href="<?= base_url('user'); ?>" class="<?= $link . ' ' . ($active === 'user' ? $on : $off); ?>">
                <span class="mr-2">👥</span> Daftar User
            </a>
            <a href="<?= base_url('user/tambah'); ?>" class="<?= $link . ' ' . $off; ?>">
                <span class="mr-2">➕</span> Tambah User
            </a>
        <?php endif; ?>

        <p class="mt-5 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Surat Masuk</p>
        <a href="<?= base_url('surat-masuk'); ?>" class="<?= $link . ' ' . ($active === 'surat-masuk' ? $on : $off); ?>">
            <span class="mr-2">📥</span> Input Surat Masuk
        </a>
        <a href="<?= base_url('surat-masuk/daftar?tahun=' . date('Y')); ?>" class="<?= $link . ' ' . $off; ?>">
            <span class="mr-2">🗂️</span> Daftar Surat Masuk
        </a>
        <a href="<?= base_url('disposisi?tahun=' . date('Y')); ?>" class="<?= $link . ' ' . ($active === 'disposisi' ? $on : $off); ?>">
            <span class="mr-2">📨</span> Disposisi
            <?php if (! empty($disposisi_count)): ?>
                <span class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-xs font-semibold text-white">
                    <?= (int) $disposisi_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <p class="mt-5 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Surat Keluar</p>
        <a href="<?= base_url('surat-keluar/ambil'); ?>" class="<?= $link . ' ' . $off; ?>">
            <span class="mr-2">🔢</span> Ambil Nomor Surat
        </a>
        <a href="<?= base_url('surat-keluar/sisip'); ?>" class="<?= $link . ' ' . $off; ?>">
            <span class="mr-2">📎</span> Sisip Nomor Surat
        </a>
        <a href="<?= base_url('surat-keluar/daftar-semua?tahun=' . date('Y')); ?>" class="<?= $link . ' ' . $off; ?>">
            <span class="mr-2">📤</span> Daftar Surat Keluar
        </a>
        <?php if (! $is_admin): ?>
            <a href="<?= base_url('surat-keluar/daftar?tahun=' . date('Y')); ?>" class="<?= $link . ' ' . $off; ?>">
                <span class="mr-2">🗂️</span> Daftar Nomor Surat
            </a>
        <?php endif; ?>

        <?php if ($is_admin): ?>
            <p class="mt-5 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Laporan</p>
            <a href="<?= base_url('laporan'); ?>" class="<?= $link . ' ' . ($active === 'laporan' ? $on : $off); ?>">
                <span class="mr-2">🖨️</span> Cetak Laporan
            </a>
        <?php endif; ?>

        <p class="mt-5 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Panduan</p>
        <a href="<?= base_url('assets/klasifikasi.pdf'); ?>" target="_blank" class="<?= $link . ' ' . $off; ?>">
            <span class="mr-2">📄</span> Pola Klasifikasi Surat
        </a>
    </nav>
</aside>
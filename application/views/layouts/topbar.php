<?php
$initial = strtoupper(substr(isset($user['nama']) ? $user['nama'] : 'U', 0, 1));
$subtitle = isset($subtitle) ? $subtitle : '';
?>
<header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-border bg-background/80 px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-3">
        <button id="sidebarToggle" type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-input bg-background text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground lg:hidden"
            aria-label="Buka menu">
            <?= svg_icon('menu', 'h-4 w-4'); ?>
        </button>

        <div class="min-w-0">
            <nav class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <a href="<?= base_url('dashboard'); ?>" class="transition-colors hover:text-foreground">SINOS</a>
                <span class="text-border"><?= svg_icon('chevron-right', 'h-3 w-3'); ?></span>
                <span class="truncate font-medium text-foreground"><?= html_escape($title); ?></span>
            </nav>
            <h1 class="truncate text-base font-semibold leading-tight tracking-tight text-foreground">
                <?= html_escape($title); ?>
            </h1>
        </div>
    </div>

    <div class="relative shrink-0">
        <button type="button" data-dropdown="userMenu"
            class="flex items-center gap-2 rounded-full border border-transparent py-1 pl-1 pr-2 text-sm transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            aria-haspopup="menu" aria-expanded="false">
            <span class="avatar"><?= html_escape($initial); ?></span>
            <span class="hidden max-w-[10rem] truncate text-left text-sm font-medium text-foreground sm:block">
                <?= html_escape($user['nama']); ?>
            </span>
            <span class="hidden text-muted-foreground sm:block"><?= svg_icon('chevron-down', 'h-4 w-4'); ?></span>
        </button>

        <div id="userMenu" data-dropdown-menu
            class="dropdown-menu absolute right-0 mt-2 hidden w-60 animate-fade-in">
            <div class="px-2 py-1.5">
                <p class="truncate text-sm font-medium text-foreground"><?= html_escape($user['nama']); ?></p>
                <p class="truncate text-xs text-muted-foreground"><?= html_escape($user['nip']); ?></p>
            </div>
            <div class="dropdown-separator"></div>
            <a href="<?= base_url('ganti-password'); ?>" class="dropdown-item">
                <?= svg_icon('key'); ?>
                <span>Ganti Password</span>
            </a>
            <a href="<?= base_url('assets/klasifikasi.pdf'); ?>" target="_blank" rel="noopener" class="dropdown-item">
                <?= svg_icon('book'); ?>
                <span>Panduan Klasifikasi</span>
            </a>
            <div class="dropdown-separator"></div>
            <button type="button" class="dropdown-item-destructive w-full"
                onclick="document.getElementById('logoutModal').classList.remove('hidden')">
                <?= svg_icon('log-out'); ?>
                <span>Logout</span>
            </button>
        </div>
    </div>
</header>
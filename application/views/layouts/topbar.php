<?php
$initial = strtoupper(substr($user['nama'], 0, 1));
?>
<header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
        <button id="sidebarToggle" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Toggle sidebar">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <h1 class="text-lg font-semibold text-slate-800"><?= html_escape($title); ?></h1>
    </div>

    <div class="relative">
        <button data-dropdown="userMenu" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-3 hover:bg-slate-100">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white"><?= html_escape($initial); ?></span>
            <span class="hidden text-sm font-medium text-slate-700 sm:block"><?= html_escape($user['nama']); ?></span>
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="userMenu" data-dropdown-menu class="absolute right-0 mt-2 hidden w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
            <a href="<?= base_url('ganti-password'); ?>" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                <span>🔒</span> Ganti Password
            </a>
            <div class="my-1 border-t border-slate-100"></div>
            <button type="button" onclick="document.getElementById('logoutModal').classList.remove('hidden')" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">
                <span>🚪</span> Logout
            </button>
        </div>
    </div>
</header>
<?php
$this->load->helper('ui');
$ui_v4 = ui_variant() === 'v4';

$initial  = strtoupper(substr(isset($user['nama']) ? $user['nama'] : 'U', 0, 1));
$subtitle = isset($subtitle) ? $subtitle : '';
$role     = ! empty($user['is_admin']) ? 'Administrator' : (! empty($user['is_operator']) ? 'Operator' : 'Pengguna');

// Token semantik hanya ada di v4; v3 tetap memakai utilitas gray/white-nya.
$bar_surface = $ui_v4 ? 'bg-[var(--color-surface)]/95' : 'bg-white/90';
$bar_line    = $ui_v4 ? 'border-[var(--color-line)]' : 'border-gray-200';
$ink_strong  = $ui_v4 ? 'text-[var(--color-ink,#0f172a)]' : 'text-gray-900';
$ink_subtle  = $ui_v4 ? 'text-[var(--color-ink-subtle,#64748b)]' : 'text-gray-500';
$ink_muted   = $ui_v4 ? 'text-[var(--color-ink-muted,#475569)]' : 'text-gray-800';
$hover_surf  = $ui_v4 ? 'hover:bg-[var(--color-surface-muted)]' : 'hover:bg-gray-100';
?>
<header
    class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b <?= $bar_line; ?> <?= $bar_surface; ?> px-4 backdrop-blur sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-3">
        <button id="sidebarToggle" type="button"
            class="btn-icon lg:hidden"
            aria-label="Buka menu">
            <?= svg_icon('menu', 'h-4 w-4'); ?>
        </button>

        <div class="min-w-0">
            <?php if (isset($breadcrumb) && is_array($breadcrumb) && ! empty($breadcrumb)): ?>
                <nav aria-label="Breadcrumb" class="mb-0.5 hidden sm:block">
                    <ol class="flex items-center gap-1 text-[0.6875rem] font-medium <?= $ink_subtle; ?>">
                        <?php foreach (array_values($breadcrumb) as $i => $crumb): ?>
                            <?php
                            $label = is_array($crumb) ? (isset($crumb['label']) ? $crumb['label'] : '') : (string) $crumb;
                            $url   = is_array($crumb) && isset($crumb['url']) ? $crumb['url'] : null;
                            ?>
                            <?php if ($i > 0): ?>
                                <li aria-hidden="true"><?= svg_icon('chevron-right', 'h-3 w-3 opacity-60'); ?></li>
                            <?php endif; ?>
                            <li class="<?= $i === count($breadcrumb) - 1 ? $ink_strong : ''; ?>">
                                <?php if ($url && $i !== count($breadcrumb) - 1): ?>
                                    <a href="<?= html_escape($url); ?>" class="rounded hover:underline"><?= html_escape($label); ?></a>
                                <?php elseif ($i === count($breadcrumb) - 1): ?>
                                    <span aria-current="page"><?= html_escape($label); ?></span>
                                <?php else: ?>
                                    <span><?= html_escape($label); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>

            <h1 class="truncate text-base font-semibold leading-tight tracking-tight <?= $ink_strong; ?>">
                <?= html_escape($title); ?>
            </h1>
            <?php if ($subtitle !== ''): ?>
                <p class="truncate text-xs <?= $ink_subtle; ?>"><?= html_escape($subtitle); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- User menu (Preline dropdown) -->
    <div class="hs-dropdown relative inline-flex [--placement:bottom-right] [--auto-close:true]">
        <button id="userMenuToggle" type="button"
            class="hs-dropdown-toggle flex items-center gap-2 rounded-full py-1 pl-1 pr-2 text-sm transition-colors <?= $hover_surf; ?> focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-900"
            aria-haspopup="menu" aria-expanded="false" aria-label="Menu pengguna">
            <span class="avatar"><?= html_escape($initial); ?></span>
            <span class="hidden min-w-0 text-left leading-tight sm:block">
                <span class="block max-w-[10rem] truncate text-sm font-medium <?= $ink_muted; ?>">
                    <?= html_escape($user['nama']); ?>
                </span>
                <span class="block max-w-[10rem] truncate text-[0.6875rem] font-normal <?= $ink_subtle; ?>">
                    <?= html_escape($role); ?>
                </span>
            </span>
            <span class="hidden <?= $ink_subtle; ?> sm:block">
                <?= svg_icon('chevron-down', 'h-4 w-4 transition-transform duration-200 hs-dropdown-open:rotate-180'); ?>
            </span>
        </button>

        <div class="hs-dropdown-menu dropdown-menu absolute right-0 top-full mt-2 hidden w-60 opacity-0 transition-[opacity,margin] duration-150 [--placement:bottom-right]"
            role="menu" aria-labelledby="userMenuToggle">
            <div class="px-2.5 py-2">
                <p class="truncate text-sm font-semibold <?= $ink_strong; ?>"><?= html_escape($user['nama']); ?></p>
                <p class="truncate text-xs <?= $ink_subtle; ?>"><?= html_escape($user['nip']); ?></p>
            </div>
            <div class="dropdown-separator"></div>
            <a href="<?= base_url('assets/klasifikasi.pdf'); ?>" target="_blank" rel="noopener" class="dropdown-item" role="menuitem">
                <?= svg_icon('book'); ?>
                <span>Panduan Klasifikasi</span>
            </a>
            <div class="dropdown-separator"></div>
            <button type="button" class="dropdown-item-destructive w-full" role="menuitem"
                data-hs-overlay="#logoutModal">
                <?= svg_icon('log-out'); ?>
                <span>Logout</span>
            </button>
        </div>
    </div>
</header>
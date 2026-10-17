<?php $this->load->helper('ui'); ?>
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0A2463">
    <title>Masuk - SINOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?= ui_css_tag(); ?>
</head>

<body class="flex min-h-full items-center justify-center bg-gray-50 p-4 font-sans sm:p-6 <?= ui_body_class(); ?>">

    <main class="w-full max-w-4xl">
        <div class="card grid overflow-hidden lg:grid-cols-2">

            <!-- Panel merek (desktop) -->
            <aside
                class="relative hidden flex-col justify-between overflow-hidden bg-brand-900 p-8 text-white lg:flex"
                style="background-image:
                    radial-gradient(120% 110% at 100% 0%, rgb(251 54 64 / 0.20) 0%, rgb(251 54 64 / 0) 58%),
                    linear-gradient(155deg, rgb(10 36 99 / 0) 42%, rgb(7 26 69 / 0.92) 100%);">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10">
                            <?= svg_icon('bank', 'h-5 w-5 text-white'); ?>
                        </span>
                        <div class="leading-tight">
                            <p class="text-sm font-semibold tracking-tight">SINOS</p>
                            <p class="text-xs text-white/70">Pengadilan Agama Kupang</p>
                        </div>
                    </div>

                    <h2 class="mt-10 text-2xl font-semibold leading-snug tracking-tight">
                        Sistem Informasi<br>Nomor Surat
                    </h2>
                    <p class="mt-3 max-w-xs text-sm leading-relaxed text-white/70">
                        Pencatatan surat masuk, pengambilan nomor surat keluar, disposisi, dan pelaporan
                        dalam satu tempat.
                    </p>
                </div>

                <ul class="mt-10 space-y-3 text-sm text-white/80">
                    <li class="flex items-start gap-2.5">
                        <?= svg_icon('hash', 'mt-0.5 h-4 w-4 shrink-0 text-white/70'); ?>
                        <span>Penomoran surat otomatis dan konsisten</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <?= svg_icon('send', 'mt-0.5 h-4 w-4 shrink-0 text-white/70'); ?>
                        <span>Disposisi tercatat dengan riwayat</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <?= svg_icon('shield', 'mt-0.5 h-4 w-4 shrink-0 text-white/70'); ?>
                        <span>Autentikasi terpusat lewat SSO</span>
                    </li>
                </ul>
            </aside>

            <!-- Panel masuk -->
            <div class="flex min-w-0 flex-col">

                <!-- Header ringkas (mobile/tablet) -->
                <div class="flex items-center gap-3 bg-brand-900 px-5 py-4 text-white lg:hidden"
                    style="background-image: radial-gradient(120% 140% at 100% 0%, rgb(251 54 64 / 0.20) 0%, rgb(251 54 64 / 0) 62%);">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10">
                        <?= svg_icon('bank', 'h-5 w-5 text-white'); ?>
                    </span>
                    <div class="min-w-0 leading-tight">
                        <p class="text-sm font-semibold tracking-tight">SINOS</p>
                        <p class="truncate text-xs text-white/70">Pengadilan Agama Kupang</p>
                    </div>
                </div>

                <div class="card-content flex flex-1 flex-col justify-center p-6 sm:p-10">
                    <h1 class="text-xl font-semibold tracking-tight text-gray-900">Masuk ke SINOS</h1>
                    <p class="mt-1.5 text-sm leading-relaxed text-gray-500">
                        Autentikasi dilakukan melalui SSO. Anda akan diarahkan ke halaman login terpusat.
                    </p>

                    <?php if (! empty($error)): ?>
                        <div class="alert-destructive mt-5" role="alert">
                            <span class="mt-0.5 shrink-0"><?= svg_icon('octagon-x', 'h-4 w-4'); ?></span>
                            <div>
                                <p class="alert-title">Login gagal</p>
                                <p class="alert-description"><?= html_escape($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <a href="<?= base_url('sso/login'); ?>" class="btn btn-primary btn-lg mt-6 w-full">
                        <?= svg_icon('log-out', 'rotate-180'); ?>
                        <span>Lanjut ke SSO</span>
                    </a>

                    <?php
                    // Tautan cadangan: login lokal saat gateway SSO bermasalah.
                    $CI = &get_instance();
                    $CI->config->load('sso', TRUE);
                    if ($CI->config->item('sso_enabled', 'sso')):
                    ?>
                        <div class="separator"></div>
                        <div class="text-center">
                            <a href="<?= base_url('login?local=1'); ?>"
                                class="inline-flex items-center justify-center px-3 py-3 text-xs font-medium text-gray-500 underline-offset-4 hover:text-gray-800 hover:underline">
                                Login lokal (cadangan)
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-[var(--color-ink-subtle,#64748b)]">
            &copy; <?= date('Y'); ?> Pengadilan Agama Kupang
        </p>
    </main>

    <?= ui_js_tag(); ?>

</body>

</html>
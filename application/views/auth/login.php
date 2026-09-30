<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>Masuk - SINOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">
</head>

<body class="flex min-h-full items-center justify-center bg-muted/40 p-4 font-sans">

    <div class="w-full max-w-[26rem]">
        <!-- Brand -->
        <div class="mb-6 flex flex-col items-center text-center">
            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <?= svg_icon('bank', 'h-5 w-5 text-primary-foreground'); ?>
            </span>
            <h1 class="mt-3 text-lg font-semibold tracking-tight text-foreground">SINOS</h1>
            <p class="text-sm text-muted-foreground">Sistem Informasi Nomor Surat</p>
        </div>

        <!-- Card -->
        <div class="rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
            <div class="mb-6">
                <h2 class="text-xl font-semibold tracking-tight text-foreground">Selamat datang kembali</h2>
                <p class="mt-1 text-sm text-muted-foreground">Masukkan kredensial Anda untuk melanjutkan.</p>
            </div>

            <?php if (! empty($error)): ?>
                <div class="alert-destructive mb-5" role="alert">
                    <span class="mt-0.5 shrink-0"><?= svg_icon('octagon-x', 'h-4 w-4'); ?></span>
                    <div>
                        <p class="alert-title">Login gagal</p>
                        <p class="alert-description"><?= html_escape($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('login'); ?>" class="space-y-4">
                <div>
                    <label class="label" for="nip">Username / NIP</label>
                    <input class="input" type="text" id="nip" name="nip" placeholder="mis. admin" autocomplete="username" autofocus required>
                </div>
                <div>
                    <label class="label" for="pass">Password</label>
                    <input class="input" type="password" id="pass" name="pass" placeholder="••••••••" autocomplete="current-password" required>
                </div>
                <button type="submit" name="login" value="1" class="btn-primary w-full">
                    <?= svg_icon('log-out', 'rotate-180'); ?>
                    <span>Masuk</span>
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-muted-foreground">
            &copy; <?= date('Y'); ?> Pengadilan Agama Kupang
        </p>
    </div>

</body>

</html>
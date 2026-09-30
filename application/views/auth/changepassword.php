<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>Ganti Password - SINOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">
</head>

<body class="flex min-h-full items-center justify-center bg-muted/40 p-4 font-sans">

    <div class="w-full max-w-[26rem]">
        <div class="mb-6 flex flex-col items-center text-center">
            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <?= svg_icon('key', 'h-5 w-5 text-primary-foreground'); ?>
            </span>
            <h1 class="mt-3 text-lg font-semibold tracking-tight text-foreground">Ganti Password</h1>
            <p class="text-sm text-muted-foreground">Perbarui kredensial akun Anda</p>
        </div>

        <div class="rounded-lg border border-border bg-card p-6 shadow-sm sm:p-8">
            <?php if (! empty($error)): ?>
                <div class="alert-destructive mb-5" role="alert">
                    <span class="mt-0.5 shrink-0"><?= svg_icon('octagon-x', 'h-4 w-4'); ?></span>
                    <div>
                        <p class="alert-title">Gagal</p>
                        <p class="alert-description"><?= html_escape($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('ganti-password'); ?>" class="space-y-4">
                <div>
                    <label class="label" for="old">Password Lama</label>
                    <input class="input" type="password" id="old" name="old" autocomplete="current-password" required>
                </div>
                <div>
                    <label class="label" for="new">Password Baru</label>
                    <input class="input" type="password" id="new" name="new" autocomplete="new-password" required>
                </div>
                <div>
                    <label class="label" for="rep">Ulangi Password Baru</label>
                    <input class="input" type="password" id="rep" name="rep" autocomplete="new-password" required>
                </div>
                <div class="flex gap-2 pt-2">
                    <a href="<?= base_url('dashboard'); ?>" class="btn-outline flex-1">
                        <?= svg_icon('arrow-left'); ?>
                        <span>Kembali</span>
                    </a>
                    <button type="submit" name="simpan" value="1" class="btn-primary flex-1">
                        <?= svg_icon('check'); ?>
                        <span>Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Password - SINOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">
</head>

<body class="flex min-h-full items-center justify-center bg-gradient-to-br from-slate-900 via-brand-800 to-brand-600 p-4 font-sans">

    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl">
        <h1 class="text-xl font-bold text-slate-800">Ganti Password</h1>
        <p class="mb-6 mt-1 text-sm text-slate-500">Masukkan password lama dan password baru Anda.</p>

        <?php if (! empty($error)): ?>
            <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                <?= html_escape($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('ganti-password'); ?>" class="space-y-4">
            <div>
                <label class="form-label">Password Lama</label>
                <input class="form-input" type="password" name="old" required>
            </div>
            <div>
                <label class="form-label">Password Baru</label>
                <input class="form-input" type="password" name="new" required>
            </div>
            <div>
                <label class="form-label">Ulangi Password Baru</label>
                <input class="form-input" type="password" name="rep" required>
            </div>
            <div class="flex gap-2 pt-2">
                <a href="<?= base_url('dashboard'); ?>" class="btn-muted flex-1 justify-center">Kembali</a>
                <button type="submit" name="simpan" value="1" class="btn-primary flex-1 justify-center">Simpan</button>
            </div>
        </form>
    </div>

</body>

</html>
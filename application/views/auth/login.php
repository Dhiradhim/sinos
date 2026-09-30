<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - SINOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">
</head>

<body class="flex min-h-full items-center justify-center bg-gradient-to-br from-slate-900 via-brand-800 to-brand-600 p-4 font-sans">

    <div class="w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl md:flex">
        <!-- Panel kiri -->
        <div class="hidden flex-col justify-between bg-slate-900 p-10 text-white md:flex md:w-1/2">
            <div>
                <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-2xl font-bold">S</div>
                <h1 class="text-3xl font-bold leading-tight">SINOS</h1>
                <p class="mt-2 text-slate-300">Sistem Informasi Nomor Surat</p>
                <p class="mt-1 text-sm text-slate-400">Pengadilan Agama Kupang</p>
            </div>
            <p class="text-xs text-slate-500">Kelola nomor surat keluar, surat masuk, dan pelaporan dalam satu aplikasi.</p>
        </div>

        <!-- Panel kanan -->
        <div class="w-full p-8 sm:p-10 md:w-1/2">
            <h2 class="text-2xl font-bold text-slate-800">Selamat Datang</h2>
            <p class="mb-6 mt-1 text-sm text-slate-500">Silakan masuk untuk melanjutkan.</p>

            <?php if (! empty($error)): ?>
                <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200">
                    <?= html_escape($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('login'); ?>" class="space-y-4">
                <div>
                    <label class="form-label" for="nip">Username / NIP</label>
                    <input class="form-input" type="text" id="nip" name="nip" placeholder="Masukkan username" autofocus required>
                </div>
                <div>
                    <label class="form-label" for="pass">Password</label>
                    <input class="form-input" type="password" id="pass" name="pass" placeholder="Masukkan password" required>
                </div>
                <button type="submit" name="login" value="1" class="btn-primary w-full justify-center">Login</button>
            </form>
        </div>
    </div>

</body>

</html>
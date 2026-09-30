<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= html_escape($title); ?> - SINOS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">
</head>

<body class="h-full font-sans">

    <div class="min-h-full">
        <?php $this->load->view('layouts/sidebar'); ?>

        <!-- Overlay mobile -->
        <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-slate-900/60 lg:hidden"></div>

        <!-- Content wrapper -->
        <div class="lg:pl-64">
            <?php $this->load->view('layouts/topbar'); ?>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <?php $this->load->view('layouts/flash'); ?>

                <?php if (! empty($subtitle)): ?>
                    <p class="mb-4 text-sm text-slate-500"><?= html_escape($subtitle); ?></p>
                <?php endif; ?>

                <?= $content; ?>
            </main>

            <?php $this->load->view('layouts/footer'); ?>
        </div>
    </div>

    <?php $this->load->view('layouts/logout_modal'); ?>

    <script src="<?= base_url('assets/js/app.js'); ?>"></script>
</body>

</html>
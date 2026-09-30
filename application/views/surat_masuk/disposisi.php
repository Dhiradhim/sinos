<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lembar Disposisi - <?= html_escape($row->no_surat); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
                background: #fff;
            }

            .sheet {
                border: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>

<body class="bg-muted/40 p-4 font-sans text-sm text-foreground sm:p-6">

    <div class="no-print mx-auto mb-4 flex max-w-3xl justify-end gap-2">
        <button onclick="window.print()" class="btn-primary btn-sm">
            <?= svg_icon('printer'); ?>
            <span>Cetak</span>
        </button>
        <a href="<?= base_url('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat))); ?>" class="btn-outline btn-sm">
            <?= svg_icon('arrow-left'); ?>
            <span>Kembali</span>
        </a>
    </div>

    <div class="sheet mx-auto max-w-3xl rounded-md border border-border bg-card shadow-sm">
        <div class="border-b border-border p-4 text-center">
            <img src="<?= base_url('assets/img/KOP.jpg'); ?>" alt="Kop Surat" class="mx-auto max-h-24">
        </div>
        <div class="border-b border-border bg-muted/50 p-3 text-center text-base font-semibold uppercase tracking-wide">
            Lembar Disposisi
        </div>

        <table class="w-full border-collapse text-sm">
            <tbody>
                <tr class="border-b border-border">
                    <td class="w-48 border-r border-border bg-muted/40 px-3 py-2 font-medium text-muted-foreground">Nomor Surat</td>
                    <td class="px-3 py-2"><?= html_escape($row->no_surat); ?></td>
                </tr>
                <tr class="border-b border-border">
                    <td class="border-r border-border bg-muted/40 px-3 py-2 font-medium text-muted-foreground">Tanggal Surat</td>
                    <td class="px-3 py-2"><?= tanggal_indonesia($row->tgl_surat); ?></td>
                </tr>
                <tr class="border-b border-border">
                    <td class="border-r border-border bg-muted/40 px-3 py-2 font-medium text-muted-foreground">Tanggal Diterima</td>
                    <td class="px-3 py-2"><?= tanggal_indonesia($row->tgl_diterima); ?></td>
                </tr>
                <tr class="border-b border-border">
                    <td class="border-r border-border bg-muted/40 px-3 py-2 font-medium text-muted-foreground">Pengirim</td>
                    <td class="px-3 py-2"><?= html_escape($row->pengirim); ?></td>
                </tr>
                <tr class="border-b border-border">
                    <td class="border-r border-border bg-muted/40 px-3 py-2 font-medium text-muted-foreground">Perihal</td>
                    <td class="px-3 py-2"><?= html_escape($row->perihal); ?></td>
                </tr>
                <tr class="border-b border-border">
                    <td class="border-r border-border bg-muted/40 px-3 py-2 font-medium text-muted-foreground">Kode / Pengolah</td>
                    <td class="px-3 py-2"><?= html_escape($row->kode); ?> / <?= html_escape($row->pengolah); ?></td>
                </tr>
                <tr class="border-b border-border">
                    <td class="border-r border-border bg-muted/40 px-3 py-2 font-medium text-muted-foreground">Keterangan</td>
                    <td class="px-3 py-2"><?= html_escape($row->keterangan); ?></td>
                </tr>
                <tr>
                    <td class="border-r border-border bg-muted/40 px-3 py-2 align-top font-medium text-muted-foreground">Disposisi</td>
                    <td class="px-3 py-2">
                        <div class="h-32"></div>
                        <p class="text-xs text-muted-foreground">Diteruskan kepada / instruksi:</p>
                        <div class="mt-2 space-y-4">
                            <div class="border-b border-dashed border-border"></div>
                            <div class="border-b border-dashed border-border"></div>
                            <div class="border-b border-dashed border-border"></div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</body>

</html>
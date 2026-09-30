<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Lembar Disposisi - <?= html_escape($row->no_surat); ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-white p-6 font-sans text-sm text-slate-800">

    <div class="no-print mb-4 flex justify-end gap-2">
        <button onclick="window.print()" class="btn-primary">Cetak</button>
        <a href="<?= base_url('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat))); ?>" class="btn-muted">Kembali</a>
    </div>

    <div class="mx-auto max-w-3xl border border-slate-300">
        <div class="border-b border-slate-300 p-4 text-center">
            <img src="<?= base_url('img/KOP.jpg'); ?>" alt="Kop Surat" class="mx-auto max-h-24">
        </div>
        <div class="border-b border-slate-300 bg-slate-50 p-3 text-center text-base font-bold uppercase">Lembar Disposisi</div>

        <table class="w-full border-collapse text-sm">
            <tbody>
                <tr class="border-b border-slate-200">
                    <td class="w-48 border-r border-slate-200 bg-slate-50 px-3 py-2 font-medium">Nomor Surat</td>
                    <td class="px-3 py-2"><?= html_escape($row->no_surat); ?></td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="border-r border-slate-200 bg-slate-50 px-3 py-2 font-medium">Tanggal Surat</td>
                    <td class="px-3 py-2"><?= tanggal_indonesia($row->tgl_surat); ?></td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="border-r border-slate-200 bg-slate-50 px-3 py-2 font-medium">Tanggal Diterima</td>
                    <td class="px-3 py-2"><?= tanggal_indonesia($row->tgl_diterima); ?></td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="border-r border-slate-200 bg-slate-50 px-3 py-2 font-medium">Pengirim</td>
                    <td class="px-3 py-2"><?= html_escape($row->pengirim); ?></td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="border-r border-slate-200 bg-slate-50 px-3 py-2 font-medium">Perihal</td>
                    <td class="px-3 py-2"><?= html_escape($row->perihal); ?></td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="border-r border-slate-200 bg-slate-50 px-3 py-2 font-medium">Kode / Pengolah</td>
                    <td class="px-3 py-2"><?= html_escape($row->kode); ?> / <?= html_escape($row->pengolah); ?></td>
                </tr>
                <tr class="border-b border-slate-200">
                    <td class="border-r border-slate-200 bg-slate-50 px-3 py-2 font-medium">Keterangan</td>
                    <td class="px-3 py-2"><?= html_escape($row->keterangan); ?></td>
                </tr>
                <tr>
                    <td class="border-r border-slate-200 bg-slate-50 px-3 py-2 font-medium align-top">Disposisi</td>
                    <td class="px-3 py-2">
                        <div class="h-32"></div>
                        <p class="text-xs text-slate-400">Diteruskan kepada / instruksi:</p>
                        <div class="mt-2 space-y-4">
                            <div class="border-b border-dashed border-slate-300"></div>
                            <div class="border-b border-dashed border-slate-300"></div>
                            <div class="border-b border-dashed border-slate-300"></div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</body>

</html>
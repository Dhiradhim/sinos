<?php

/**
 * Dashboard - ringkasan aktivitas.
 * Stat cards + tabel nomor surat terbaru.
 */
$stats = array(
    array(
        'label' => 'Total Surat Keluar',
        'value' => (int) $total_surat_keluar,
        'icon'  => 'folder',
        'tone'  => 'text-primary bg-primary/10',
    ),
    array(
        'label' => 'Total Surat Masuk',
        'value' => (int) $total_surat_masuk,
        'icon'  => 'inbox',
        'tone'  => 'text-indigo-600 bg-indigo-100',
    ),
    array(
        'label' => 'Surat Keluar Saya · ' . html_escape($tahun),
        'value' => (int) $surat_keluar_saya,
        'icon'  => 'user',
        'tone'  => 'text-amber-600 bg-amber-100',
    ),
    array(
        'label' => 'Belum Upload Berkas',
        'value' => (int) $belum_upload,
        'icon'  => 'upload',
        'tone'  => $belum_upload > 0 ? 'text-destructive bg-destructive/10' : 'text-emerald-600 bg-emerald-100',
    ),
);
?>

<!-- Stat cards -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($stats as $s): ?>
        <div class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-muted-foreground"><?= $s['label']; ?></p>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md <?= $s['tone']; ?>">
                    <?= svg_icon($s['icon'], 'h-4 w-4'); ?>
                </span>
            </div>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-foreground"><?= $s['value']; ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Tabel terbaru -->
<div class="mt-6 card">
    <div class="flex items-center justify-between border-b border-border px-6 py-4">
        <div>
            <h2 class="text-base font-semibold tracking-tight text-foreground">Nomor Surat Terbaru</h2>
            <p class="text-xs text-muted-foreground">5 nomor surat keluar terakhir milik Anda pada <?= html_escape($tahun); ?></p>
        </div>
        <a href="<?= base_url('surat-keluar/daftar?tahun=' . $tahun); ?>" class="btn-ghost btn-sm">
            <span>Lihat semua</span>
            <?= svg_icon('arrow-right'); ?>
        </a>
    </div>

    <?php if (empty($terbaru)): ?>
        <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <?= svg_icon('file-text', 'h-5 w-5'); ?>
            </span>
            <p class="text-sm font-medium text-foreground">Belum ada nomor surat</p>
            <p class="text-sm text-muted-foreground">Nomor surat untuk tahun ini belum dibuat.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No. Surat</th>
                        <th>Tanggal</th>
                        <th>Perihal</th>
                        <th>Tujuan</th>
                        <th class="text-right">Berkas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($terbaru as $s): ?>
                        <tr>
                            <td class="font-medium"><?= html_escape($s->no); ?></td>
                            <td class="whitespace-nowrap text-muted-foreground"><?= tanggal_indonesia($s->tanggal); ?></td>
                            <td><?= html_escape($s->hal); ?></td>
                            <td class="text-muted-foreground"><?= html_escape($s->tujuan); ?></td>
                            <td class="text-right">
                                <?php if ($s->file == '1'): ?>
                                    <span class="badge-warning">Belum ada</span>
                                <?php elseif (empty($s->file)): ?>
                                    <span class="badge-muted">&mdash;</span>
                                <?php else: ?>
                                    <a href="<?= base_url($s->file); ?>" target="_blank" rel="noopener" class="badge-success">Lihat</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
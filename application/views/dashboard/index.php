<?php

/**
 * Dashboard - ringkasan aktivitas.
 * Stat cards + tabel nomor surat terbaru (gaya Preline).
 */
$stats = array(
    array(
        'label' => 'Total Surat Keluar',
        'value' => (int) $total_surat_keluar,
        'icon'  => 'folder',
        'tone'  => 'bg-brand-50 text-brand-900',
        'href'  => base_url('surat-keluar/daftar-semua?tahun=' . $tahun),
    ),
    array(
        'label' => 'Total Surat Masuk',
        'value' => (int) $total_surat_masuk,
        'icon'  => 'inbox',
        'tone'  => 'bg-sky-50 text-sky-700',
        'href'  => base_url('surat-masuk/daftar?tahun=' . $tahun),
    ),
    array(
        'label' => 'Surat Keluar Saya',
        'value' => (int) $surat_keluar_saya,
        'icon'  => 'user',
        'tone'  => 'bg-amber-50 text-amber-700',
        'href'  => base_url('surat-keluar/daftar?tahun=' . $tahun),
    ),
    array(
        'label' => 'Belum Upload Berkas',
        'value' => (int) $belum_upload,
        'icon'  => 'upload',
        'tone'  => $belum_upload > 0
            ? 'bg-red-50 text-red-700'
            : 'bg-emerald-50 text-emerald-700',
        'href'  => base_url('surat-keluar/daftar?tahun=' . $tahun),
    ),
);
?>

<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="section-title">Ringkasan <?= (int) $tahun; ?></h2>
            <p class="section-subtitle">Aktivitas surat masuk dan keluar pada tahun berjalan.</p>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($stats as $s): ?>
            <a href="<?= html_escape($s['href']); ?>"
                class="card focus-ring transition-colors hover:border-brand-300">
                <div class="card-content flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs text-[var(--color-ink-subtle,#6b7280)]"><?= html_escape($s['label']); ?></p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-[var(--color-ink,#111827)]">
                            <?= number_format($s['value'], 0, ',', '.'); ?>
                        </p>
                    </div>
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg rounded-[var(--radius-control,0.625rem)] <?= $s['tone']; ?>">
                        <?= svg_icon($s['icon'], 'h-5 w-5'); ?>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Tabel terbaru -->
    <div class="card">
        <div class="card-header">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="card-title">Nomor Surat Terbaru</h2>
                    <p class="card-description">5 nomor surat keluar terakhir milik Anda pada <?= html_escape($tahun); ?></p>
                </div>
                <a href="<?= base_url('surat-keluar/daftar?tahun=' . $tahun); ?>"
                    class="btn-outline btn-sm shrink-0">
                    <span>Lihat semua</span>
                    <?= svg_icon('arrow-right'); ?>
                </a>
            </div>
        </div>

        <?php if (empty($terbaru)): ?>
            <div class="empty-state">
                <span class="empty-state-icon">
                    <?= svg_icon('file-text', 'h-5 w-5'); ?>
                </span>
                <div>
                    <p class="text-sm font-medium text-[var(--color-ink,#111827)]">Belum ada nomor surat</p>
                    <p class="mt-1 text-sm text-[var(--color-ink-subtle,#6b7280)]">Nomor surat untuk tahun ini belum dibuat.</p>
                </div>
                <a href="<?= base_url('surat-keluar/ambil'); ?>" class="btn-primary btn-sm mt-1">
                    <?= svg_icon('plus'); ?>
                    <span>Ambil Nomor</span>
                </a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-prose">No. Surat</th>
                            <th>Tanggal</th>
                            <th class="col-prose">Perihal</th>
                            <th class="col-prose">Tujuan</th>
                            <th class="col-aksi">Berkas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terbaru as $s): ?>
                            <tr>
                                <td class="col-prose whitespace-nowrap font-medium tabular-nums text-[var(--color-ink,#111827)]"><?= html_escape($s->no); ?></td>
                                <td class="col-nowrap"><?= tanggal_indonesia($s->tanggal); ?></td>
                                <td class="col-prose"><?= html_escape($s->hal); ?></td>
                                <td class="col-prose"><?= html_escape($s->tujuan); ?></td>
                                <td class="col-aksi">
                                    <?php if ($s->file == '1'): ?>
                                        <span class="badge-warning">Belum ada</span>
                                    <?php elseif (empty($s->file)): ?>
                                        <span class="badge-muted">&mdash;</span>
                                    <?php else: ?>
                                        <a href="<?= base_url($s->file); ?>" target="_blank" rel="noopener" class="badge-success">
                                            <?= svg_icon('eye', 'h-3 w-3'); ?>Lihat
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
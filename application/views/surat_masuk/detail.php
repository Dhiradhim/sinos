<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <!-- Detail surat -->
    <div class="card lg:col-span-2">
        <div class="card-header">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="card-title">Detail Surat Masuk</h2>
                    <p class="card-description">Informasi lengkap surat masuk.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <?php if ($row->status === 'diarsipkan'): ?>
                        <span class="badge-secondary">Arsip</span>
                    <?php endif; ?>
                    <?php if (! empty($row->file)): ?>
                        <a href="<?= base_url($row->file); ?>" target="_blank" rel="noopener" class="btn-outline btn-sm">
                            <?= svg_icon('file-text'); ?>
                            <span>Lihat Berkas Surat</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('surat-masuk/daftar?tahun=' . date('Y', strtotime($row->tgl_surat))); ?>" class="btn-outline btn-sm">
                        <?= svg_icon('arrow-left'); ?>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-content">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">No. Surat</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($row->no_surat); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">No. Agenda</dt>
                    <dd class="mt-0.5 text-sm font-medium tabular-nums text-[var(--color-ink,#111827)]"><?= html_escape($row->no_agenda); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Tanggal Surat</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= tanggal_indonesia($row->tgl_surat); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Tanggal Diterima</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= tanggal_indonesia($row->tgl_diterima); ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Perihal</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($row->perihal); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Pengirim</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($row->pengirim); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Pengolah</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($row->pengolah); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Kode Klasifikasi</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($row->kode); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Keterangan</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape(isset($row->keterangan) && $row->keterangan !== '' ? $row->keterangan : '—'); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Posisi disposisi & riwayat -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Posisi Disposisi</h2>
        </div>
        <div class="card-content">
            <?php if ($posisi): ?>
                <div class="rounded-lg bg-gray-50 p-3">
                    <p class="text-xs text-gray-500">Saat ini di</p>
                    <p class="mt-0.5 font-medium text-gray-900"><?= html_escape($posisi->kepada); ?></p>
                    <p class="text-xs text-gray-500"><?= html_escape($posisi->kategori); ?></p>
                    <p class="mt-2">
                        <?php if ((int) $posisi->dikembalikan === 1): ?>
                            <span class="badge-success">Dikembalikan ke operator</span>
                        <?php elseif ((int) $posisi->dibaca === 1): ?>
                            <span class="badge-default">Sudah dibaca</span>
                        <?php else: ?>
                            <span class="badge-warning">Belum dibaca</span>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-500">Surat ini belum pernah didisposisi.</p>
            <?php endif; ?>

            <?php if (! empty($riwayat)): ?>
                <div class="mt-5">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Riwayat Disposisi</p>
                    <ol class="space-y-2">
                        <?php foreach ($riwayat as $rw): ?>
                            <li class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                <span class="font-medium text-gray-900"><?= html_escape($rw->kepada); ?></span>
                                <span class="block text-xs text-gray-500"><?= date('d-m-Y H:i', strtotime($rw->tanggal)); ?></span>
                                <?php if (! empty($rw->instruksi)): ?>
                                    <span class="block text-xs text-gray-500"><?= html_escape($rw->instruksi); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
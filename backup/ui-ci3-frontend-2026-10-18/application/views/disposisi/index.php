<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Disposisi Surat</h2>
        <p class="card-description">
            <?= ! empty($is_operator)
                ? 'Seluruh surat yang sedang didisposisi (belum diarsipkan).'
                : 'Surat yang posisi disposisinya sedang ada pada Anda.'; ?>
        </p>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <span class="empty-state-icon"><?= svg_icon('send', 'h-5 w-5'); ?></span>
            <p class="text-sm font-medium text-[var(--color-ink,#111827)]">Tidak ada disposisi</p>
            <p class="text-sm text-[var(--color-ink-subtle,#6b7280)]">
                <?= ! empty($is_operator)
                    ? 'Belum ada surat yang sedang didisposisi.'
                    : 'Tidak ada surat yang posisi disposisinya ada pada Anda.'; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table" id="tblDisposisi" data-datatable data-nosort="7">
                <thead>
                    <tr>
                        <th class="w-10">No.</th>
                        <th>Agenda / No. Surat</th>
                        <th class="col-nowrap">Tanggal</th>
                        <th class="col-prose">Pengirim Surat</th>
                        <th class="col-prose">Perihal</th>
                        <th class="col-nowrap">Posisi Saat Ini</th>
                        <th class="col-nowrap">Instruksi</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <?php $diarsipkan = ($r->status_surat === 'diarsipkan'); ?>
                        <tr class="<?= $diarsipkan ? 'bg-[var(--color-surface-muted,#f8fafc)]' : ''; ?>">
                            <td class="tabular-nums text-[var(--color-ink-subtle,#6b7280)]"><?= $i++; ?></td>
                            <td class="col-nowrap">
                                <span class="block font-medium leading-snug text-[var(--color-ink,#111827)]"><?= html_escape($r->no_surat); ?></span>
                                <span class="mt-0.5 block text-xs tabular-nums text-[var(--color-ink-subtle,#6b7280)]"><?= html_escape($r->no_agenda); ?></span>
                                <?php if ($diarsipkan): ?>
                                    <span class="badge-muted mt-1">Diarsipkan</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-nowrap text-[var(--color-ink-subtle,#6b7280)]" data-order="<?= html_escape($r->tgl_surat); ?>"><?= tanggal_indonesia($r->tgl_surat); ?></td>
                            <td class="col-prose"><?= html_escape($r->pengirim); ?></td>
                            <td class="col-prose"><?= html_escape($r->perihal); ?></td>
                            <td class="col-nowrap">
                                <span class="badge-info"><?= html_escape(isset($r->lokasi) && $r->lokasi !== '' ? $r->lokasi : $r->kepada); ?></span>
                                <span class="mt-1 block text-xs text-[var(--color-ink-subtle,#6b7280)]"><?= html_escape($r->kategori); ?></span>
                            </td>
                            <td class="col-nowrap">
                                <span class="block text-sm text-[var(--color-ink-muted,#475569)]"><?= html_escape(isset($r->instruksi) && $r->instruksi !== '' ? $r->instruksi : '—'); ?></span>
                                <span class="mt-1 block">
                                    <?php if ($diarsipkan): ?>
                                        <span class="badge-muted">Sudah diarsipkan</span>
                                    <?php elseif ((int) $r->dikembalikan === 1): ?>
                                        <span class="badge-success">Dikembalikan</span>
                                    <?php elseif ((int) $r->dibaca === 1): ?>
                                        <span class="badge-outline">Dibaca</span>
                                    <?php else: ?>
                                        <span class="badge-warning">Belum dibaca</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="col-aksi">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="<?= base_url('disposisi/detail/' . $r->id); ?>" class="btn-icon" title="Detail">
                                        <?= svg_icon('eye', 'h-4 w-4'); ?>
                                    </a>

                                    <?php
                                    $aksi_lain = array();
                                    if (! empty($is_operator) && ! empty($r->posisi_di_operator)) {
                                        $aksi_lain[] = array(
                                            'href'    => base_url('disposisi/arsipkan/' . $r->surmas_id),
                                            'icon'    => 'archive',
                                            'label'   => 'Arsipkan surat',
                                            'confirm' => 'Arsipkan surat ini?',
                                        );
                                    }
                                    if (! empty($user['is_admin']) || $r->dari === $user['nip']) {
                                        $aksi_lain[] = array(
                                            'href'    => base_url('disposisi/hapus/' . $r->id),
                                            'icon'    => 'trash',
                                            'label'   => 'Hapus disposisi',
                                            'confirm' => 'Hapus disposisi ini?',
                                            'danger'  => TRUE,
                                        );
                                    }
                                    ?>

                                    <?php if (! empty($aksi_lain)): ?>
                                        <?php /* `strategy: fixed` membuat panel dirender relatif viewport,
                                                 sehingga tidak terpotong `overflow-x: auto` pada .table-wrap. */ ?>
                                        <div class="hs-dropdown relative inline-flex [--placement:bottom-right]"
                                            data-hs-dropdown-options='{"strategy":"fixed"}'>
                                            <button type="button" class="hs-dropdown-toggle btn-icon" aria-haspopup="menu" aria-expanded="false" title="Aksi lainnya">
                                                <?= svg_icon('ellipsis', 'h-4 w-4'); ?>
                                            </button>
                                            <div class="hs-dropdown-menu dropdown-menu absolute right-0 top-full mt-1 hidden w-56 opacity-0 transition-[opacity,margin] duration-150" role="menu">
                                                <?php foreach ($aksi_lain as $a): ?>
                                                    <a href="<?= $a['href']; ?>"
                                                        class="dropdown-item <?= ! empty($a['danger']) ? 'text-red-600 hover:bg-red-50 [&_svg]:text-red-500' : ''; ?>"
                                                        <?= ! empty($a['confirm']) ? 'data-confirm="' . html_escape($a['confirm']) . '"' : ''; ?>
                                                        role="menuitem">
                                                        <?= svg_icon($a['icon']); ?>
                                                        <span><?= html_escape($a['label']); ?></span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<div class="card">
    <div class="flex flex-col gap-4 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold tracking-tight text-foreground">Daftar Disposisi Surat</h2>
            <p class="text-xs text-muted-foreground">
                <?= ! empty($is_operator)
                    ? 'Seluruh surat yang sedang didisposisi (belum diarsipkan).'
                    : 'Surat yang posisi disposisinya sedang ada pada Anda.'; ?>
            </p>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <?= svg_icon('send', 'h-5 w-5'); ?>
            </span>
            <p class="text-sm font-medium text-foreground">Tidak ada disposisi</p>
            <p class="text-sm text-muted-foreground">
                <?= ! empty($is_operator)
                    ? 'Belum ada surat yang sedang didisposisi.'
                    : 'Tidak ada surat yang posisi disposisinya ada pada Anda.'; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="table-wrap p-2">
            <table class="table" id="tblDisposisi" data-datatable data-nosort="8">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Agenda</th>
                        <th>No. Surat</th>
                        <th>Tanggal</th>
                        <th>Pengirim Surat</th>
                        <th>Perihal</th>
                        <th>Posisi Saat Ini</th>
                        <th>Instruksi</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <?php $diarsipkan = ($r->status_surat === 'diarsipkan'); ?>
                        <tr class="<?= $diarsipkan ? 'opacity-70' : ''; ?>">
                            <td class="text-muted-foreground">
                                <?= $i++; ?>
                                <?php if ($diarsipkan): ?>
                                    <span class="badge-secondary ml-1">Arsip</span>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($r->no_agenda); ?></td>
                            <td class="font-medium"><?= html_escape($r->no_surat); ?></td>
                            <td class="whitespace-nowrap text-muted-foreground" data-order="<?= html_escape($r->tgl_surat); ?>"><?= tanggal_indonesia($r->tgl_surat); ?></td>
                            <td><?= html_escape($r->pengirim); ?></td>
                            <td><?= html_escape($r->perihal); ?></td>
                            <td>
                                <span class="badge-default"><?= html_escape(isset($r->lokasi) && $r->lokasi !== '' ? $r->lokasi : $r->kepada); ?></span>
                                <span class="mt-0.5 block text-xs text-muted-foreground"><?= html_escape($r->kategori); ?></span>
                            </td>
                            <td class="text-muted-foreground">
                                <?= html_escape(isset($r->instruksi) && $r->instruksi !== '' ? $r->instruksi : '—'); ?>
                                <span class="mt-0.5 block">
                                    <?php if ($diarsipkan): ?>
                                        <span class="badge-secondary">Sudah Diarsipkan</span>
                                    <?php elseif ((int) $r->dikembalikan === 1): ?>
                                        <span class="badge-success">Dikembalikan</span>
                                    <?php elseif ((int) $r->dibaca === 1): ?>
                                        <span class="badge-default">Dibaca</span>
                                    <?php else: ?>
                                        <span class="badge-muted">Belum dibaca</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="col-aksi">
                                <div class="flex justify-end gap-1.5">
                                    <a href="<?= base_url('disposisi/detail/' . $r->id); ?>" class="btn-icon" title="Detail">
                                        <?= svg_icon('eye', 'h-4 w-4'); ?>
                                    </a>
                                    <?php if (! empty($is_operator) && ! empty($r->posisi_di_operator)): ?>
                                        <a href="<?= base_url('disposisi/arsipkan/' . $r->surmas_id); ?>" data-confirm="Arsipkan surat ini?" class="btn-icon text-emerald-600 hover:bg-emerald-50" title="Arsipkan Surat">
                                            <?= svg_icon('archive', 'h-4 w-4'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (! empty($user['is_admin']) || $r->dari === $user['nip']): ?>
                                        <a href="<?= base_url('disposisi/hapus/' . $r->id); ?>" data-confirm="Hapus disposisi ini?" class="btn-icon text-destructive hover:bg-destructive/10 hover:text-destructive" title="Hapus">
                                            <?= svg_icon('trash', 'h-4 w-4'); ?>
                                        </a>
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
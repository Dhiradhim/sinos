<div class="card">
    <div class="flex flex-col gap-4 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold tracking-tight text-foreground">Daftar Pengguna</h2>
            <p class="text-xs text-muted-foreground">Kelola akun pengguna aplikasi SINOS.</p>
        </div>
        <a href="<?= base_url('user/tambah'); ?>" class="btn-primary btn-sm">
            <?= svg_icon('plus'); ?>
            <span>Tambah User</span>
        </a>
    </div>

    <?php if (empty($rows)): ?>
        <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <?= svg_icon('users', 'h-5 w-5'); ?>
            </span>
            <p class="text-sm font-medium text-foreground">Belum ada data user</p>
            <p class="text-sm text-muted-foreground">Tambahkan user baru untuk memulai.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap p-2">
            <table class="table" id="tblUser" data-datatable data-nosort="5">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>NIP / Username</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Status</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr>
                            <td class="text-muted-foreground"><?= $i++; ?></td>
                            <td class="font-medium"><?= html_escape($r->nip); ?></td>
                            <td><?= html_escape($r->nama); ?></td>
                            <td class="text-muted-foreground">
                                <?= html_escape(isset($r->jabatan) ? $r->jabatan : '—'); ?><?= ! empty($r->subbag) ? ' / ' . html_escape($r->subbag) : ''; ?>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <?php if ((int) $r->aktif === 0): ?>
                                        <span class="badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                    <?php if ((int) $r->operator === 1): ?>
                                        <span class="badge-default">Operator</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="col-aksi">
                                <div class="flex justify-end gap-1.5">
                                    <a href="<?= base_url('user/edit/' . $r->id); ?>" class="btn-icon" title="Edit">
                                        <?= svg_icon('edit', 'h-4 w-4'); ?>
                                    </a>
                                    <?php if ($r->nip !== 'admin'): ?>
                                        <a href="<?= base_url('user/hapus/' . $r->id); ?>" data-confirm="Hapus user ini?" class="btn-icon text-destructive hover:bg-destructive/10 hover:text-destructive" title="Hapus">
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
<div class="card">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-base font-semibold text-slate-700">Daftar Pengguna</h2>
        <a href="<?= base_url('user/tambah'); ?>" class="btn-primary whitespace-nowrap">+ Tambah User</a>
    </div>

    <?php if (empty($rows)): ?>
        <p class="py-10 text-center text-sm text-slate-400">Belum ada data user.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table-modern" id="tblUser" data-datatable data-nosort="5">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>NIP / Username</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td class="font-medium text-slate-700"><?= html_escape($r->nip); ?></td>
                            <td><?= html_escape($r->nama); ?></td>
                            <td><?= html_escape(isset($r->jabatan) ? $r->jabatan : '-'); ?><?= ! empty($r->subbag) ? ' / ' . html_escape($r->subbag) : ''; ?></td>
                            <td>
                                <?php if ((int) $r->aktif === 0): ?>
                                    <span class="badge bg-emerald-100 text-emerald-700">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-slate-200 text-slate-600">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="<?= base_url('user/edit/' . $r->id); ?>" class="btn-icon bg-sky-600 hover:bg-sky-700" title="Edit">✎</a>
                                    <?php if ($r->nip !== 'admin'): ?>
                                        <a href="<?= base_url('user/hapus/' . $r->id); ?>" data-confirm="Hapus user ini?" class="btn-icon bg-rose-600 hover:bg-rose-700" title="Hapus">🗑</a>
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
<div class="card">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-base font-semibold text-slate-700">Tahun <?= (int) $tahun; ?></h2>
        <div class="flex gap-2">
            <input type="text" data-table-search="tblMasuk" placeholder="Cari surat..." class="form-input sm:w-64">
            <a href="<?= base_url('surat-masuk'); ?>" class="btn-primary whitespace-nowrap">+ Input Surat</a>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <p class="py-10 text-center text-sm text-slate-400">Belum ada data surat masuk untuk tahun <?= (int) $tahun; ?>.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table-modern" id="tblMasuk">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Agenda</th>
                        <th>No. Surat</th>
                        <th>Tanggal</th>
                        <th>Pengirim</th>
                        <th>Perihal</th>
                        <th>Pengolah</th>
                        <th>Berkas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= html_escape($r->no_agenda); ?></td>
                            <td class="font-medium text-slate-700"><?= html_escape($r->no_surat); ?></td>
                            <td><?= tanggal_indonesia($r->tgl_surat); ?></td>
                            <td><?= html_escape($r->pengirim); ?></td>
                            <td><?= html_escape($r->perihal); ?></td>
                            <td><?= html_escape($r->pengolah); ?></td>
                            <td>
                                <?php if (! empty($r->file)): ?>
                                    <a href="<?= base_url($r->file); ?>" target="_blank" class="badge bg-emerald-100 text-emerald-700">Lihat</a>
                                <?php else: ?>
                                    <span class="badge bg-slate-100 text-slate-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="<?= base_url('surat-masuk/edit/' . $r->id); ?>" class="btn-icon bg-sky-600 hover:bg-sky-700" title="Edit">✎</a>
                                    <a href="<?= base_url('surat-masuk/disposisi/' . $r->id); ?>" target="_blank" class="btn-icon bg-slate-600 hover:bg-slate-700" title="Disposisi">🖨</a>
                                    <?php if (! empty($user['is_admin'])): ?>
                                        <a href="<?= base_url('surat-masuk/hapus/' . $r->id); ?>" data-confirm="Hapus surat ini?" class="btn-icon bg-rose-600 hover:bg-rose-700" title="Hapus">🗑</a>
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
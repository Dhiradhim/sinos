<div class="card">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-base font-semibold text-slate-700">Tahun <?= (int) $tahun; ?></h2>
        <div class="flex gap-2">
            <input type="text" data-table-search="tblKeluar" placeholder="Cari surat..." class="form-input sm:w-64">
            <a href="<?= base_url('surat-keluar/ambil'); ?>" class="btn-primary whitespace-nowrap">+ Ambil Nomor</a>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <p class="py-10 text-center text-sm text-slate-400">Belum ada data nomor surat untuk tahun <?= (int) $tahun; ?>.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table-modern" id="tblKeluar">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Surat</th>
                        <th>Nama</th>
                        <th>Tanggal</th>
                        <th>Perihal</th>
                        <th>Tujuan</th>
                        <th>Berkas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr class="<?= $r->file == '1' ? 'bg-rose-50/60' : ''; ?>">
                            <td><?= $i++; ?></td>
                            <td class="font-medium text-slate-700"><?= html_escape($r->no); ?></td>
                            <td><?= html_escape($r->nama); ?></td>
                            <td><?= tanggal_indonesia($r->tanggal); ?></td>
                            <td><?= html_escape($r->hal); ?></td>
                            <td><?= html_escape($r->tujuan); ?></td>
                            <td>
                                <?php if ($r->file == '1'): ?>
                                    <span class="badge bg-amber-100 text-amber-700">Belum ada</span>
                                <?php elseif (empty($r->file)): ?>
                                    <span class="badge bg-slate-100 text-slate-500">-</span>
                                <?php else: ?>
                                    <a href="<?= base_url($r->file); ?>" target="_blank" class="badge bg-emerald-100 text-emerald-700">Lihat</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="<?= base_url('surat-keluar/upload/' . $r->id); ?>" class="btn-icon bg-brand-600 hover:bg-brand-700" title="Upload Berkas">⬆</a>
                                    <a href="<?= base_url('surat-keluar/edit/' . $r->id); ?>" class="btn-icon bg-sky-600 hover:bg-sky-700" title="Edit">✎</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
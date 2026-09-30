<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="card">
        <p class="text-sm text-slate-500">Total Surat Keluar</p>
        <p class="mt-2 text-3xl font-bold text-slate-800"><?= (int) $total_surat_keluar; ?></p>
    </div>
    <div class="card">
        <p class="text-sm text-slate-500">Total Surat Masuk</p>
        <p class="mt-2 text-3xl font-bold text-slate-800"><?= (int) $total_surat_masuk; ?></p>
    </div>
    <div class="card">
        <p class="text-sm text-slate-500">Surat Keluar Saya (<?= html_escape($tahun); ?>)</p>
        <p class="mt-2 text-3xl font-bold text-slate-800"><?= (int) $surat_keluar_saya; ?></p>
    </div>
    <div class="card">
        <p class="text-sm text-slate-500">Belum Upload Berkas</p>
        <p class="mt-2 text-3xl font-bold <?= $belum_upload > 0 ? 'text-rose-600' : 'text-emerald-600'; ?>"><?= (int) $belum_upload; ?></p>
    </div>
</div>

<div class="mt-6 card">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-700">Nomor Surat Terbaru Saya</h2>
        <a href="<?= base_url('surat-keluar/daftar?tahun=' . $tahun); ?>" class="text-sm font-medium text-brand-600 hover:text-brand-700">Lihat semua</a>
    </div>

    <?php if (empty($terbaru)): ?>
        <p class="py-8 text-center text-sm text-slate-400">Belum ada nomor surat untuk tahun ini.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>No. Surat</th>
                        <th>Tanggal</th>
                        <th>Perihal</th>
                        <th>Tujuan</th>
                        <th>Berkas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($terbaru as $s): ?>
                        <tr>
                            <td class="font-medium text-slate-700"><?= html_escape($s->no); ?></td>
                            <td><?= tanggal_indonesia($s->tanggal); ?></td>
                            <td><?= html_escape($s->hal); ?></td>
                            <td><?= html_escape($s->tujuan); ?></td>
                            <td>
                                <?php if ($s->file == '1'): ?>
                                    <span class="badge bg-amber-100 text-amber-700">Belum ada</span>
                                <?php elseif (empty($s->file)): ?>
                                    <span class="badge bg-slate-100 text-slate-500">-</span>
                                <?php else: ?>
                                    <a href="<?= base_url($s->file); ?>" target="_blank" class="badge bg-emerald-100 text-emerald-700">Lihat</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
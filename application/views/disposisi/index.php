<div class="card">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-base font-semibold text-slate-700">Daftar Disposisi Surat</h2>
            <?php $tahun_action = base_url('disposisi');
            $this->load->view('layouts/_tahun_selector'); ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <p class="py-10 text-center text-sm text-slate-400">Belum ada disposisi untuk tahun <?= (int) $tahun; ?>.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table-modern" id="tblDisposisi" data-datatable data-nosort="8">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Agenda</th>
                        <th>No. Surat</th>
                        <th>Tanggal</th>
                        <th>Pengirim Surat</th>
                        <th>Perihal</th>
                        <th>Diteruskan Kepada</th>
                        <th>Instruksi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr class="<?= ((int) $r->dibaca === 0 && $r->nip_tujuan === $user['nip']) ? 'bg-brand-50/60' : ''; ?>">
                            <td><?= $i++; ?></td>
                            <td><?= html_escape($r->no_agenda); ?></td>
                            <td class="font-medium text-slate-700"><?= html_escape($r->no_surat); ?></td>
                            <td data-order="<?= html_escape($r->tgl_surat); ?>"><?= tanggal_indonesia($r->tgl_surat); ?></td>
                            <td><?= html_escape($r->pengirim); ?></td>
                            <td><?= html_escape($r->perihal); ?></td>
                            <td>
                                <span class="badge bg-brand-100 text-brand-700"><?= html_escape($r->kepada); ?></span>
                                <span class="block text-xs text-slate-400"><?= html_escape($r->kategori); ?></span>
                            </td>
                            <td><?= html_escape(isset($r->instruksi) ? $r->instruksi : '-'); ?></td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="<?= base_url('disposisi/detail/' . $r->id); ?>" class="btn-icon bg-sky-600 hover:bg-sky-700" title="Detail">👁</a>
                                    <?php if (! empty($user['is_admin']) || $r->dari === $user['nip']): ?>
                                        <a href="<?= base_url('disposisi/hapus/' . $r->id); ?>" data-confirm="Hapus disposisi ini?" class="btn-icon bg-rose-600 hover:bg-rose-700" title="Hapus">🗑</a>
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
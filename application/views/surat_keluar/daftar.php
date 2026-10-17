<div class="card overflow-hidden">
    <div class="card-header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="card-title">Daftar Nomor Surat</h2>
                <p class="card-description">Nomor surat keluar yang Anda ambil pada periode terpilih.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <?php $tahun_action = base_url('surat-keluar/daftar');
                $this->load->view('layouts/_tahun_selector'); ?>
                <a href="<?= base_url('surat-keluar/ambil'); ?>" class="btn-primary btn-sm">
                    <?= svg_icon('plus'); ?>
                    <span>Ambil Nomor</span>
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <span class="empty-state-icon"><?= svg_icon('file-text', 'h-5 w-5'); ?></span>
            <p class="text-sm font-medium text-gray-900">Belum ada data</p>
            <p class="text-sm text-gray-500">Tidak ada nomor surat untuk tahun <?= (int) $tahun; ?>.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table" id="tblKeluar" data-datatable data-nosort="7">
                <thead>
                    <tr>
                        <th class="w-10">No.</th>
                        <th>No. Surat</th>
                        <th>Nama</th>
                        <th class="col-nowrap">Tanggal</th>
                        <th class="col-prose">Perihal</th>
                        <th class="col-prose">Tujuan</th>
                        <th class="col-nowrap">Berkas</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr>
                            <td class="tabular-nums text-xs text-gray-400"><?= $i++; ?></td>
                            <td class="whitespace-nowrap font-medium tabular-nums text-gray-900"><?= html_escape($r->no); ?></td>
                            <td><?= html_escape($r->nama); ?></td>
                            <td class="whitespace-nowrap text-gray-500" data-order="<?= html_escape($r->tanggal); ?>"><?= tanggal_indonesia($r->tanggal); ?></td>
                            <td class="col-prose"><?= html_escape($r->hal); ?></td>
                            <td class="col-prose text-gray-500"><?= html_escape($r->tujuan); ?></td>
                            <td>
                                <?php if ($r->file == '1'): ?>
                                    <span class="badge-warning">Belum ada</span>
                                <?php elseif (empty($r->file)): ?>
                                    <span class="badge-muted">&mdash;</span>
                                <?php else: ?>
                                    <a href="<?= base_url($r->file); ?>" target="_blank" rel="noopener" class="badge-success">Lihat</a>
                                <?php endif; ?>
                            </td>
                            <td class="col-aksi">
                                <div class="flex justify-end gap-1.5">
                                    <a href="<?= base_url('surat-keluar/upload/' . $r->id); ?>" class="btn-icon" title="Upload berkas">
                                        <?= svg_icon('upload', 'h-4 w-4'); ?>
                                    </a>
                                    <a href="<?= base_url('surat-keluar/edit/' . $r->id); ?>" class="btn-icon" title="Edit">
                                        <?= svg_icon('edit', 'h-4 w-4'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
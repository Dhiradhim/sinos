<div class="card">
    <div class="flex flex-col gap-4 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold tracking-tight text-foreground">Daftar Nomor Surat</h2>
            <p class="text-xs text-muted-foreground">Nomor surat keluar yang Anda ambil pada periode terpilih.</p>
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

    <?php if (empty($rows)): ?>
        <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <?= svg_icon('file-text', 'h-5 w-5'); ?>
            </span>
            <p class="text-sm font-medium text-foreground">Belum ada data</p>
            <p class="text-sm text-muted-foreground">Tidak ada nomor surat untuk tahun <?= (int) $tahun; ?>.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap p-2">
            <table class="table" id="tblKeluar" data-datatable data-nosort="7">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Surat</th>
                        <th>Nama</th>
                        <th>Tanggal</th>
                        <th>Perihal</th>
                        <th>Tujuan</th>
                        <th>Berkas</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr>
                            <td class="text-muted-foreground"><?= $i++; ?></td>
                            <td class="font-medium"><?= html_escape($r->no); ?></td>
                            <td><?= html_escape($r->nama); ?></td>
                            <td class="whitespace-nowrap text-muted-foreground" data-order="<?= html_escape($r->tanggal); ?>"><?= tanggal_indonesia($r->tanggal); ?></td>
                            <td><?= html_escape($r->hal); ?></td>
                            <td class="text-muted-foreground"><?= html_escape($r->tujuan); ?></td>
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
                                    <a href="<?= base_url('surat-keluar/upload/' . $r->id); ?>" class="btn-icon" title="Upload Berkas">
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
<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <h2 class="mb-4 text-base font-semibold text-slate-700">Detail Disposisi</h2>

        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-slate-400">No. Surat</dt>
                <dd class="font-medium text-slate-700"><?= html_escape($surat->no_surat); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">No. Agenda</dt>
                <dd class="font-medium text-slate-700"><?= html_escape($surat->no_agenda); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">Tanggal Surat</dt>
                <dd class="font-medium text-slate-700"><?= tanggal_indonesia($surat->tgl_surat); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">Tanggal Diterima</dt>
                <dd class="font-medium text-slate-700"><?= tanggal_indonesia($surat->tgl_diterima); ?></dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-slate-400">Perihal</dt>
                <dd class="font-medium text-slate-700"><?= html_escape($surat->perihal); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">Pengirim</dt>
                <dd class="font-medium text-slate-700"><?= html_escape($surat->pengirim); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">Pengolah</dt>
                <dd class="font-medium text-slate-700"><?= html_escape($surat->pengolah); ?></dd>
            </div>
        </dl>

        <?php if (! empty($surat->file)): ?>
            <div class="mt-4">
                <a href="<?= base_url($surat->file); ?>" target="_blank" class="btn-muted">📄 Lihat Berkas Surat</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="mb-4 text-base font-semibold text-slate-700">Informasi Disposisi</h2>
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-slate-400">Diteruskan Kepada</dt>
                <dd class="font-medium text-slate-700"><?= html_escape($row->kepada); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">Kategori</dt>
                <dd class="font-medium text-slate-700"><?= html_escape($row->kategori); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">Instruksi</dt>
                <dd class="font-medium text-slate-700"><?= html_escape(isset($row->instruksi) ? $row->instruksi : '-'); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">Catatan</dt>
                <dd class="font-medium text-slate-700"><?= html_escape(isset($row->catatan) ? $row->catatan : '-'); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">Tanggal Disposisi</dt>
                <dd class="font-medium text-slate-700"><?= date('d-m-Y H:i', strtotime($row->tanggal)); ?></dd>
            </div>
            <div>
                <dt class="text-slate-400">Status</dt>
                <dd>
                    <?php if ((int) $row->dibaca === 1): ?>
                        <span class="badge bg-emerald-100 text-emerald-700">Sudah dibaca</span>
                    <?php else: ?>
                        <span class="badge bg-amber-100 text-amber-700">Belum dibaca</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>

        <div class="mt-5">
            <a href="<?= base_url('disposisi'); ?>" class="btn-muted w-full justify-center">Kembali</a>
        </div>
    </div>
</div>
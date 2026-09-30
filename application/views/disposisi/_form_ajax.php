<form id="formDisposisi" method="post" action="<?= base_url('disposisi/kirim'); ?>" class="space-y-4" onsubmit="return kirimDisposisiAjax(this)">
    <input type="hidden" name="surmas_id" value="<?= (int) $surat->id; ?>">

    <!-- Ringkasan surat -->
    <div class="rounded-xl bg-slate-50 p-3 text-sm ring-1 ring-slate-100">
        <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
            <div><span class="text-slate-400">No. Surat:</span> <span class="font-medium text-slate-700"><?= html_escape($surat->no_surat); ?></span></div>
            <div><span class="text-slate-400">No. Agenda:</span> <span class="font-medium text-slate-700"><?= html_escape($surat->no_agenda); ?></span></div>
            <div><span class="text-slate-400">Tanggal:</span> <span class="font-medium text-slate-700"><?= tanggal_indonesia($surat->tgl_surat); ?></span></div>
            <div><span class="text-slate-400">Pengirim:</span> <span class="font-medium text-slate-700"><?= html_escape($surat->pengirim); ?></span></div>
            <div class="sm:col-span-2"><span class="text-slate-400">Perihal:</span> <span class="font-medium text-slate-700"><?= html_escape($surat->perihal); ?></span></div>
        </div>
    </div>

    <!-- Penerima disposisi (dropdown, pilih satu) -->
    <div>
        <label class="form-label" for="target">Disposisi Kepada <span class="text-rose-500">*</span></label>
        <select class="form-select" id="target" name="target" required>
            <option value="" hidden>- Pilih Penerima -</option>
            <?php foreach ($penerima as $u): ?>
                <option value="<?= (int) $u->id; ?>"><?= html_escape($u->label); ?></option>
            <?php endforeach; ?>
            <option value="Arsip">Arsip</option>
        </select>
        <p class="mt-1 text-xs text-slate-400">Hanya menampilkan user aktif. Pilih satu penerima.</p>
    </div>

    <div>
        <label class="form-label" for="instruksi">Instruksi</label>
        <select class="form-select" id="instruksi" name="instruksi">
            <option value="">- Pilih Instruksi -</option>
            <option value="Untuk Ditindaklanjuti">Untuk Ditindaklanjuti</option>
            <option value="Untuk Diketahui">Untuk Diketahui</option>
            <option value="Untuk Diproses Sesuai Ketentuan">Untuk Diproses Sesuai Ketentuan</option>
            <option value="Untuk Dijawab">Untuk Dijawab</option>
            <option value="Untuk Dikoordinasikan">Untuk Dikoordinasikan</option>
            <option value="Untuk Dipelajari dan Dikaji">Untuk Dipelajari dan Dikaji</option>
            <option value="Untuk Diarsipkan">Untuk Diarsipkan</option>
        </select>
    </div>

    <div>
        <label class="form-label" for="catatan">Catatan</label>
        <textarea class="form-input" id="catatan" name="catatan" rows="4" placeholder="Catatan (opsional)"></textarea>
    </div>

    <?php if (! empty($riwayat)): ?>
        <div>
            <p class="form-label">Riwayat Disposisi</p>
            <ul class="max-h-32 space-y-1 overflow-y-auto text-xs text-slate-600">
                <?php foreach ($riwayat as $rw): ?>
                    <li class="rounded-lg bg-slate-50 px-3 py-1.5">
                        <span class="font-medium"><?= html_escape($rw->kepada); ?></span>
                        <span class="text-slate-400">(<?= html_escape($rw->kategori); ?>)</span>
                        &middot; <?= date('d-m-Y H:i', strtotime($rw->tanggal)); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="flex justify-end gap-2 pt-1">
        <button type="button" onclick="tutupDisposisi()" class="btn-muted">Batal</button>
        <button type="submit" class="btn-primary">Kirim Disposisi</button>
    </div>
</form>
<?php
$attr_target = hs_select_attr('Pilih penerima', 'Cari nama penerima…', TRUE);
?>
<form id="formDisposisi" method="post" action="<?= base_url('disposisi/kirim'); ?>" class="space-y-4"
    onsubmit="return kirimDisposisiAjax(this)">
    <input type="hidden" name="surmas_id" value="<?= (int) $surat->id; ?>">

    <!-- Ringkasan surat -->
    <div class="rounded-[var(--radius-control,0.625rem)] border border-[var(--color-line,#e5e7eb)] bg-[var(--color-surface-muted,#f8fafc)] p-4">
        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">No. Surat</dt>
                <dd class="text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($surat->no_surat); ?></dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">No. Agenda</dt>
                <dd class="text-sm font-medium tabular-nums text-[var(--color-ink,#111827)]"><?= html_escape($surat->no_agenda); ?></dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Tanggal</dt>
                <dd class="text-sm font-medium text-[var(--color-ink,#111827)]"><?= tanggal_indonesia($surat->tgl_surat); ?></dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Pengirim</dt>
                <dd class="text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($surat->pengirim); ?></dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Perihal</dt>
                <dd class="text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($surat->perihal); ?></dd>
            </div>
        </dl>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <!-- Penerima disposisi -->
        <div class="sm:col-span-2">
            <label class="label" for="target">Disposisi Kepada <span class="text-red-600">*</span></label>
            <select class="select" id="target" name="target" required <?= $attr_target; ?>>
                <option value="">Pilih Penerima</option>
                <?php foreach ($penerima as $u): ?>
                    <option value="<?= (int) $u->id; ?>"><?= html_escape($u->label); ?></option>
                <?php endforeach; ?>
                <option value="Arsip">Arsip</option>
            </select>
            <p class="mt-1.5 text-xs text-[var(--color-ink-subtle,#6b7280)]">Hanya menampilkan user aktif. Pilih satu penerima.</p>
        </div>

        <div>
            <label class="label" for="instruksi">Instruksi</label>
            <select class="select" id="instruksi" name="instruksi">
                <option value="">Pilih Instruksi</option>
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
            <label class="label" for="catatan">Catatan</label>
            <textarea class="textarea" id="catatan" name="catatan" rows="4" placeholder="Catatan (opsional)"></textarea>
        </div>
    </div>

    <?php if (! empty($riwayat)): ?>
        <div>
            <p class="fieldset-legend">Riwayat Disposisi</p>
            <ul class="max-h-32 space-y-1 overflow-y-auto text-xs">
                <?php foreach ($riwayat as $rw): ?>
                    <li class="flex items-center justify-between gap-2 rounded-md border border-[var(--color-line,#e5e7eb)] bg-[var(--color-surface-muted,#f8fafc)] px-3 py-1.5">
                        <span class="min-w-0">
                            <span class="font-medium text-[var(--color-ink,#111827)]"><?= html_escape($rw->kepada); ?></span>
                            <span class="text-[var(--color-ink-subtle,#6b7280)]">(<?= html_escape($rw->kategori); ?>)</span>
                        </span>
                        <span class="shrink-0 tabular-nums text-[var(--color-ink-subtle,#6b7280)]"><?= date('d-m-Y H:i', strtotime($rw->tanggal)); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="flex justify-end gap-2 border-t border-[var(--color-line,#e5e7eb)] pt-4">
        <button type="button" onclick="tutupDisposisi()" class="btn-outline">Batal</button>
        <button type="submit" class="btn-primary">
            <?= svg_icon('send'); ?>
            <span>Kirim Disposisi</span>
        </button>
    </div>
</form>
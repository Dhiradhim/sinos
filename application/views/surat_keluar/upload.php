<div class="mx-auto w-full max-w-xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Upload Berkas Surat</h2>
            <p class="card-description">Unggah dokumen PDF untuk nomor surat ini.</p>
        </div>
        <form method="post" action="<?= base_url('surat-keluar/upload-simpan'); ?>" enctype="multipart/form-data">
            <div class="card-content">
                <!-- Ringkasan surat -->
                <div class="mb-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">No. Surat</p>
                            <p class="font-medium tabular-nums text-[var(--color-ink,#111827)]"><?= html_escape($row->no); ?></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Perihal</p>
                            <p class="font-medium text-[var(--color-ink,#111827)]"><?= html_escape($row->hal); ?></p>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="id_surat" value="<?= (int) $row->id; ?>">
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                    <label class="label" for="file">Berkas Surat (PDF)</label>
                    <input class="input" type="file" id="file" name="file" accept="application/pdf" required>
                    <p class="mt-1.5 text-xs text-gray-500">Hanya file PDF, maksimal 10 MB.</p>
                </div>
            </div>

            <div class="card-footer flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 px-5 py-3">
                <a href="<?= base_url('dashboard'); ?>" class="btn-outline">
                    <?= svg_icon('arrow-left'); ?>
                    <span>Kembali</span>
                </a>
                <button type="submit" class="btn-primary">
                    <?= svg_icon('upload'); ?>
                    <span>Upload</span>
                </button>
            </div>
        </form>
    </div>
</div>
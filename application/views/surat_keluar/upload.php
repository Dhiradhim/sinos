<div class="mx-auto w-full max-w-xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Upload Berkas Surat</h2>
            <p class="card-description">Unggah dokumen PDF untuk nomor surat ini.</p>
        </div>
        <div class="card-content">
            <!-- Ringkasan surat -->
            <div class="mb-5 rounded-md border border-border bg-muted/40 p-4">
                <div class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">No. Surat</p>
                        <p class="font-medium text-foreground"><?= html_escape($row->no); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Perihal</p>
                        <p class="font-medium text-foreground"><?= html_escape($row->hal); ?></p>
                    </div>
                </div>
            </div>

            <form method="post" action="<?= base_url('surat-keluar/upload-simpan'); ?>" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id_surat" value="<?= (int) $row->id; ?>">
                <div>
                    <label class="label" for="file">Berkas Surat (PDF)</label>
                    <input class="input file:mr-3 file:rounded file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-secondary-foreground"
                        type="file" id="file" name="file" accept="application/pdf" required>
                    <p class="mt-1.5 text-xs text-muted-foreground">Hanya file PDF, maksimal 10 MB.</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-border pt-5">
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
</div>
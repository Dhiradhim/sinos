<div class="card max-w-xl">
    <div class="mb-4">
        <p class="text-sm text-slate-500">No. Surat</p>
        <p class="text-base font-semibold text-slate-700"><?= html_escape($row->no); ?></p>
        <p class="mt-1 text-sm text-slate-500">Perihal: <?= html_escape($row->hal); ?></p>
    </div>

    <form method="post" action="<?= base_url('surat-keluar/upload-simpan'); ?>" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id_surat" value="<?= (int) $row->id; ?>">
        <div>
            <label class="form-label" for="file">Berkas Surat (PDF)</label>
            <input class="form-input" type="file" id="file" name="file" accept="application/pdf" required>
            <p class="mt-1 text-xs text-slate-400">Hanya file PDF, maksimal 10 MB.</p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="<?= base_url('dashboard'); ?>" class="btn-muted">Kembali</a>
            <button type="submit" class="btn-success">Upload</button>
        </div>
    </form>
</div>
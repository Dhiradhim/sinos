<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Edit Nomor Surat</h2>
            <p class="card-description">Perbarui data nomor surat keluar.</p>
        </div>
        <form method="post" action="<?= base_url('surat-keluar/update/' . $row->id); ?>">
            <div class="card-content">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Nomor surat</p>
                        <p class="truncate font-medium tabular-nums text-gray-900"><?= html_escape($row->no); ?></p>
                    </div>
                    <span class="badge-outline">
                        <?= html_escape($row->no_urut . ($row->huruf ? $row->huruf : '')); ?>
                    </span>
                </div>

                <?php $this->load->view('surat_keluar/_form_nomor_fields'); ?>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="tanggal">Tanggal Surat</label>
                        <input class="input" type="date" id="tanggal" name="tanggal"
                            value="<?= html_escape($row->tanggal); ?>" required>
                    </div>
                </div>
            </div>

            <div class="card-footer flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 px-5 py-3">
                <a href="<?= base_url('surat-keluar/daftar?tahun=' . date('Y', strtotime($row->tanggal))); ?>" class="btn-outline">
                    <?= svg_icon('arrow-left'); ?>
                    <span>Kembali</span>
                </a>
                <button type="submit" class="btn-primary">
                    <?= svg_icon('check'); ?>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
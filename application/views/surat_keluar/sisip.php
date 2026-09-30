<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Sisip Nomor Surat</h2>
            <p class="card-description">Sisipkan nomor surat pada dokumen yang sudah memiliki tanggal surat.</p>
        </div>
        <div class="card-content">
            <form method="post" action="<?= base_url('surat-keluar/sisip-simpan'); ?>" class="space-y-5">
                <?php $this->load->view('surat_keluar/_pengambil_field'); ?>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="kj">Derajat Surat (KJ)</label>
                        <input class="input" type="text" id="kj" name="kj" placeholder="mis. A1" required>
                    </div>
                    <div>
                        <label class="label" for="tanggal">Tanggal Surat</label>
                        <input class="input" type="date" id="tanggal" name="tanggal" required>
                    </div>
                </div>

                <?php $this->load->view('surat_keluar/_klasifikasi_fields'); ?>

                <div>
                    <label class="label" for="hal">Perihal</label>
                    <input class="input" type="text" id="hal" name="hal" required>
                </div>

                <div>
                    <label class="label" for="tujuan">Tujuan</label>
                    <input class="input" type="text" id="tujuan" name="tujuan" required>
                </div>

                <div class="flex justify-end gap-2 border-t border-border pt-5">
                    <button type="reset" class="btn-outline">Ulang</button>
                    <button type="submit" class="btn-primary">
                        <?= svg_icon('paperclip'); ?>
                        <span>Sisip Nomor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
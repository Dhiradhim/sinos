<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Ambil Nomor Surat</h2>
            <p class="card-description">Daftarkan nomor surat keluar baru beserta klasifikasinya.</p>
        </div>
        <div class="card-content">
            <form method="post" action="<?= base_url('surat-keluar/ambil-simpan'); ?>" class="space-y-5">
                <?php $this->load->view('surat_keluar/_pengambil_field'); ?>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="kj">Derajat Surat (KJ)</label>
                        <input class="input" type="text" id="kj" name="kj" placeholder="mis. A1" required>
                    </div>
                </div>

                <?php $this->load->view('surat_keluar/_klasifikasi_fields'); ?>

                <div>
                    <label class="label" for="hal">Perihal</label>
                    <input class="input" type="text" id="hal" name="hal" placeholder="Perihal surat" required>
                </div>

                <div>
                    <label class="label" for="tujuan">Tujuan</label>
                    <input class="input" type="text" id="tujuan" name="tujuan" placeholder="Tujuan surat" required>
                </div>

                <div class="flex justify-end gap-2 border-t border-border pt-5">
                    <button type="reset" class="btn-outline">Ulang</button>
                    <button type="submit" class="btn-primary">
                        <?= svg_icon('hash'); ?>
                        <span>Ambil Nomor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Ambil Nomor Surat</h2>
            <p class="card-description">Daftarkan nomor surat keluar baru beserta klasifikasinya.</p>
        </div>
        <form method="post" action="<?= base_url('surat-keluar/ambil-simpan'); ?>">
            <div class="card-content">
                <?php $this->load->view('surat_keluar/_form_nomor_fields'); ?>
            </div>

            <div class="card-footer flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 px-5 py-3">
                <p class="text-xs text-gray-500">
                    Nomor melanjutkan nomor terakhir pada tanggal hari ini.
                </p>
                <div class="flex gap-2">
                    <button type="reset" class="btn-outline">Ulang</button>
                    <button type="submit" class="btn-primary">
                        <?= svg_icon('hash'); ?>
                        <span>Ambil Nomor</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
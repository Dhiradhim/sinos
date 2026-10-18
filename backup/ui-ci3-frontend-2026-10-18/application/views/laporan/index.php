<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Cetak Laporan</h2>
            <p class="card-description">Pilih jenis laporan dan periode, lalu cetak sebagai PDF.</p>
        </div>
        <form method="post" action="<?= base_url('laporan/cetak'); ?>" target="_blank">
            <div class="card-content">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2">
                        <label class="label" for="tipelaporan">Jenis Laporan</label>
                        <select class="select" id="tipelaporan" name="tipelaporan" required>
                            <option value="surkel">Agenda Surat Keluar</option>
                            <option value="surmas">Agenda Surat Masuk</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="tgl_cetak">Tanggal Cetak</label>
                        <input class="input" type="text" id="tgl_cetak" name="tgl_cetak" value="<?= date('d-m-Y'); ?>">
                    </div>
                    <div>
                        <label class="label" for="bulan">Bulan</label>
                        <select class="select" id="bulan" name="bulan" required>
                            <?php foreach ($bulan as $num => $nama): ?>
                                <option value="<?= $num; ?>" <?= ($num === '00') ? 'selected' : ''; ?>><?= $nama; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="tahun">Tahun</label>
                        <select class="select" id="tahun" name="tahun" required>
                            <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                                <option value="<?= $y; ?>"><?= $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="kode">Kode Klasifikasi</label>
                        <select class="select" id="kode" name="kode">
                            <option value="all">Semua Kode</option>
                            <?php foreach ($klasifikasi as $k): ?>
                                <option value="<?= html_escape($k->kode); ?>"><?= html_escape($k->kode . ' - ' . (isset($k->nama) ? $k->nama : '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-footer flex-col gap-2 sm:flex-row sm:justify-end">
                <button type="reset" class="btn-outline w-full sm:w-auto">Atur Ulang</button>
                <button type="submit" class="btn-primary w-full sm:w-auto">
                    <?= svg_icon('printer'); ?>
                    <span>Cetak PDF</span>
                </button>
            </div>
        </form>
    </div>
</div>
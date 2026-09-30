<div class="mx-auto w-full max-w-3xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Cetak Laporan</h2>
            <p class="card-description">Pilih jenis laporan dan periode, lalu cetak sebagai PDF.</p>
        </div>
        <div class="card-content">
            <form method="post" action="<?= base_url('laporan/cetak'); ?>" target="_blank" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="label" for="tipelaporan">Jenis Laporan</label>
                        <select class="select" id="tipelaporan" name="tipelaporan" required>
                            <option value="surkel">Agenda Surat Keluar</option>
                            <option value="surmas">Agenda Surat Masuk</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="tgl_cetak">Tanggal Cetak</label>
                        <input class="input" type="text" id="tgl_cetak" name="tgl_cetak" value="<?= date('d-m-Y'); ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                    <div>
                        <label class="label" for="kode">Kode Klasifikasi</label>
                        <select class="select" id="kode" name="kode">
                            <option value="all">Semua Kode</option>
                            <?php foreach ($klasifikasi as $k): ?>
                                <option value="<?= html_escape($k->kode); ?>"><?= html_escape($k->kode . ' - ' . (isset($k->nama) ? $k->nama : '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end border-t border-border pt-5">
                    <button type="submit" class="btn-primary">
                        <?= svg_icon('printer'); ?>
                        <span>Cetak PDF</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
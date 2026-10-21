<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: serif;
            font-size: 11px;
            color: #111827;
        }

        /*
         * JUDUL & SUB-JUDUL
         *
         * Judul mengikuti jenis laporan yang dipilih
         * ("Laporan Rekapitulasi Surat Keluar/Masuk"), sub-judul berisi
         * periode ("Periode: Oktober 2026" / "Periode: Tahun 2026").
         * Keduanya berada di luar tabel data, rata tengah.
         *
         * Ukuran = setengah dari versi sebelumnya (39px → 19.5px untuk judul,
         * 33px → 16.5px untuk sub-judul). `line-height` dipersempit agar jarak
         * antar baris tetap rapat.
         */
        .judul {
            text-align: center;
            font-weight: bold;
            font-size: 19.5px;
            line-height: 1.1;
            margin: 0 0 2px 0;
        }

        .periode {
            text-align: center;
            font-size: 16.5px;
            line-height: 1.1;
            margin: 0 0 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000000;
            padding: 4px 3px;
            vertical-align: top;
        }

        /*
         * Satu baris surat tidak boleh terpotong antar halaman. Kepala tabel
         * (`thead`) otomatis diulang mPDF pada setiap halaman baru.
         */
        tr {
            page-break-inside: avoid;
        }

        /* Baris kepala tabel: latar abu-abu, teks tebal rata tengah. */
        th {
            background-color: #bfbfbf;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 5px 3px;
        }

        td.center {
            text-align: center;
        }

        /*
         * TABEL REKAP KODE KLASIFIKASI
         *
         * Dicetak hanya bila kode = 'all'. Kepalanya tanpa latar abu-abu
         * (putih) agar berbeda dari tabel daftar surat di atasnya, dan
         * kolomnya dibuat rapat karena satu baris memuat 13 kode x 2 angka.
         */
        table.rekap {
            margin-top: 18px;
        }

        table.rekap th,
        table.rekap td {
            background-color: #ffffff;
            padding: 3px 2px;
            font-size: 9px;
            text-align: center;
        }

        table.rekap th.judul-rekap {
            font-size: 10px;
            font-weight: bold;
            padding: 4px 2px;
        }

        table.rekap th.kode {
            font-weight: bold;
        }

        table.rekap th.sub {
            font-weight: normal;
            font-size: 8px;
        }

        /*
         * Nomor agenda pada kolom "No Surat" (khusus surat masuk): ukuran
         * lebih kecil sedikit dan abu-abu gelap agar tidak mengalahkan nomor
         * surat yang menjadi informasi utama.
         */
        .no-agenda {
            display: block;
            font-size: 9px;
            color: #4b5563;
            margin-top: 1px;
        }

        /*
         * BLOK REKAP + TANDA TANGAN
         *
         * Keduanya dibungkus satu `div` dengan `page-break-inside: avoid`.
         * Bila keduanya tidak muat pada halaman berjalan (tanda tangan akan
         * terpotong atau terpisah), mPDF memindahkan SELURUH blok — tabel
         * rekap sekaligus kolom tanda tangan — ke halaman baru.
         */
        .blok-penutup {
            page-break-inside: avoid;
        }

        /* Tabel tanda tangan: tanpa garis sama sekali. */
        table.no-border,
        table.no-border td {
            border: none;
        }

        table.no-border td {
            padding: 0;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.45;
        }
    </style>
</head>

<body>

    <p class="judul"><?= html_escape($judul); ?></p>
    <p class="periode"><?= html_escape($periode); ?></p>

    <?php
    /*
     * Tabel daftar surat. Kolom "Tanggal" berada TEPAT setelah "No Surat",
     * lalu "Pengirim" (khusus surat masuk), lalu "Perihal":
     *
     *   surat keluar : No. | No Surat | Tanggal | Perihal
     *   surat masuk  : No. | No Surat | Tanggal | Pengirim | Perihal
     */
    $is_surmas = (isset($jenis) && $jenis === 'surmas');
    $kolom     = $is_surmas ? 5 : 4;
    ?>
    <table>
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th width="<?= $is_surmas ? '28%' : '32%' ?>">No Surat</th>
                <th width="13%">Tanggal</th>
                <?php if ($is_surmas): ?>
                    <th width="20%">Pengirim</th>
                <?php endif; ?>
                <th>Perihal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= (int) $kolom ?>" class="center">Tidak ada data.</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="center"><?= $i++; ?></td>
                        <td>
                            <?= html_escape($r['no_surat']); ?>
                            <?php
                            // Nomor agenda hanya ada pada surat masuk.
                            $no_agenda = isset($r['no_agenda']) ? trim((string) $r['no_agenda']) : '';
                            if ($is_surmas && $no_agenda !== '' && $no_agenda !== '-'):
                            ?>
                                <span class="no-agenda">Agenda: <?= html_escape($no_agenda); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="center"><?= html_escape($r['tanggal']); ?></td>
                        <?php if ($is_surmas): ?>
                            <td><?= html_escape($r['pengirim']); ?></td>
                        <?php endif; ?>
                        <td><?= html_escape($r['perihal']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    /*
     * REKAPITULASI KODE KLASIFIKASI (hanya bila kode = 'all').
     *
     * Sumber angka: api/Laporan::hitung_kode() lewat `$rekap`, satu entri per
     * kode dasar pada config `laporan_kode_dasar` (13 kode).
     *
     * DUA BENTUK TABEL
     *   - TAHUNAN (`$is_tahunan`): satu kolom per kode, langsung berisi jumlah
     *     setahun. Tidak ada baris sub-kolom "Tahun / s.d. Tahun" karena
     *     keduanya akan selalu bernilai sama.
     *   - BULANAN: dua sub-kolom per kode —
     *       <singkatan bulan>  = jumlah pada bulan terpilih
     *       s.d. <singkatan>   = jumlah kumulatif s.d. bulan terpilih
     */
    $kode_dasar  = isset($kode_dasar) ? $kode_dasar : array();
    $rekap       = isset($rekap) ? $rekap : array();
    $label_bulan = isset($label_bulan) ? $label_bulan : 'Tahun';
    $is_all      = (isset($kode) && strcasecmp((string) $kode, 'all') === 0);

    // Tahunan = satu angka per kode; bulanan = dua sub-kolom per kode.
    $is_tahunan = isset($is_tahunan)
        ? (bool) $is_tahunan
        : ($label_bulan === 'Tahun');
    $span = $is_tahunan ? 1 : 2;

    // "Bulan Oktober 2026" untuk periode bulanan, "Tahun 2026" untuk Tahunan.
    $label_rekap = $is_tahunan
        ? 'Tahun ' . $tahun
        : 'Bulan ' . $namabulan . ' ' . $tahun;
    ?>
    <?php
    /*
     * Rekap + tanda tangan dibungkus satu blok `page-break-inside: avoid`.
     * Bila keduanya tidak muat di sisa halaman berjalan (tanda tangan akan
     * terpotong / nama-NIP terpisah halaman), mPDF memindahkan seluruh blok
     * ke halaman baru — tabel rekap ikut pindah, bukan tertinggal sendirian.
     */
    ?>
    <div class="blok-penutup">
        <?php if ($is_all && ! empty($kode_dasar)): ?>
            <table class="rekap">
                <thead>
                    <tr>
                        <th colspan="<?= count($kode_dasar) * $span; ?>" class="judul-rekap">
                            Rekapitulasi Surat Berdasarkan Kode Klasifikasi <?= html_escape($label_rekap); ?>
                        </th>
                    </tr>
                    <tr>
                        <?php foreach ($kode_dasar as $kd => $nama_kode): ?>
                            <th colspan="<?= (int) $span; ?>" class="kode"><?= html_escape($kd); ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php if (! $is_tahunan): ?>
                        <tr>
                            <?php foreach ($kode_dasar as $kd => $nama_kode): ?>
                                <th class="sub"><?= html_escape($label_bulan); ?></th>
                                <th class="sub">s.d. <?= html_escape($label_bulan); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($kode_dasar as $kd => $nama_kode): ?>
                            <?php
                            $angka = isset($rekap[$kd])
                                ? $rekap[$kd]
                                : array('tah' => 0, 'sd' => 0);
                            ?>
                            <?php if ($is_tahunan): ?>
                                <td><?= (int) $angka['tah']; ?></td>
                            <?php else: ?>
                                <td><?= (int) $angka['tah']; ?></td>
                                <td><?= (int) $angka['sd']; ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <?php
        /*
         * Blok tanda tangan.
         *
         * `$ttd` disusun api/Laporan::susun_ttd():
         *   mengetahui, jabatan_kiri, kota_tanggal, jabatan_kanan,
         *   nama_kiri, nama_kanan, nip_kiri, nip_kanan
         * Nama dan NIP diambil dari `pegawai_db.pegawai` lewat config
         * `laporan_ttd`, sehingga selalu mengikuti data pegawai terpusat.
         */
        ?>
        <table class="no-border" style="margin-top: 26px;">
            <tr class="no-border">
                <td width="35%" class="no-border"><?= html_escape($ttd['mengetahui']); ?></td>
                <td width="30%" class="no-border"></td>
                <td width="35%" class="no-border"><?= html_escape($ttd['kota_tanggal']); ?></td>
            </tr>
            <tr class="no-border">
                <td class="no-border"><?= html_escape($ttd['jabatan_kiri']); ?></td>
                <td class="no-border"></td>
                <td class="no-border"><?= html_escape($ttd['jabatan_kanan']); ?></td>
            </tr>
            <tr class="no-border">
                <td colspan="3" class="no-border" style="height: 60px;"></td>
            </tr>
            <tr class="no-border">
                <td class="no-border"><b><?= html_escape($ttd['nama_kiri']); ?></b></td>
                <td class="no-border"></td>
                <td class="no-border"><b><?= html_escape($ttd['nama_kanan']); ?></b></td>
            </tr>
            <tr class="no-border">
                <td class="no-border"><?= html_escape($ttd['nip_kiri']); ?></td>
                <td class="no-border"></td>
                <td class="no-border"><?= html_escape($ttd['nip_kanan']); ?></td>
            </tr>
        </table>
    </div><!-- /.blok-penutup -->

</body>

</html>
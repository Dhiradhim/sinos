<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #1e293b;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .header h2 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }

        .header p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #94a3b8;
            padding: 5px 6px;
        }

        th {
            background-color: #e2e8f0;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
        }

        td.center {
            text-align: center;
        }

        .footer {
            margin-top: 24px;
            width: 100%;
        }

        .ttd {
            float: right;
            width: 220px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2><?= html_escape($judul); ?></h2>
        <p>Periode: <?= html_escape($namabulan); ?> <?= html_escape($ctahun); ?></p>
        <p>Pengadilan Agama Kupang</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No.</th>
                <th width="25%">Nomor Surat</th>
                <th width="12%">Tanggal</th>
                <th width="38%">Perihal</th>
                <th width="20%">Tujuan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" class="center">Tidak ada data.</td>
                </tr>
            <?php else: ?>
                <?php $i = 1;
                foreach ($rows as $r): ?>
                    <tr>
                        <td class="center"><?= $i++; ?></td>
                        <td><?= html_escape($r->{$col_no}); ?></td>
                        <td class="center"><?= tanggal_indonesia($r->{$col_tgl}); ?></td>
                        <td><?= html_escape($r->{$col_hal}); ?></td>
                        <td><?= html_escape(isset($r->tujuan) ? $r->tujuan : ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <div class="ttd">
            <p>Kupang, <?= html_escape($tgl_cetak); ?></p>
            <p style="margin-top:60px;">(...................................)</p>
        </div>
    </div>

</body>

</html>
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= html_escape($title); ?> - SINOS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">

    <?php if (! empty($use_datatables)): ?>
        <!-- DataTables (Tailwind-friendly) -->
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
        <style>
            /* Penyesuaian tampilan DataTables agar selaras dengan Tailwind */
            .dt-container {
                font-size: 0.875rem;
                color: #334155;
            }

            .dt-container .dt-length select,
            .dt-container .dt-search input {
                border: 1px solid #cbd5e1 !important;
                border-radius: 0.5rem !important;
                padding: 0.35rem 0.6rem !important;
                font-size: 0.875rem !important;
                background-color: #fff !important;
            }

            .dt-container .dt-search input:focus {
                outline: none;
                box-shadow: 0 0 0 2px #bcd3ff;
            }

            table.dataTable thead th {
                border-bottom: 1px solid #e2e8f0 !important;
                background-color: #f8fafc;
            }

            table.dataTable tbody tr {
                cursor: default;
            }

            .dt-container .dt-paging .dt-paging-button.current {
                background: #1f47f5 !important;
                border-color: #1f47f5 !important;
                color: #fff !important;
                border-radius: 0.5rem;
            }

            .dt-container .dt-paging .dt-paging-button {
                border-radius: 0.5rem;
                border: 1px solid #e2e8f0;
            }

            .dt-container .dt-paging .dt-paging-button:hover {
                background: #eef4ff !important;
                color: #1836e1 !important;
            }
        </style>
    <?php endif; ?>
</head>

<body class="h-full font-sans">

    <div class="min-h-full">
        <?php $this->load->view('layouts/sidebar'); ?>

        <!-- Overlay mobile -->
        <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-slate-900/60 lg:hidden"></div>

        <!-- Content wrapper -->
        <div class="lg:pl-64">
            <?php $this->load->view('layouts/topbar'); ?>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <?php $this->load->view('layouts/flash'); ?>

                <?php if (! empty($subtitle)): ?>
                    <p class="mb-4 text-sm text-slate-500"><?= html_escape($subtitle); ?></p>
                <?php endif; ?>

                <?= $content; ?>
            </main>

            <?php $this->load->view('layouts/footer'); ?>
        </div>
    </div>

    <?php $this->load->view('layouts/logout_modal'); ?>

    <?php if (! empty($use_datatables)): ?>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
        <script>
            // Inisialisasi otomatis semua tabel ber-atribut data-datatable
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('table[data-datatable]').forEach(function(table) {
                    var opts = {
                        pageLength: 25,
                        lengthMenu: [10, 25, 50, 100],
                        order: [],
                        language: {
                            search: 'Cari:',
                            lengthMenu: 'Tampilkan _MENU_ data',
                            info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                            infoEmpty: 'Tidak ada data',
                            infoFiltered: '(disaring dari _MAX_ total data)',
                            zeroRecords: 'Data tidak ditemukan',
                            emptyTable: 'Belum ada data',
                            paginate: {
                                first: 'Awal',
                                last: 'Akhir',
                                next: 'Berikutnya',
                                previous: 'Sebelumnya'
                            }
                        }
                    };
                    // Kolom yang tidak boleh diurutkan/di-cari (mis. kolom Aksi)
                    var noSort = table.getAttribute('data-nosort');
                    if (noSort) {
                        opts.columnDefs = noSort.split(',').map(function(i) {
                            return {
                                targets: parseInt(i, 10),
                                orderable: false,
                                searchable: false
                            };
                        });
                    }
                    new DataTable(table, opts);
                });
            });
        </script>
    <?php endif; ?>

    <script src="<?= base_url('assets/js/app.js'); ?>"></script>
</body>

</html>
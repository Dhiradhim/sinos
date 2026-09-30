<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#ffffff">
    <title><?= html_escape($title); ?> - SINOS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php $css_ver = @filemtime(FCPATH . 'assets/css/app.css'); ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?><?= $css_ver ? '?v=' . $css_ver : ''; ?>">

    <?php if (! empty($use_datatables)): ?>
        <!-- DataTables -->
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
        <style>
            /* DataTables selaras dengan design system shadcn */
            .dt-container {
                font-size: 0.875rem;
                color: hsl(var(--foreground));
            }

            .dt-container .dt-layout-row {
                align-items: center;
                gap: 1rem;
                margin: 0 0 1rem;
            }

            .dt-container .dt-layout-row:last-child {
                margin: 1rem 0 0;
            }

            .dt-container .dt-length,
            .dt-container .dt-search {
                color: hsl(var(--muted-foreground));
                font-size: 0.8125rem;
                gap: 0.5rem;
            }

            .dt-container .dt-length select,
            .dt-container .dt-search input {
                border: 1px solid hsl(var(--input)) !important;
                border-radius: var(--radius) !important;
                padding: 0.375rem 0.625rem !important;
                font-size: 0.875rem !important;
                height: 2.25rem;
                background-color: hsl(var(--background)) !important;
                color: hsl(var(--foreground)) !important;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
                transition: box-shadow 150ms, border-color 150ms;
            }

            .dt-container .dt-search input {
                min-width: 14rem;
            }

            .dt-container .dt-length select:focus,
            .dt-container .dt-search input:focus {
                outline: none !important;
                border-color: hsl(var(--ring)) !important;
                box-shadow: 0 0 0 2px hsl(var(--ring) / 0.35) !important;
            }

            table.dataTable {
                border-collapse: separate;
                border-spacing: 0;
                width: 100% !important;
                margin: 0 !important;
            }

            table.dataTable thead th {
                border-bottom: 1px solid hsl(var(--border)) !important;
                border-top: 0 !important;
                background-color: transparent !important;
                color: hsl(var(--muted-foreground));
                font-size: 0.6875rem;
                font-weight: 600;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                padding: 0.75rem !important;
            }

            table.dataTable tbody td {
                border-bottom: 1px solid hsl(var(--border)) !important;
                padding: 0.625rem 0.75rem !important;
                color: hsl(var(--foreground));
            }

            table.dataTable tbody tr:hover>* {
                background-color: hsl(var(--muted) / 0.4) !important;
            }

            table.dataTable tbody tr:last-child td {
                border-bottom: 0 !important;
            }

            .dt-container .dt-info {
                color: hsl(var(--muted-foreground));
                font-size: 0.8125rem;
            }

            .dt-container .dt-paging .dt-paging-button {
                border: 1px solid hsl(var(--border)) !important;
                border-radius: calc(var(--radius) - 2px) !important;
                background: hsl(var(--background)) !important;
                color: hsl(var(--foreground)) !important;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
                padding: 0.25rem 0.625rem !important;
                margin: 0 0.125rem;
            }

            .dt-container .dt-paging .dt-paging-button:hover:not(.disabled) {
                background: hsl(var(--accent)) !important;
                color: hsl(var(--accent-foreground)) !important;
            }

            .dt-container .dt-paging .dt-paging-button.current,
            .dt-container .dt-paging .dt-paging-button.current:hover {
                background: hsl(var(--primary)) !important;
                border-color: hsl(var(--primary)) !important;
                color: hsl(var(--primary-foreground)) !important;
            }

            .dt-container .dt-paging .dt-paging-button.disabled,
            .dt-container .dt-paging .dt-paging-button.disabled:hover {
                opacity: 0.4;
                background: hsl(var(--background)) !important;
                color: hsl(var(--muted-foreground)) !important;
            }

            table.dataTable thead .dt-orderable-asc,
            table.dataTable thead .dt-orderable-desc {
                cursor: pointer;
            }

            /* Kolom aksi tidak pernah terpotong */
            table.dataTable th.col-aksi,
            table.dataTable td.col-aksi {
                width: 1% !important;
                white-space: nowrap !important;
                text-align: right;
            }

            table.dataTable th.col-aksi::before,
            table.dataTable th.col-aksi::after {
                display: none !important;
            }

            /* Wrapper DataTables mengisi lebar penuh & bisa scroll horizontal */
            .table-wrap .dt-container {
                width: 100%;
            }

            .table-wrap .dt-container .dt-layout-row {
                width: 100% !important;
            }
        </style>
    <?php endif; ?>
</head>

<body class="h-full font-sans">
    <div class="min-h-full">
        <?php $this->load->view('layouts/sidebar'); ?>

        <!-- Overlay mobile -->
        <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-foreground/50 backdrop-blur-[1px] lg:hidden"></div>

        <!-- Content wrapper -->
        <div class="lg:pl-64">
            <?php $this->load->view('layouts/topbar'); ?>

            <main class="w-full px-4 py-6 sm:px-6 lg:px-8">
                <?php $this->load->view('layouts/flash'); ?>

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
                            search: '',
                            searchPlaceholder: 'Cari…',
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

    <?php $js_ver = @filemtime(FCPATH . 'assets/js/app.js'); ?>
    <script src="<?= base_url('assets/js/app.js'); ?><?= $js_ver ? '?v=' . $js_ver : ''; ?>"></script>
</body>

</html>
<?php
// Helper rollout flag (v3/v4). Dimuat eksplisit di shell — bukan lewat autoload.
$this->load->helper('ui');
?>
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#0a2463">
    <meta name="color-scheme" content="light">
    <title><?= html_escape($title); ?> - SINOS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?= ui_css_tag(); ?>

    <?php if (! empty($use_datatables)): ?>
        <!-- DataTables: CSS bawaan dimuat apa adanya, lalu ditimpa skin SINOS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
        <?php
        /*
         * Skin DataTables + fallback token v3.
         * Dimuat SETELAH app.css/app.v4.css dan CSS DataTables CDN supaya
         * menang tanpa banyak `!important`. Berkas ini juga mendefinisikan
         * token semantik (--color-line, --color-surface, ...) untuk v3.
         */
        $dt_skin_ver = @filemtime(FCPATH . 'assets/css/datatables-skin.css');
        ?>
        <link rel="stylesheet" href="<?= base_url('assets/css/datatables-skin.css'); ?><?= $dt_skin_ver ? '?v=' . $dt_skin_ver : ''; ?>">
    <?php endif; ?>
    <!-- UI variant: <?= ui_variant(); ?> (switch with ?ui=v4) -->
</head>

<?php
// Latar shell mengikuti token pada v4, tetap gray-50 pada v3.
$body_bg = ui_variant() === 'v4' ? 'bg-[var(--color-surface-sunken)]' : 'bg-gray-50';
?>

<body class="h-full <?= $body_bg; ?> font-sans <?= ui_body_class(); ?>">
    <div class="flex min-h-full flex-col">
        <?php $this->load->view('layouts/sidebar'); ?>

        <!-- Backdrop sidebar (mobile) -->
        <div id="sidebarOverlay"
            class="fixed inset-0 z-30 hidden bg-gray-900/50 backdrop-blur-[1px] transition-opacity lg:hidden"></div>

        <div class="flex min-h-screen flex-col lg:pl-64">
            <?php $this->load->view('layouts/topbar'); ?>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto w-full max-w-[110rem]">
                    <?php $this->load->view('layouts/flash'); ?>

                    <?= $content; ?>
                </div>
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
                    // Guard double-init: Preline 5 membawa datatables.net sendiri,
                    // jadi tabel bisa sudah diinisialisasi oleh runtime Preline.
                    if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable(table)) {
                        return;
                    }

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

    <?= ui_js_tag(); ?>
    <?php $js_ver = @filemtime(FCPATH . 'assets/js/app.js'); ?>
    <script src="<?= base_url('assets/js/app.js'); ?><?= $js_ver ? '?v=' . $js_ver : ''; ?>"></script>
</body>

</html>
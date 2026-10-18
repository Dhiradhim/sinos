<?php $this->load->helper('api_berkas'); // tautan berkas bertanda tangan (Fase 2) 
?>
<div class="card">
    <div class="card-header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="card-title">Daftar Surat Masuk</h2>
                <p class="card-description">Agenda surat masuk pada periode terpilih.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <?php $tahun_action = base_url('surat-masuk/daftar');
                $this->load->view('layouts/_tahun_selector'); ?>
                <?php if (! empty($user['is_operator'])): ?>
                    <a href="<?= base_url('surat-masuk'); ?>" class="btn-primary btn-sm">
                        <?= svg_icon('plus'); ?>
                        <span>Input Surat</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="empty-state">
            <span class="empty-state-icon"><?= svg_icon('inbox', 'h-5 w-5'); ?></span>
            <p class="text-sm font-medium text-gray-900">Belum ada data</p>
            <p class="text-sm text-gray-500">Tidak ada surat masuk untuk tahun <?= (int) $tahun; ?>.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table" id="tblMasuk" data-datatable data-nosort="6">
                <thead>
                    <tr>
                        <th class="w-10">No.</th>
                        <th>Agenda / No. Surat</th>
                        <th class="col-nowrap">Tanggal</th>
                        <th class="col-prose">Pengirim</th>
                        <th class="col-prose">Perihal</th>
                        <th class="col-nowrap">Status & Berkas</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr class="<?= ($r->status === 'diarsipkan') ? 'bg-gray-50/60' : ''; ?>">
                            <td class="tabular-nums text-xs text-gray-400"><?= $i++; ?></td>
                            <td class="col-nowrap">
                                <span class="block font-medium leading-snug text-gray-900"><?= html_escape($r->no_surat); ?></span>
                                <span class="mt-0.5 block text-xs tabular-nums text-gray-500">
                                    <?= html_escape($r->no_agenda); ?>
                                </span>
                            </td>
                            <td class="col-nowrap text-gray-500" data-order="<?= html_escape($r->tgl_surat); ?>"><?= tanggal_indonesia($r->tgl_surat); ?></td>
                            <td class="col-prose"><?= html_escape($r->pengirim); ?></td>
                            <td class="col-prose"><?= html_escape($r->perihal); ?></td>
                            <td class="col-nowrap">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <?php
                                    $st = isset($status_disposisi[(int) $r->id]) ? $status_disposisi[(int) $r->id] : NULL;
                                    if ($st):
                                    ?>
                                        <span class="<?= $st->tone; ?>"><?= html_escape($st->status); ?></span>
                                    <?php else: ?>
                                        <span class="badge-outline">Belum didisposisi</span>
                                    <?php endif; ?>

                                    <?php if ($r->status === 'diarsipkan'): ?>
                                        <span class="badge-muted">Diarsipkan</span>
                                    <?php endif; ?>

                                    <?php if (! empty($r->file)): ?>
                                        <a href="<?= berkas_url($r->file); ?>" target="_blank" rel="noopener" class="badge-success">
                                            <?= svg_icon('eye', 'h-3 w-3'); ?>Berkas
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php if ($st && ! empty($st->lokasi)): ?>
                                    <span class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                        <?= svg_icon('user', 'h-3 w-3 shrink-0'); ?>
                                        <span class="truncate"><?= html_escape($st->lokasi); ?></span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="col-aksi">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="<?= base_url('surat-masuk/detail/' . $r->id); ?>" class="btn-icon" title="Lihat detail">
                                        <?= svg_icon('eye', 'h-4 w-4'); ?>
                                    </a>
                                    <?php if (! empty($can_send_map[(int) $r->id])): ?>
                                        <button type="button" onclick="bukaDisposisi(<?= (int) $r->id; ?>)" class="btn-icon" title="Kirim / teruskan disposisi">
                                            <?= svg_icon('send', 'h-4 w-4'); ?>
                                        </button>
                                    <?php endif; ?>

                                    <?php
                                    // Aksi sekunder dirapatkan ke satu menu agar kolom aksi
                                    // tidak melebar dan terpotong di layar 1440px.
                                    $aksi_lain = array();
                                    if (! empty($user['is_admin']) || ! empty($user['is_operator'])) {
                                        $aksi_lain[] = array(
                                            'href'  => base_url('surat-masuk/edit/' . $r->id),
                                            'icon'  => 'edit',
                                            'label' => 'Edit surat',
                                        );
                                        $aksi_lain[] = array(
                                            'href'  => base_url('surat-masuk/disposisi/' . $r->id),
                                            'icon'  => 'printer',
                                            'label' => 'Cetak lembar disposisi',
                                            'blank' => TRUE,
                                        );
                                    }
                                    if (! empty($user['is_operator']) && $r->status === 'diarsipkan') {
                                        $aksi_lain[] = array(
                                            'href'    => base_url('surat-masuk/batal-arsip/' . $r->id),
                                            'icon'    => 'archive-restore',
                                            'label'   => 'Batalkan arsip',
                                            'confirm' => 'Batalkan status arsip surat ini?',
                                        );
                                    }
                                    if (! empty($user['is_admin'])) {
                                        $aksi_lain[] = array(
                                            'href'    => base_url('surat-masuk/hapus/' . $r->id),
                                            'icon'    => 'trash',
                                            'label'   => 'Hapus surat',
                                            'confirm' => 'Hapus surat ini?',
                                            'danger'  => TRUE,
                                        );
                                    }
                                    ?>

                                    <?php if (! empty($aksi_lain)): ?>
                                        <?php /* `strategy: fixed` membuat panel dirender relatif viewport,
                                                 sehingga tidak terpotong `overflow-x: auto` pada .table-wrap. */ ?>
                                        <div class="hs-dropdown relative inline-flex [--placement:bottom-right]"
                                            data-hs-dropdown-options='{"strategy":"fixed"}'>
                                            <button type="button" class="hs-dropdown-toggle btn-icon" aria-haspopup="menu" aria-expanded="false" title="Aksi lainnya">
                                                <?= svg_icon('ellipsis', 'h-4 w-4'); ?>
                                            </button>
                                            <div class="hs-dropdown-menu dropdown-menu absolute right-0 top-full mt-1 hidden w-56 opacity-0 transition-[opacity,margin] duration-150" role="menu">
                                                <?php foreach ($aksi_lain as $a): ?>
                                                    <a href="<?= $a['href']; ?>"
                                                        class="dropdown-item <?= ! empty($a['danger']) ? 'text-red-600 hover:bg-red-50 [&_svg]:text-red-500' : ''; ?>"
                                                        <?= ! empty($a['blank']) ? 'target="_blank" rel="noopener"' : ''; ?>
                                                        <?= ! empty($a['confirm']) ? 'data-confirm="' . html_escape($a['confirm']) . '"' : ''; ?>
                                                        role="menuitem">
                                                        <?= svg_icon($a['icon']); ?>
                                                        <span><?= html_escape($a['label']); ?></span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Preline overlay: dialog disposisi -->
<div id="modalDisposisi"
    class="hs-overlay pointer-events-none fixed inset-0 z-[80] hidden size-full overflow-y-auto overflow-x-hidden"
    role="dialog" aria-modal="true" tabindex="-1" aria-labelledby="disposisiTitle">
    <div class="pointer-events-none m-3 mt-0 flex min-h-[calc(100%-1.5rem)] items-start opacity-0 transition-all duration-300 ease-out sm:mx-auto sm:w-full sm:max-w-2xl hs-overlay-open:mt-7 hs-overlay-open:opacity-100">
        <div class="pointer-events-auto flex w-full flex-col rounded-xl border border-gray-200 bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                <h3 id="disposisiTitle" class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-900">
                        <?= svg_icon('send', 'h-4 w-4'); ?>
                    </span>
                    Kirim Disposisi
                </h3>
                <button type="button" class="btn-icon !border-transparent !shadow-none" data-hs-overlay="#modalDisposisi" aria-label="Tutup">
                    <?= svg_icon('x', 'h-4 w-4'); ?>
                </button>
            </div>
            <div id="modalDisposisiBody" class="px-5 py-4">
                <p class="py-8 text-center text-sm text-gray-500">Memuat…</p>
            </div>
        </div>
    </div>
</div>

<script>
    var MODAL = document.getElementById('modalDisposisi');
    var BODY = document.getElementById('modalDisposisiBody');
    var BASE = '<?= base_url(); ?>';

    function bukaDisposisi(id) {
        BODY.innerHTML = '<p class="py-8 text-center text-sm text-gray-500">Memuat…</p>';

        if (window.HSOverlay && window.HSOverlay.open) {
            window.HSOverlay.open(MODAL);
        } else {
            MODAL.classList.remove('hidden');
            MODAL.classList.add('open');
        }

        fetch(BASE + 'disposisi/form-ajax/' + id, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) {
                return r.text();
            })
            .then(function(html) {
                BODY.innerHTML = html;
                // Eksekusi <script> yang disisipkan via innerHTML
                BODY.querySelectorAll('script').forEach(function(old) {
                    var s = document.createElement('script');
                    s.textContent = old.textContent;
                    old.parentNode.replaceChild(s, old);
                });
                // Aktifkan plugin Preline untuk form yang baru disuntikkan
                if (typeof window.sinosInitPreline === 'function') window.sinosInitPreline();
            })
            .catch(function() {
                BODY.innerHTML = '<p class="py-8 text-center text-sm text-red-600">Gagal memuat form disposisi.</p>';
            });
    }

    function tutupDisposisi() {
        if (window.HSOverlay && window.HSOverlay.close) {
            window.HSOverlay.close(MODAL);
        } else {
            MODAL.classList.add('hidden');
            MODAL.classList.remove('open');
        }
        BODY.innerHTML = '';
    }

    function kirimDisposisiAjax(form) {
        var data = new FormData(form);
        var select = form.querySelector('select[name="target"]');
        if (!select || !select.value) {
            alert('Pilih penerima disposisi.');
            return false;
        }
        fetch(form.action, {
                method: 'POST',
                body: data,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) {
                if (r.redirected) {
                    window.location.href = r.url;
                } else {
                    return r.text();
                }
            })
            .then(function(html) {
                if (html) {
                    window.location.href = BASE + 'disposisi';
                }
            })
            .catch(function() {
                window.location.href = BASE + 'disposisi';
            });
        return false;
    }
</script>
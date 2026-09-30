<div class="card">
    <div class="flex flex-col gap-4 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold tracking-tight text-foreground">Daftar Surat Masuk</h2>
            <p class="text-xs text-muted-foreground">Agenda surat masuk pada periode terpilih.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php $tahun_action = base_url('surat-masuk/daftar');
            $this->load->view('layouts/_tahun_selector'); ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <?= svg_icon('inbox', 'h-5 w-5'); ?>
            </span>
            <p class="text-sm font-medium text-foreground">Belum ada data</p>
            <p class="text-sm text-muted-foreground">Tidak ada surat masuk untuk tahun <?= (int) $tahun; ?>.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap p-2">
            <table class="table" id="tblMasuk" data-datatable data-nosort="8">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Agenda</th>
                        <th>No. Surat</th>
                        <th>Tanggal</th>
                        <th>Pengirim</th>
                        <th>Perihal</th>
                        <th>Status Disposisi</th>
                        <th>Berkas</th>
                        <th class="col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr class="<?= ($r->status === 'diarsipkan') ? 'opacity-70' : ''; ?>">
                            <td class="text-muted-foreground">
                                <?= $i++; ?>
                                <?php if ($r->status === 'diarsipkan'): ?>
                                    <span class="badge-secondary ml-1">Arsip</span>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($r->no_agenda); ?></td>
                            <td class="font-medium"><?= html_escape($r->no_surat); ?></td>
                            <td class="whitespace-nowrap text-muted-foreground" data-order="<?= html_escape($r->tgl_surat); ?>"><?= tanggal_indonesia($r->tgl_surat); ?></td>
                            <td><?= html_escape($r->pengirim); ?></td>
                            <td><?= html_escape($r->perihal); ?></td>
                            <td>
                                <?php
                                $st = isset($status_disposisi[(int) $r->id]) ? $status_disposisi[(int) $r->id] : NULL;
                                if ($st):
                                ?>
                                    <span class="<?= $st->tone; ?>"><?= html_escape($st->status); ?></span>
                                    <span class="mt-0.5 block text-xs text-muted-foreground">
                                        <?= svg_icon('user', 'mr-1 inline h-3 w-3 align-[-2px]'); ?><?= html_escape($st->lokasi); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-outline">Belum didisposisi</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (! empty($r->file)): ?>
                                    <a href="<?= base_url($r->file); ?>" target="_blank" rel="noopener" class="badge-success">Lihat</a>
                                <?php else: ?>
                                    <span class="badge-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-aksi">
                                <div class="flex justify-end gap-1.5">
                                    <a href="<?= base_url('surat-masuk/detail/' . $r->id); ?>" class="btn-icon" title="Lihat Detail">
                                        <?= svg_icon('eye', 'h-4 w-4'); ?>
                                    </a>
                                    <?php if (! empty($can_send_map[(int) $r->id])): ?>
                                        <button type="button" onclick="bukaDisposisi(<?= (int) $r->id; ?>)" class="btn-icon" title="Kirim / Teruskan Disposisi">
                                            <?= svg_icon('send', 'h-4 w-4'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (! empty($user['is_admin']) || ! empty($user['is_operator'])): ?>
                                        <a href="<?= base_url('surat-masuk/edit/' . $r->id); ?>" class="btn-icon" title="Edit">
                                            <?= svg_icon('edit', 'h-4 w-4'); ?>
                                        </a>
                                        <a href="<?= base_url('surat-masuk/disposisi/' . $r->id); ?>" target="_blank" rel="noopener" class="btn-icon" title="Cetak Lembar Disposisi">
                                            <?= svg_icon('printer', 'h-4 w-4'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (! empty($user['is_operator']) && $r->status === 'diarsipkan'): ?>
                                        <a href="<?= base_url('surat-masuk/batal-arsip/' . $r->id); ?>" data-confirm="Batalkan status arsip surat ini?" class="btn-icon text-amber-600 hover:bg-amber-50" title="Batalkan Arsip">
                                            <?= svg_icon('archive-restore', 'h-4 w-4'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (! empty($user['is_admin'])): ?>
                                        <a href="<?= base_url('surat-masuk/hapus/' . $r->id); ?>" data-confirm="Hapus surat ini?" class="btn-icon text-destructive hover:bg-destructive/10 hover:text-destructive" title="Hapus">
                                            <?= svg_icon('trash', 'h-4 w-4'); ?>
                                        </a>
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

<!-- Dialog Disposisi -->
<div id="modalDisposisi" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto p-4">
    <div class="absolute inset-0 bg-foreground/40 backdrop-blur-[2px]" onclick="tutupDisposisi()"></div>
    <div role="dialog" aria-modal="true" aria-labelledby="disposisiTitle"
        class="relative my-8 w-full max-w-2xl animate-zoom-in rounded-lg border border-border bg-card text-card-foreground shadow-lg">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-md bg-primary/10 text-primary">
                    <?= svg_icon('send', 'h-4 w-4'); ?>
                </span>
                <h3 id="disposisiTitle" class="text-base font-semibold tracking-tight">Kirim Disposisi</h3>
            </div>
            <button type="button" onclick="tutupDisposisi()" class="btn-icon" aria-label="Tutup">
                <?= svg_icon('x', 'h-4 w-4'); ?>
            </button>
        </div>
        <div id="modalDisposisiBody" class="px-6 py-5">
            <p class="py-8 text-center text-sm text-muted-foreground">Memuat…</p>
        </div>
    </div>
</div>

<script>
    var MODAL = document.getElementById('modalDisposisi');
    var BODY = document.getElementById('modalDisposisiBody');
    var BASE = '<?= base_url(); ?>';

    MODAL.style.display = 'none';

    function bukaDisposisi(id) {
        BODY.innerHTML = '<p class="py-8 text-center text-sm text-muted-foreground">Memuat…</p>';
        MODAL.classList.remove('hidden');
        MODAL.style.display = 'flex';

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
            })
            .catch(function() {
                BODY.innerHTML = '<p class="py-8 text-center text-sm text-destructive">Gagal memuat form disposisi.</p>';
            });
    }

    function tutupDisposisi() {
        MODAL.classList.add('hidden');
        MODAL.style.display = 'none';
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
                    // Fallback: muat ulang halaman bila tidak ada redirect
                    window.location.href = BASE + 'disposisi';
                }
            })
            .catch(function() {
                window.location.href = BASE + 'disposisi';
            });
        return false;
    }
</script>
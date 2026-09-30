<div class="card">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-base font-semibold text-slate-700">Daftar Surat Masuk</h2>
            <?php $tahun_action = base_url('surat-masuk/daftar');
            $this->load->view('layouts/_tahun_selector'); ?>
        </div>
        <a href="<?= base_url('surat-masuk'); ?>" class="btn-primary whitespace-nowrap">+ Input Surat</a>
    </div>

    <?php if (empty($rows)): ?>
        <p class="py-10 text-center text-sm text-slate-400">Belum ada data surat masuk untuk tahun <?= (int) $tahun; ?>.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table-modern" id="tblMasuk" data-datatable data-nosort="8">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>No. Agenda</th>
                        <th>No. Surat</th>
                        <th>Tanggal</th>
                        <th>Pengirim</th>
                        <th>Perihal</th>
                        <th>Pengolah</th>
                        <th>Berkas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= html_escape($r->no_agenda); ?></td>
                            <td class="font-medium text-slate-700"><?= html_escape($r->no_surat); ?></td>
                            <td data-order="<?= html_escape($r->tgl_surat); ?>"><?= tanggal_indonesia($r->tgl_surat); ?></td>
                            <td><?= html_escape($r->pengirim); ?></td>
                            <td><?= html_escape($r->perihal); ?></td>
                            <td><?= html_escape($r->pengolah); ?></td>
                            <td>
                                <?php if (! empty($r->file)): ?>
                                    <a href="<?= base_url($r->file); ?>" target="_blank" class="badge bg-emerald-100 text-emerald-700">Lihat</a>
                                <?php else: ?>
                                    <span class="badge bg-slate-100 text-slate-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <a href="<?= base_url('surat-masuk/edit/' . $r->id); ?>" class="btn-icon bg-sky-600 hover:bg-sky-700" title="Edit">✎</a>
                                    <button type="button" onclick="bukaDisposisi(<?= (int) $r->id; ?>)" class="btn-icon bg-brand-600 hover:bg-brand-700" title="Disposisi">📨</button>
                                    <a href="<?= base_url('surat-masuk/disposisi/' . $r->id); ?>" target="_blank" class="btn-icon bg-slate-600 hover:bg-slate-700" title="Cetak Lembar Disposisi">🖨</a>
                                    <?php if (! empty($user['is_admin'])): ?>
                                        <a href="<?= base_url('surat-masuk/hapus/' . $r->id); ?>" data-confirm="Hapus surat ini?" class="btn-icon bg-rose-600 hover:bg-rose-700" title="Hapus">🗑</a>
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

<!-- Modal Disposisi -->
<div id="modalDisposisi" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-900/50 p-4">
    <div class="mt-8 w-full max-w-2xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="text-base font-semibold text-slate-800">Kirim Disposisi</h3>
            <button type="button" onclick="tutupDisposisi()" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100">✕</button>
        </div>
        <div id="modalDisposisiBody" class="px-5 py-4">
            <p class="py-8 text-center text-sm text-slate-400">Memuat...</p>
        </div>
    </div>
</div>

<script>
    var MODAL = document.getElementById('modalDisposisi');
    var BODY = document.getElementById('modalDisposisiBody');
    var BASE = '<?= base_url(); ?>';

    MODAL.style.display = 'none';

    function bukaDisposisi(id) {
        BODY.innerHTML = '<p class="py-8 text-center text-sm text-slate-400">Memuat...</p>';
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
                BODY.innerHTML = '<p class="py-8 text-center text-sm text-rose-500">Gagal memuat form disposisi.</p>';
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
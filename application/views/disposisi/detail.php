<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <!-- Detail surat -->
    <div class="card lg:col-span-2">
        <div class="card-header">
            <h2 class="card-title">Detail Surat</h2>
            <p class="card-description">Informasi surat masuk yang didisposisikan.</p>
        </div>
        <div class="card-content">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-muted-foreground">No. Surat</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= html_escape($surat->no_surat); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">No. Agenda</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= html_escape($surat->no_agenda); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Tanggal Surat</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= tanggal_indonesia($surat->tgl_surat); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Tanggal Diterima</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= tanggal_indonesia($surat->tgl_diterima); ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs text-muted-foreground">Perihal</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= html_escape($surat->perihal); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Pengirim</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= html_escape($surat->pengirim); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Pengolah</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= html_escape($surat->pengolah); ?></dd>
                </div>
            </dl>

            <?php if (! empty($surat->file)): ?>
                <div class="mt-6 border-t border-border pt-5">
                    <a href="<?= base_url($surat->file); ?>" target="_blank" rel="noopener" class="btn-outline btn-sm">
                        <?= svg_icon('file-text'); ?>
                        <span>Lihat Berkas Surat</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informasi disposisi -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Informasi Disposisi</h2>
        </div>
        <div class="card-content">
            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-xs text-muted-foreground">Diteruskan Kepada</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= html_escape($row->kepada); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Kategori</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= html_escape($row->kategori); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Instruksi</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= html_escape(isset($row->instruksi) ? $row->instruksi : '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Catatan</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= html_escape(isset($row->catatan) ? $row->catatan : '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Tanggal Disposisi</dt>
                    <dd class="mt-0.5 font-medium text-foreground"><?= date('d-m-Y H:i', strtotime($row->tanggal)); ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Status</dt>
                    <dd class="mt-1">
                        <?php if ((int) $row->dibaca === 1): ?>
                            <span class="badge-success">Sudah dibaca</span>
                        <?php else: ?>
                            <span class="badge-warning">Belum dibaca</span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>

            <div class="mt-6 space-y-2 border-t border-border pt-5">
                <?php if (! empty($can_forward)): ?>
                    <button type="button" onclick="bukaDisposisi(<?= (int) $row->surmas_id; ?>)" class="btn-primary w-full">
                        <?= svg_icon('send'); ?>
                        <span>Teruskan Disposisi</span>
                    </button>
                <?php endif; ?>
                <a href="<?= base_url('disposisi'); ?>" class="btn-outline w-full">
                    <?= svg_icon('arrow-left'); ?>
                    <span>Kembali</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Teruskan Disposisi (memakai modul yang sama dengan Surat Masuk) -->
<div id="modalDisposisi" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-foreground/50 p-4 backdrop-blur-[1px]">
    <div class="relative my-8 w-full max-w-2xl animate-zoom-in rounded-lg border border-border bg-card text-card-foreground shadow-lg">
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h3 class="text-base font-semibold tracking-tight">Teruskan Disposisi</h3>
            <button type="button" onclick="tutupDisposisi()" class="btn-ghost btn-icon" aria-label="Tutup">
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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && MODAL.style.display === 'flex') tutupDisposisi();
    });

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
                    window.location.href = BASE + 'disposisi';
                }
            })
            .catch(function() {
                window.location.href = BASE + 'disposisi';
            });
        return false;
    }
</script>
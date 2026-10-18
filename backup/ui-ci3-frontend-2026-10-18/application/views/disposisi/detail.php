<?php $this->load->helper('api_berkas'); // tautan berkas bertanda tangan (Fase 2) 
?>
<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <!-- Detail surat -->
    <div class="card lg:col-span-2">
        <div class="card-header">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="card-title">Detail Surat</h2>
                    <p class="card-description">Informasi surat masuk yang didisposisikan.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <?php if ((int) $row->dibaca === 1): ?>
                        <span class="badge-success">Sudah dibaca</span>
                    <?php else: ?>
                        <span class="badge-warning">Belum dibaca</span>
                    <?php endif; ?>
                    <?php if (! empty($surat->file)): ?>
                        <a href="<?= berkas_url($surat->file); ?>" target="_blank" rel="noopener" class="btn-outline btn-sm">
                            <?= svg_icon('file-text'); ?>
                            <span>Lihat Berkas Surat</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-content">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">No. Surat</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($surat->no_surat); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">No. Agenda</dt>
                    <dd class="mt-0.5 text-sm font-medium tabular-nums text-[var(--color-ink,#111827)]"><?= html_escape($surat->no_agenda); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Tanggal Surat</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= tanggal_indonesia($surat->tgl_surat); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Tanggal Diterima</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= tanggal_indonesia($surat->tgl_diterima); ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Perihal</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($surat->perihal); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Pengirim</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($surat->pengirim); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Pengolah</dt>
                    <dd class="mt-0.5 text-sm font-medium text-[var(--color-ink,#111827)]"><?= html_escape($surat->pengolah); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Informasi disposisi -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Informasi Disposisi</h2>
        </div>
        <div class="card-content">
            <dl class="space-y-4">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Diteruskan Kepada</dt>
                    <dd class="mt-0.5 text-sm text-[var(--color-ink,#111827)]"><?= html_escape($row->kepada); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Kategori</dt>
                    <dd class="mt-0.5 text-sm text-[var(--color-ink,#111827)]"><?= html_escape($row->kategori); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Instruksi</dt>
                    <dd class="mt-0.5 text-sm text-[var(--color-ink,#111827)]"><?= html_escape(isset($row->instruksi) ? $row->instruksi : '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Catatan</dt>
                    <dd class="mt-0.5 text-sm text-[var(--color-ink,#111827)]"><?= html_escape(isset($row->catatan) ? $row->catatan : '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Tanggal Disposisi</dt>
                    <dd class="mt-0.5 text-sm tabular-nums text-[var(--color-ink,#111827)]"><?= date('d-m-Y H:i', strtotime($row->tanggal)); ?></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-[var(--color-ink-subtle,#6b7280)]">Status</dt>
                    <dd class="mt-1">
                        <?php if ((int) $row->dibaca === 1): ?>
                            <span class="badge-success">Sudah dibaca</span>
                        <?php else: ?>
                            <span class="badge-warning">Belum dibaca</span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>

            <div class="separator"></div>

            <div class="space-y-2">
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

<!-- Preline overlay: teruskan disposisi (modul sama dengan Surat Masuk) -->
<div id="modalDisposisi"
    class="hs-overlay pointer-events-none fixed inset-0 z-[80] hidden size-full overflow-y-auto overflow-x-hidden"
    role="dialog" tabindex="-1" aria-labelledby="disposisiTitleDetail">
    <div class="pointer-events-none m-3 mt-0 flex min-h-[calc(100%-1.5rem)] items-start opacity-0 transition-all duration-300 ease-out sm:mx-auto sm:w-full sm:max-w-2xl hs-overlay-open:mt-7 hs-overlay-open:opacity-100">
        <div class="pointer-events-auto flex w-full flex-col rounded-xl border border-gray-200 bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                <h3 id="disposisiTitleDetail" class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-900">
                        <?= svg_icon('send', 'h-4 w-4'); ?>
                    </span>
                    Teruskan Disposisi
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
                BODY.querySelectorAll('script').forEach(function(old) {
                    var s = document.createElement('script');
                    s.textContent = old.textContent;
                    old.parentNode.replaceChild(s, old);
                });
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
                    window.location.href = BASE + 'disposisi';
                }
            })
            .catch(function() {
                window.location.href = BASE + 'disposisi';
            });
        return false;
    }
</script>
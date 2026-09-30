<div id="logoutModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-foreground/40 backdrop-blur-[2px]" onclick="document.getElementById('logoutModal').classList.add('hidden')"></div>

    <!-- Panel -->
    <div role="dialog" aria-modal="true" aria-labelledby="logoutTitle"
        class="relative w-full max-w-sm animate-zoom-in rounded-lg border border-border bg-card p-6 text-card-foreground shadow-lg">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-destructive/10 text-destructive">
            <?= svg_icon('log-out', 'h-5 w-5'); ?>
        </div>
        <h3 id="logoutTitle" class="text-base font-semibold tracking-tight">Konfirmasi Logout</h3>
        <p class="mt-1.5 text-sm text-muted-foreground">
            Apakah Anda yakin ingin keluar dari aplikasi SINOS? Anda perlu login kembali untuk mengakses sistem.
        </p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn-outline btn-sm"
                onclick="document.getElementById('logoutModal').classList.add('hidden')">Batal</button>
            <a href="<?= base_url('logout'); ?>" class="btn-destructive btn-sm">
                <?= svg_icon('log-out'); ?>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>
<script>
    // Tampilkan modal sebagai flex saat dibuka (menghapus class hidden)
    (function() {
        var m = document.getElementById('logoutModal');
        if (!m) return;

        function sync() {
            m.style.display = m.classList.contains('hidden') ? 'none' : 'flex';
        }
        new MutationObserver(sync).observe(m, {
            attributes: true,
            attributeFilter: ['class']
        });
        sync();
    })();
</script>
<div id="logoutModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-800">Konfirmasi Logout</h3>
        <p class="mt-2 text-sm text-slate-500">Apakah Anda yakin ingin keluar dari aplikasi SINOS?</p>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('logoutModal').classList.add('hidden')" class="btn-muted">Batal</button>
            <a href="<?= base_url('logout'); ?>" class="btn-danger">Logout</a>
        </div>
    </div>
</div>
<script>
    // Pastikan modal tampil sebagai flex saat dibuka
    (function() {
        var m = document.getElementById('logoutModal');
        if (!m) return;
        new MutationObserver(function() {
            m.style.display = m.classList.contains('hidden') ? 'none' : 'flex';
        }).observe(m, {
            attributes: true,
            attributeFilter: ['class']
        });
        m.style.display = m.classList.contains('hidden') ? 'none' : 'flex';
    })();
</script>
// SINOS - interaksi ringan (Preline menangani dropdown, overlay, select)
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    // Toggle sidebar (mobile)
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    function closeSidebar() {
      if (sidebar) sidebar.classList.add('-translate-x-full');
      if (overlay) overlay.classList.add('hidden');
    }

    if (toggle && sidebar) {
      toggle.addEventListener('click', function () {
        sidebar.classList.toggle('-translate-x-full');
        if (overlay) overlay.classList.toggle('hidden');
      });
    }
    if (overlay) {
      overlay.addEventListener('click', closeSidebar);
    }

    // Auto-dismiss flash message
    document.querySelectorAll('[data-flash]').forEach(function (el) {
      setTimeout(function () {
        el.style.transition = 'opacity .4s';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 400);
      }, 4000);
    });

    // Konfirmasi hapus / aksi destruktif
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        if (!window.confirm(el.getAttribute('data-confirm'))) {
          e.preventDefault();
        }
      });
    });

    // Pencarian tabel sederhana (non-DataTables)
    document.querySelectorAll('[data-table-search]').forEach(function (input) {
      input.addEventListener('input', function () {
        var q = input.value.toLowerCase();
        var table = document.getElementById(input.getAttribute('data-table-search'));
        if (!table) return;
        table.querySelectorAll('tbody tr').forEach(function (tr) {
          tr.style.display = tr.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
        });
      });
    });
  });

  /**
   * Inisialisasi ulang plugin Preline untuk markup yang disuntikkan lewat AJAX
   * (mis. form disposisi di dalam modal).
   */
  window.sinosInitPreline = function () {
    if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
      window.HSStaticMethods.autoInit();
    }
    bindRequiredSelects();
  };

  /**
   * Preline menyembunyikan <select> asli (display:none) dan menggantinya dengan
   * toggle buatan sendiri. Akibatnya atribut `required` tidak bisa difokuskan
   * browser dan submit diblokir dengan pesan "An invalid form control ... is not
   * focusable".
   *
   * Solusi: `required` dipindahkan ke penanda internal, lalu divalidasi manual.
   * Bila kosong, toggle Preline diberi `aria-invalid="true"` (gaya merah lewat
   * `aria-[invalid=true]:` pada toggleClasses) dan difokuskan.
   */
  function bindRequiredSelects() {
    document.querySelectorAll('select[data-hs-select][required]').forEach(function (select) {
      if (select.dataset.requiredBound === '1') return;
      select.dataset.requiredBound = '1';
      select.dataset.required = '1';
      select.removeAttribute('required');

      var wrapper = select.closest('.hs-select') || select.parentElement;
      var toggleEl = wrapper ? wrapper.querySelector('.hs-select-toggle') : null;

      function clearInvalid() {
        if (toggleEl) toggleEl.removeAttribute('aria-invalid');
      }

      select.addEventListener('change', clearInvalid);
      select.addEventListener('input', clearInvalid);
      select.addEventListener('hs.select.change', clearInvalid);

      var form = select.form;
      if (!form) return;

      if (form.dataset.requiredGuard === '1') return;
      form.dataset.requiredGuard = '1';

      form.addEventListener(
        'submit',
        function (e) {
          var firstInvalid = null;

          form.querySelectorAll('select[data-required="1"]').forEach(function (sel) {
            var wrap = sel.closest('.hs-select') || sel.parentElement;
            var tgl = wrap ? wrap.querySelector('.hs-select-toggle') : null;
            if (!sel.value) {
              if (tgl) tgl.setAttribute('aria-invalid', 'true');
              if (!firstInvalid) firstInvalid = tgl || sel;
            } else if (tgl) {
              tgl.removeAttribute('aria-invalid');
            }
          });

          if (firstInvalid) {
            e.preventDefault();
            firstInvalid.focus();
          }
        },
        true
      );
    });
  }

  // Preline melakukan autoInit pada event `load`; jalankan setelahnya agar
  // toggle sudah terbentuk ketika shim dipasang.
  window.addEventListener('load', function () {
    setTimeout(bindRequiredSelects, 0);
  });
})();

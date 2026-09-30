// SINOS - interaksi ringan tanpa jQuery
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    // Toggle sidebar (mobile)
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (toggle && sidebar) {
      toggle.addEventListener('click', function () {
        sidebar.classList.toggle('-translate-x-full');
        if (overlay) overlay.classList.toggle('hidden');
      });
    }
    if (overlay) {
      overlay.addEventListener('click', function () {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
      });
    }

    // Dropdown user
    document.querySelectorAll('[data-dropdown]').forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        var target = document.getElementById(trigger.getAttribute('data-dropdown'));
        if (target) target.classList.toggle('hidden');
      });
    });
    document.addEventListener('click', function () {
      document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) {
        m.classList.add('hidden');
      });
    });

    // Auto-dismiss flash message
    document.querySelectorAll('[data-flash]').forEach(function (el) {
      setTimeout(function () {
        el.style.transition = 'opacity .4s';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 400);
      }, 4000);
    });

    // Konfirmasi hapus
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        if (!window.confirm(el.getAttribute('data-confirm'))) {
          e.preventDefault();
        }
      });
    });

    // Pencarian tabel sederhana
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
})();

// SINOS - interaksi ringan tanpa jQuery
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

    // Dropdown menu (topbar user menu, dsb.)
    document.querySelectorAll('[data-dropdown]').forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        var target = document.getElementById(trigger.getAttribute('data-dropdown'));
        if (!target) return;
        var isHidden = target.classList.contains('hidden');
        // Tutup semua dropdown lain lebih dulu
        document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) {
          m.classList.add('hidden');
        });
        if (isHidden) target.classList.remove('hidden');
        trigger.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
      });
    });
    document.addEventListener('click', function () {
      document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) {
        m.classList.add('hidden');
      });
      document.querySelectorAll('[data-dropdown]').forEach(function (t) {
        t.setAttribute('aria-expanded', 'false');
      });
    });

    // Tutup dengan tombol Escape (dropdown & dialog)
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      document.querySelectorAll('[data-dropdown-menu]').forEach(function (m) {
        m.classList.add('hidden');
      });
      var logout = document.getElementById('logoutModal');
      if (logout && !logout.classList.contains('hidden')) {
        logout.classList.add('hidden');
      }
      var disposisi = document.getElementById('modalDisposisi');
      if (disposisi && !disposisi.classList.contains('hidden') && typeof window.tutupDisposisi === 'function') {
        window.tutupDisposisi();
      }
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

    // Searchable select (mirip Select2) untuk <select data-searchable-select>
    initSearchableSelects();
  });

  // Kelas Tailwind untuk komponen searchable select
  var SS_WRAP = 'relative';
  var SS_TRIGGER = 'input flex items-center justify-between gap-2 cursor-pointer text-left';
  var SS_PANEL = 'fixed z-50 overflow-hidden rounded-md border border-border bg-popover text-popover-foreground shadow-md';
  var SS_SEARCH_WRAP = 'border-b border-border p-2';
  var SS_SEARCH = 'input h-9';
  var SS_LIST = 'max-h-60 overflow-y-auto py-1';
  var SS_OPT = 'flex cursor-pointer items-center gap-2 px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground';
  var SS_OPT_ACTIVE = 'bg-accent text-accent-foreground';
  var SS_EMPTY = 'px-3 py-2 text-sm text-muted-foreground';

  function initSearchableSelects() {
    document.querySelectorAll('select[data-searchable-select]').forEach(function (select) {
      if (select.dataset.ssReady === '1') return;
      select.dataset.ssReady = '1';
      enhanceSelect(select);
    });
  }

  function enhanceSelect(select) {
    var placeholder = select.getAttribute('data-placeholder') || 'Pilih…';

    // Sembunyikan select asli, tapi tetap dipakai untuk submit form
    select.classList.add('hidden');
    select.style.display = 'none';

    var wrap = document.createElement('div');
    wrap.className = SS_WRAP;

    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = SS_TRIGGER;
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    var triggerText = document.createElement('span');
    triggerText.className = 'truncate';
    trigger.appendChild(triggerText);

    var triggerCaret = document.createElement('span');
    triggerCaret.className = 'pointer-events-none text-muted-foreground';
    triggerCaret.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
    trigger.appendChild(triggerCaret);

    var panel = document.createElement('div');
    panel.className = SS_PANEL;
    panel.style.display = 'none';

    var searchWrap = document.createElement('div');
    searchWrap.className = SS_SEARCH_WRAP;

    var search = document.createElement('input');
    search.type = 'text';
    search.className = SS_SEARCH;
    search.placeholder = select.getAttribute('data-search-placeholder') || 'Cari…';
    search.autocomplete = 'off';
    searchWrap.appendChild(search);

    var list = document.createElement('div');
    list.className = SS_LIST;
    list.setAttribute('role', 'listbox');

    panel.appendChild(searchWrap);
    panel.appendChild(list);
    wrap.appendChild(trigger);

    // Panel ditempel ke body agar tidak terpotong/tertimpa oleh elemen lain
    document.body.appendChild(panel);

    select.parentNode.insertBefore(wrap, select.nextSibling);

    function options() {
      return Array.prototype.slice.call(select.options);
    }

    function optionLabel(opt) {
      return (opt.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function updateTrigger() {
      var sel = select.options[select.selectedIndex];
      var has = sel && sel.value !== '';
      triggerText.textContent = has ? optionLabel(sel) : placeholder;
      triggerText.classList.toggle('text-muted-foreground', !has);
    }

    function renderList(filter) {
      var q = (filter || '').toLowerCase();
      list.innerHTML = '';
      var count = 0;

      options().forEach(function (opt) {
        if (opt.disabled && !opt.value) return;
        var label = optionLabel(opt);
        if (q && label.toLowerCase().indexOf(q) === -1 && (opt.value || '').toLowerCase().indexOf(q) === -1) {
          return;
        }
        count++;
        var item = document.createElement('div');
        item.className = SS_OPT + (opt.selected ? ' ' + SS_OPT_ACTIVE : '');
        item.setAttribute('role', 'option');
        item.textContent = label;
        item.addEventListener('mousedown', function (e) {
          e.preventDefault();
          select.value = opt.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          updateTrigger();
          close();
          trigger.focus();
        });
        list.appendChild(item);
      });

      if (count === 0) {
        var empty = document.createElement('div');
        empty.className = SS_EMPTY;
        empty.textContent = 'Tidak ditemukan';
        list.appendChild(empty);
      }
    }

    function positionPanel() {
      var rect = trigger.getBoundingClientRect();
      panel.style.left = rect.left + 'px';
      panel.style.top = (rect.bottom + 4) + 'px';
      panel.style.width = rect.width + 'px';
    }

    function open() {
      // Tutup searchable select lain
      document.querySelectorAll('[data-ss-panel]').forEach(function (p) {
        if (p !== panel) {
          p.style.display = 'none';
          if (p._ssTrigger) p._ssTrigger.setAttribute('aria-expanded', 'false');
        }
      });
      panel.style.display = '';
      panel.setAttribute('data-ss-panel', '1');
      positionPanel();
      trigger.setAttribute('aria-expanded', 'true');
      search.value = '';
      renderList('');
      search.focus();
    }

    function close() {
      panel.style.display = 'none';
      trigger.setAttribute('aria-expanded', 'false');
    }

    window.addEventListener('resize', function () {
      if (panel.style.display !== 'none') positionPanel();
    });
    window.addEventListener('scroll', function () {
      if (panel.style.display !== 'none') positionPanel();
    }, true);

    panel._ssTrigger = trigger;

    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (panel.style.display === 'none') {
        open();
      } else {
        close();
      }
    });

    search.addEventListener('input', function () {
      renderList(search.value);
    });

    search.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        close();
        trigger.focus();
      }
    });

    document.addEventListener('click', function () {
      close();
    });
    panel.addEventListener('click', function (e) {
      e.stopPropagation();
    });

    updateTrigger();
  }
})();

(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('inspection-form');
    if (!form) return;

    const KEY = 'draft_inspection_' + (form.dataset.inspectionId || '');
    const AUTOSAVE_MS = 10000;

    try {
      const saved = localStorage.getItem(KEY);
      if (saved && window.confirm('Ditemukan draft tersimpan di perangkat ini. Muat draft?')) {
        const data = JSON.parse(saved);
        Object.keys(data).forEach(function (name) {
          const el = form.elements.namedItem(name);
          if (!el || el.type === 'file' || el.type === 'hidden') return;
          if (el.length) { /* RadioNodeList */ }
          else { el.value = data[name]; }
        });
      }
    } catch (e) { console.warn('Gagal memuat draft:', e); }

    const timer = setInterval(function () {
      try {
        const data = {};
        new FormData(form).forEach(function (v, k) {
          if (v instanceof File) return;
          data[k] = v;
        });
        delete data.csrf_token;
        localStorage.setItem(KEY, JSON.stringify(data));
      } catch (e) { /* silent */ }
    }, AUTOSAVE_MS);

    form.addEventListener('submit', function () {
      clearInterval(timer);
      localStorage.removeItem(KEY);
    });
  });
})();
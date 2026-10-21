(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const sidebar  = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');

    function closeSidebar() {
      sidebar?.classList.remove('show');
      backdrop?.classList.remove('show');
    }
    function openSidebar() {
      sidebar?.classList.add('show');
      backdrop?.classList.add('show');
    }

    document.querySelectorAll('[data-toggle-sidebar]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (sidebar?.classList.contains('show')) closeSidebar();
        else openSidebar();
      });
    });
    backdrop?.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeSidebar();
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 992) closeSidebar();
    });

    document.querySelectorAll('.alert-dismissible-auto').forEach(function (el) {
      setTimeout(function () {
        el.style.transition = 'opacity .4s ease, transform .4s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateX(120%)';
        setTimeout(function () { el.remove(); }, 400);
      }, 4000);
    });

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        const msg = el.getAttribute('data-confirm') || 'Yakin melanjutkan?';
        if (!window.confirm(msg)) {
          e.preventDefault();
          e.stopImmediatePropagation();
        }
      });
    });
  });
})();
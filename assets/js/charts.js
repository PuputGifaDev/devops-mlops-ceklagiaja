(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const PALETTE = ['#2563eb', '#16a34a', '#f59e0b', '#ef4444', '#8b5cf6', '#0891b2'];

    document.querySelectorAll('canvas[data-labels][data-values]').forEach(function (cv) {
      let labels = [], values = [];
      try {
        labels = JSON.parse(cv.dataset.labels || '[]');
        values = JSON.parse(cv.dataset.values || '[]');
      } catch (e) {
        console.warn('Data chart tidak valid:', cv.id, e);
        return;
      }
      const type = cv.dataset.type || 'bar';
      const label = cv.dataset.label || '';

      new Chart(cv.getContext('2d'), {
        type: type,
        data: {
          labels: labels,
          datasets: [{
            label: label,
            data: values,
            backgroundColor: type === 'pie' || type === 'doughnut'
              ? PALETTE
              : (cv.dataset.color || '#2563eb'),
            borderRadius: type === 'bar' ? 6 : 0,
            borderWidth: type === 'pie' || type === 'doughnut' ? 2 : 0,
            borderColor: '#fff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: type === 'pie' || type === 'doughnut', position: 'bottom' }
          },
          scales: (type === 'bar' || type === 'line') ? {
            y: { beginAtZero: true, ticks: { precision: 0 } }
          } : {}
        }
      });
    });
  });
})();
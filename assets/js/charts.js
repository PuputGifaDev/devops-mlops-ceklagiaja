/**
 * charts.js
 * Logika untuk menampilkan grafik statistik di Dashboard Admin
 */

document.addEventListener('DOMContentLoaded', function () {
    
    // --- 1. GRAFIK TREN INSPEKSI (Line Chart) ---
    const trendCtx = document.getElementById('inspectionTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                datasets: [{
                    label: 'Total Inspeksi',
                    data: [12, 19, 15, 25, 22, 30], // Data ini idealnya dilempar dari PHP
                    borderColor: '#2563eb', // Warna primary dari CSS
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2563eb',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4, // Membuat garis melengkung halus (modern)
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5],
                            color: '#e2e8f0'
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // --- 2. GRAFIK STATUS TEMUAN (Doughnut Chart) ---
    const findingCtx = document.getElementById('findingStatusChart');
    if (findingCtx) {
        new Chart(findingCtx, {
            type: 'doughnut',
            data: {
                labels: ['Open', 'In Progress', 'Completed'],
                datasets: [{
                    data: [10, 5, 15], // Data ini idealnya dilempar dari PHP
                    backgroundColor: [
                        '#ef4444', // Merah untuk Open
                        '#f59e0b', // Kuning untuk In Progress
                        '#10b981'  // Hijau untuk Completed
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%', // Membuat lubang tengah lebih besar (gaya modern)
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                }
            }
        });
    }
});
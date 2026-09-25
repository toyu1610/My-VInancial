function renderCharts(pemasukan, pengeluaran, categories) {
    if (pieChartInstance) pieChartInstance.destroy();
    if (barChartInstance) barChartInstance.destroy();

    // Set Default Warna Teks Grafik untuk Tema Gelap
    Chart.defaults.color = '#cccccc';

    // Pie Chart
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    pieChartInstance = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Pemasukan', 'Pengeluaran'],
            datasets: [{
                data: [pemasukan, pengeluaran],
                backgroundColor: ['#00ffaa', '#ff5555'],
                borderColor: '#1e1e1e',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#ffffff' } }
            }
        }
    });

    // Bar Chart
    const ctxBar = document.getElementById('barChart').getContext('2d');
    barChartInstance = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: Object.keys(categories),
            datasets: [{
                label: 'Pengeluaran (Rp)',
                data: Object.values(categories),
                backgroundColor: '#00ffaa',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: { color: '#cccccc' },
                    grid: { color: '#333333' }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: '#cccccc' },
                    grid: { color: '#333333' }
                }
            },
            plugins: {
                legend: { labels: { color: '#ffffff' } }
            }
        }
    });
}

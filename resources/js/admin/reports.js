const reportsPage = document.getElementById('adminReportsPage');
const salesCanvas = document.getElementById('salesChart');
const commissionCanvas = document.getElementById('commissionChart');

if (reportsPage && salesCanvas && commissionCanvas && typeof Chart !== 'undefined') {
    const { labels, sales, commission } = JSON.parse(reportsPage.dataset.chartConfig || '{}');
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } },
    };

    new Chart(salesCanvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Sales',
                data: sales,
                borderColor: '#7a6a9e',
                backgroundColor: 'rgba(122, 106, 158, 0.15)',
                fill: true,
                tension: 0.4,
            }],
        },
        options: chartOptions,
    });

    new Chart(commissionCanvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Commission',
                data: commission,
                borderColor: '#15803d',
                backgroundColor: 'rgba(21, 128, 61, 0.15)',
                fill: true,
                tension: 0.4,
            }],
        },
        options: chartOptions,
    });
}

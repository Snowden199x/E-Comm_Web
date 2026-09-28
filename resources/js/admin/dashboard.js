const canvas = document.getElementById('salesOverviewChart');

if (canvas && typeof Chart !== 'undefined') {
    const { labels, sales, orders } = JSON.parse(canvas.dataset.chartConfig || '{}');
    const context = canvas.getContext('2d');
    const fill = (color, alpha) => {
        const gradient = context.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, `${color}${alpha}`);
        gradient.addColorStop(1, `${color}05`);
        return gradient;
    };

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Sales',
                    data: sales,
                    borderColor: '#4A2A52',
                    backgroundColor: fill('#8B6E95', '66'),
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: '#4A2A52',
                    pointBorderWidth: 0,
                    yAxisID: 'y',
                },
                {
                    label: 'Orders',
                    data: orders,
                    borderColor: '#C97B5F',
                    backgroundColor: fill('#E0916F', '55'),
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: '#C97B5F',
                    pointBorderWidth: 0,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 900, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#2B1730',
                    padding: 10,
                    cornerRadius: 10,
                    displayColors: true,
                    boxPadding: 4,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#9CA3AF', font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 7 },
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#F1ECF1' },
                    ticks: {
                        color: '#9CA3AF',
                        font: { size: 11 },
                        callback: value => value >= 1000 ? `${value / 1000}k` : value,
                    },
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    border: { display: false },
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#9CA3AF', font: { size: 11 } },
                },
            },
        },
    });
}

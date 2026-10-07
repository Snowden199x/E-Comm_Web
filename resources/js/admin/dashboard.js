// Admin dashboard: weekly sales/orders bar chart with a Sales | Orders toggle.
// Flat colours only. The current (partial) week is the darkest bar.
// Reads the same data-chart-config the controller already supplies.

const canvas = document.getElementById('salesOverviewChart');

if (canvas && typeof Chart !== 'undefined') {
    const { labels, sales, orders } = JSON.parse(canvas.dataset.chartConfig || '{}');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const metrics = {
        sales: { label: 'Sales', data: sales, shade: '#DCCBE0', strong: '#3b1735', money: true },
        orders: { label: 'Orders', data: orders, shade: '#F1D3C6', strong: '#B4573B', money: false },
    };

    const colorsFor = metric => metrics[metric].data.map((_, i, all) => (i === all.length - 1 ? metrics[metric].strong : metrics[metric].shade));
    const formatMoney = value => '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const formatShort = value => (value >= 1000 ? `${Number(value / 1000).toLocaleString('en-PH', { maximumFractionDigits: 1 })}k` : value);

    let current = 'sales';

    const chart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: metrics.sales.label,
                data: metrics.sales.data,
                backgroundColor: colorsFor('sales'),
                hoverBackgroundColor: metrics.sales.strong,
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 44,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: reduceMotion ? false : { duration: 700, easing: 'easeOutQuart' },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#2B1730',
                    padding: 10,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        title: items => `Week of ${items[0].label}`,
                        label: item => (metrics[current].money ? formatMoney(item.parsed.y) : `${item.parsed.y} orders`),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#6b7280', font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 7 },
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#F1ECF1' },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 11 },
                        precision: 0,
                        callback: value => (metrics[current].money ? formatShort(value) : value),
                    },
                },
            },
        },
    });

    document.querySelectorAll('[data-chart-metric]').forEach(button => {
        button.addEventListener('click', () => {
            const next = button.dataset.chartMetric;
            if (next === current || !metrics[next]) return;

            current = next;
            document.querySelectorAll('[data-chart-metric]').forEach(other => {
                other.setAttribute('aria-pressed', String(other === button));
            });

            const dataset = chart.data.datasets[0];
            dataset.label = metrics[next].label;
            dataset.data = metrics[next].data;
            dataset.backgroundColor = colorsFor(next);
            dataset.hoverBackgroundColor = metrics[next].strong;
            chart.update();
        });
    });
}
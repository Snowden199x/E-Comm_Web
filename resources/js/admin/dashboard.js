// Admin dashboard: weekly sales/orders bar chart with a Sales | Orders toggle.
// Flat palette colours only. The current (partial) week is the darkest bar.
// Reads the data-chart-config the controller supplies. Safe to run again after an AJAX page swap.

const PLUM = '#3B1735';
const PLUM_SOFT = '#CDB9D4';
const LAVENDER = '#E4DAEA';
const LINE = '#ECE4EC';
const INK = '#2B1730';
const MUTED = '#6B5D70';

function setupDashboardChart() {
    const canvas = document.getElementById('salesOverviewChart');
    if (!canvas || canvas.dataset.ready === '1') return;

    const frame = canvas.parentElement;
    const skeleton = frame?.querySelector('[data-chart-loading]');
    const failed = frame?.parentElement?.querySelector('[data-chart-error]');

    // Chart.js loads from a CDN (deferred); if it is missing, say so instead of leaving a blank box.
    if (typeof Chart === 'undefined') {
        skeleton?.setAttribute('hidden', '');
        canvas.hidden = true;
        failed?.removeAttribute('hidden');
        return;
    }

    canvas.dataset.ready = '1';
    const { labels, sales, orders } = JSON.parse(canvas.dataset.chartConfig || '{}');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const metrics = {
        sales: { label: 'Sales', data: sales, money: true },
        orders: { label: 'Orders', data: orders, money: false },
    };

    const colorsFor = metric => metrics[metric].data.map((_, i, all) => (i === all.length - 1 ? PLUM : PLUM_SOFT));
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
                hoverBackgroundColor: PLUM,
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 44,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: reduceMotion ? false : { duration: 500, easing: 'easeOutQuart', onComplete: () => skeleton?.setAttribute('hidden', '') },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: INK,
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
                x: { grid: { display: false }, border: { display: false }, ticks: { color: MUTED, font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: LINE },
                    ticks: { color: MUTED, font: { size: 11 }, precision: 0, callback: value => (metrics[current].money ? formatShort(value) : value) },
                },
            },
        },
    });

    skeleton?.setAttribute('hidden', '');

    document.querySelectorAll('[data-chart-metric]').forEach(button => {
        button.addEventListener('click', () => {
            const next = button.dataset.chartMetric;
            if (next === current || !metrics[next]) return;

            current = next;
            document.querySelectorAll('[data-chart-metric]').forEach(other => other.setAttribute('aria-pressed', String(other === button)));

            const dataset = chart.data.datasets[0];
            dataset.label = metrics[next].label;
            dataset.data = metrics[next].data;
            dataset.backgroundColor = colorsFor(next);
            chart.update();
        });
    });
}

setupDashboardChart();
// Sidebar links swap #main-content without a reload; set the chart up again for the new page.
window.addEventListener('ajax:merged', setupDashboardChart);
window.addEventListener('ajax:after', () => window.setTimeout(setupDashboardChart, 0));
document.addEventListener('DOMContentLoaded', setupDashboardChart);
window.addEventListener('load', setupDashboardChart);

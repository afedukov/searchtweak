import {
    Chart, LineController, LineElement, Filler, PointElement,
    LinearScale, CategoryScale, Tooltip, Legend,
    DoughnutController, ArcElement,
} from 'chart.js';
import {tailwindConfig, hexToRGB} from '../utils';

Chart.register(
    LineController, LineElement, Filler, PointElement,
    LinearScale, CategoryScale, Tooltip, Legend,
    DoughnutController, ArcElement,
);

const colorMap = {
    indigo: () => tailwindConfig().theme.colors.indigo[500],
    emerald: () => tailwindConfig().theme.colors.emerald[500],
    amber: () => tailwindConfig().theme.colors.amber[500],
    sky: () => tailwindConfig().theme.colors.sky[500],
};

function getDarkMode() {
    return localStorage.getItem('dark-mode') === 'true';
}

function getThemeColors() {
    const dark = getDarkMode();
    return {
        text: dark ? '#64748B' : '#94a3b8',
        grid: dark ? '#334155' : '#f1f5f9',
        tooltipTitle: dark ? '#f1f5f9' : '#1e293b',
        tooltipBody: dark ? '#f1f5f9' : '#1e293b',
        tooltipBg: dark ? '#334155' : '#ffffff',
        tooltipBorder: dark ? '#475569' : '#e2e8f0',
    };
}

function destroyExisting(canvas) {
    const ctx = canvas.getContext('2d');
    if (ctx.chart) {
        ctx.chart.destroy();
    }
}

// Line chart
function initLineChart(canvas) {
    destroyExisting(canvas);

    const labels = JSON.parse(canvas.getAttribute('data-labels'));
    const values = JSON.parse(canvas.getAttribute('data-values'));
    const colorName = canvas.getAttribute('data-color') || 'indigo';
    const color = (colorMap[colorName] || colorMap.indigo)();
    const theme = getThemeColors();
    const ctx = canvas.getContext('2d');

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.map(d => {
                const date = new Date(d + 'T00:00:00');
                return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
            }),
            datasets: [{
                data: values,
                fill: true,
                backgroundColor: `rgba(${hexToRGB(color)}, 0.08)`,
                borderColor: color,
                borderWidth: 2,
                tension: 0.3,
                pointRadius: 0,
                pointHoverRadius: 3,
                pointBackgroundColor: color,
                pointHoverBackgroundColor: color,
                pointBorderWidth: 0,
                pointHoverBorderWidth: 0,
                clip: 20,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {padding: {top: 12, bottom: 12, left: 12, right: 12}},
            scales: {
                y: {
                    border: {display: false},
                    beginAtZero: true,
                    ticks: {
                        maxTicksLimit: 5,
                        color: theme.text,
                        precision: 0,
                    },
                    grid: {color: theme.grid},
                },
                x: {
                    border: {display: false},
                    grid: {display: false},
                    ticks: {
                        autoSkipPadding: 48,
                        maxRotation: 0,
                        color: theme.text,
                    },
                },
            },
            plugins: {
                legend: {display: false},
                tooltip: {
                    titleFont: {weight: '600'},
                    titleColor: theme.tooltipTitle,
                    bodyColor: theme.tooltipBody,
                    backgroundColor: theme.tooltipBg,
                    borderColor: theme.tooltipBorder,
                },
            },
            interaction: {intersect: false, mode: 'nearest'},
            animation: false,
        },
    });

    ctx.chart = chart;
    return chart;
}

// Doughnut chart
function initDoughnutChart(canvas) {
    destroyExisting(canvas);

    const labels = JSON.parse(canvas.getAttribute('data-labels'));
    const values = JSON.parse(canvas.getAttribute('data-values'));
    const colors = JSON.parse(canvas.getAttribute('data-colors'));
    const theme = getThemeColors();
    const ctx = canvas.getContext('2d');

    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0,
                hoverBorderColor: getDarkMode() ? '#1e293b' : '#ffffff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: theme.text,
                        padding: 16,
                        usePointStyle: true,
                        pointStyleWidth: 10,
                    },
                },
                tooltip: {
                    titleColor: theme.tooltipTitle,
                    bodyColor: theme.tooltipBody,
                    backgroundColor: theme.tooltipBg,
                    borderColor: theme.tooltipBorder,
                },
            },
            animation: false,
        },
    });

    ctx.chart = chart;
    return chart;
}

// Stacked bar chart for judge success rate
function initBarChart(canvas) {
    destroyExisting(canvas);

    const labels = JSON.parse(canvas.getAttribute('data-labels'));
    const success = JSON.parse(canvas.getAttribute('data-success'));
    const failed = JSON.parse(canvas.getAttribute('data-failed'));
    const theme = getThemeColors();
    const ctx = canvas.getContext('2d');

    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Success',
                    data: success,
                    backgroundColor: tailwindConfig().theme.colors.emerald[500],
                    barPercentage: 0.6,
                },
                {
                    label: 'Failed',
                    data: failed,
                    backgroundColor: tailwindConfig().theme.colors.rose[500],
                    barPercentage: 0.6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {padding: {top: 12, bottom: 12, left: 12, right: 12}},
            scales: {
                x: {
                    stacked: true,
                    border: {display: false},
                    grid: {display: false},
                    ticks: {color: theme.text},
                },
                y: {
                    stacked: true,
                    border: {display: false},
                    beginAtZero: true,
                    ticks: {
                        maxTicksLimit: 5,
                        color: theme.text,
                        precision: 0,
                    },
                    grid: {color: theme.grid},
                },
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: theme.text,
                        padding: 16,
                        usePointStyle: true,
                        pointStyleWidth: 10,
                    },
                },
                tooltip: {
                    titleColor: theme.tooltipTitle,
                    bodyColor: theme.tooltipBody,
                    backgroundColor: theme.tooltipBg,
                    borderColor: theme.tooltipBorder,
                },
            },
            animation: false,
        },
    });

    ctx.chart = chart;
    return chart;
}

// Initialize all admin dashboard charts
export function adminDashboardCharts() {
    document.querySelectorAll('canvas[data-admin-line-chart]').forEach(initLineChart);
    document.querySelectorAll('canvas[data-admin-doughnut-chart]').forEach(initDoughnutChart);
    document.querySelectorAll('canvas[data-admin-bar-chart]').forEach(initBarChart);
}

// Initialize a single chart element (for morph.added)
export function adminDashboardChart(el) {
    if (el.hasAttribute('data-admin-line-chart')) {
        initLineChart(el);
    } else if (el.hasAttribute('data-admin-doughnut-chart')) {
        initDoughnutChart(el);
    } else if (el.hasAttribute('data-admin-bar-chart')) {
        initBarChart(el);
    }
}

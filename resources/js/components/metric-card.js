import {Chart, LineController, LineElement, Filler, PointElement, LinearScale, TimeScale, Tooltip} from 'chart.js';
import 'chartjs-adapter-moment';
import {tailwindConfig, formatNumber, hexToRGB} from '../utils';

Chart.register(LineController, LineElement, Filler, PointElement, LinearScale, TimeScale, Tooltip);

export const metricCards = () => {
    const canvasElements = document.querySelectorAll('canvas[data-metric-card]');

    canvasElements.forEach(function(canvas) {
        metricCard(canvas);
    });
}

export const metricCard = (canvas) => {
    const metricId = canvas.getAttribute('data-metric-card');
    const values = JSON.parse(canvas.getAttribute('data-metric-values'));
    const darkMode = localStorage.getItem('dark-mode') === 'true';

    const ctx = canvas.getContext('2d');

    // Check if there's an existing chart instance
    if (ctx.chart) {
        ctx.chart.destroy();
    }

    const textColor = {
        light: '#94a3b8',
        dark: '#64748B'
    };

    const gridColor = {
        light: '#f1f5f9',
        dark: '#334155'
    };

    const tooltipTitleColor = {
        light: '#1e293b',
        dark: '#f1f5f9'
    };

    const tooltipBodyColor = {
        light: '#1e293b',
        dark: '#f1f5f9'
    };

    const tooltipBgColor = {
        light: '#ffffff',
        dark: '#334155'
    };

    const tooltipBorderColor = {
        light: '#e2e8f0',
        dark: '#475569'
    };

    const labels = values.map(item => item.label);
    const data = values.map(item => item.value);

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                // Indigo line
                {
                    data: data,
                    fill: true,
                    backgroundColor: `rgba(${hexToRGB(tailwindConfig().theme.colors.blue[500])}, 0.08)`,
                    borderColor: tailwindConfig().theme.colors.indigo[500],
                    borderWidth: 2,
                    tension: 0,
                    pointRadius: 0,
                    pointHoverRadius: 3,
                    pointBackgroundColor: tailwindConfig().theme.colors.indigo[500],
                    pointHoverBackgroundColor: tailwindConfig().theme.colors.indigo[500],
                    pointBorderWidth: 0,
                    pointHoverBorderWidth: 0,
                    clip: 20,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: 20,
            },
            scales: {
                y: {
                    border: {
                        display: false,
                    },
                    suggestedMin: 0,
                    suggestedMax: 1,
                    ticks: {
                        maxTicksLimit: 5,
                        callback: (value) => formatNumber(value),
                        color: darkMode ? textColor.dark : textColor.light,
                    },
                    grid: {
                        color: darkMode ? gridColor.dark : gridColor.light,
                    },
                },
                x: {
                    display: false,
                    type: 'category',
                    border: {
                        display: false,
                    },
                    grid: {
                        display: false,
                    },
                    ticks: {
                        autoSkipPadding: 48,
                        maxRotation: 0,
                        color: darkMode ? textColor.dark : textColor.light,
                    },
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    titleFont: {
                        weight: '600',
                    },
                    callbacks: {
                        label: (context) => formatNumber(context.parsed.y),
                    },
                    titleColor: darkMode ? tooltipTitleColor.dark : tooltipTitleColor.light,
                    bodyColor: darkMode ? tooltipBodyColor.dark : tooltipBodyColor.light,
                    backgroundColor: darkMode ? tooltipBgColor.dark : tooltipBgColor.light,
                    borderColor: darkMode ? tooltipBorderColor.dark : tooltipBorderColor.light,
                },
            },
            interaction: {
                intersect: false,
                mode: 'nearest',
            },
            animation: false,
        },
    });

    Echo.private('metric-value.' + metricId)
        .listen('.MetricValueCreated', (e) => {
            if (e.values) {
                chart.data.labels = e.values.map(item => item.label);
                chart.data.datasets[0].data = e.values.map(item => item.value);

                chart.update('none');

                const metricValue = document.getElementById('metric-value-' + metricId);
                if (metricValue) {
                    metricValue.innerText = formatNumber(e.value);
                }
            }
        });

    document.addEventListener('darkMode', (e) => {
        const { mode } = e.detail;
        if (mode === 'on') {
            chart.options.scales.x.ticks.color = textColor.dark;
            chart.options.scales.y.ticks.color = textColor.dark;
            chart.options.scales.y.grid.color = gridColor.dark;
            chart.options.plugins.tooltip.titleColor = tooltipTitleColor.dark;
            chart.options.plugins.tooltip.bodyColor = tooltipBodyColor.dark;
            chart.options.plugins.tooltip.backgroundColor = tooltipBgColor.dark;
            chart.options.plugins.tooltip.borderColor = tooltipBorderColor.dark;
        } else {
            chart.options.scales.x.ticks.color = textColor.light;
            chart.options.scales.y.ticks.color = textColor.light;
            chart.options.scales.y.grid.color = gridColor.light;
            chart.options.plugins.tooltip.titleColor = tooltipTitleColor.light;
            chart.options.plugins.tooltip.bodyColor = tooltipBodyColor.light;
            chart.options.plugins.tooltip.backgroundColor = tooltipBgColor.light;
            chart.options.plugins.tooltip.borderColor = tooltipBorderColor.light;
        }
        chart.update('none');
    });
};

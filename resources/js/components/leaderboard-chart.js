import {Chart, LineController, LineElement, Filler, PointElement, LinearScale, TimeScale, Tooltip} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import 'chartjs-adapter-moment';
import {tailwindConfig} from '../utils';

Chart.register(LineController, LineElement, Filler, PointElement, LinearScale, TimeScale, Tooltip);

export const leaderboardCharts = () => {
    const canvasElements = document.querySelectorAll('canvas[data-leaderboard-chart]');

    canvasElements.forEach(function(canvas) {
        leaderboardChart(canvas);
    });
}

export const leaderboardChart = (canvas) => {
    const dataset = JSON.parse(canvas.getAttribute('data-leaderboard-chart'));
    const darkMode = localStorage.getItem('dark-mode') === 'true';

    // Calculate bar width based on the number of items in dataset - when there is more than 10 items, the bar width is 0.8,
    // when there is less than 10 items, the bar width going down, until it reaches 0.2 at 1 item
    const barPercentage = dataset.length >= 10 ? 0.8 : 0.4 + (dataset.length * 0.04);

    const ctx = canvas.getContext('2d');

    // Check if there's an existing chart instance
    if (ctx.chart) {
        ctx.chart.destroy();
    }

    const textColor = {
        light: tailwindConfig().theme.colors.gray[600],
        dark: tailwindConfig().theme.colors.gray[300],
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
        light: tailwindConfig().theme.colors.gray[200],
        dark: tailwindConfig().theme.colors.gray[800],
    };

    const tooltipBgColor = {
        light: tailwindConfig().theme.colors.slate[800],
        dark: tailwindConfig().theme.colors.slate[300],
    };

    const tooltipBorderColor = {
        light: tailwindConfig().theme.colors.slate[900],
        dark: tailwindConfig().theme.colors.slate[200],
    };

    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            datasets: [
                {
                    data: dataset,
                    axis: 'y',
                    backgroundColor: [
                        tailwindConfig().theme.colors.indigo[500],
                    ],
                    hoverBackgroundColor: [
                        tailwindConfig().theme.colors.indigo[600],
                    ],
                    barPercentage: barPercentage,
                    categoryPercentage: barPercentage,
                },
            ],
        },
        options: {
            indexAxis: 'y',
            parsing: {
                yAxisKey: 'label',
                xAxisKey: 'value',
            },
            scales: {
                y: {
                    border: {
                        display: false,
                    },
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: darkMode ? textColor.dark : textColor.light,
                    },
                },
                x: {
                    beginAtZero: true,
                    border: {
                        display: false,
                    },
                    ticks: {
                        maxTicksLimit: 10,
                        color: darkMode ? textColor.dark : textColor.light,
                    },
                    grid: {
                        color: darkMode ? gridColor.dark : gridColor.light,
                    },
                },
            },
            layout: {
                height: 240,
                padding: {
                    top: 12,
                    bottom: 16,
                    left: 30,
                    right: 50,
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    enabled: false,
                },
                datalabels: {
                    formatter: (value) => value.value,
                    labels: {
                        value: {
                            display: true,
                            anchor: 'end',
                            align: 'end',
                            offset: 6,
                            backgroundColor: tailwindConfig().theme.colors.blue[100],
                            borderRadius: 4,
                            padding: 6,
                            color: tailwindConfig().theme.colors.blue[800],
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
            },
            animation: {
                duration: 0,
            },
            maintainAspectRatio: false,
            responsive: true,
        },
        plugins: [
            ChartDataLabels,
        ],
    });

    document.addEventListener('darkMode', (e) => {
        const { mode } = e.detail;
        if (mode === 'on') {
            chart.options.scales.x.ticks.color = textColor.dark;
            chart.options.scales.y.ticks.color = textColor.dark;
            chart.options.scales.x.grid.color = gridColor.dark;
            chart.options.scales.y.grid.color = gridColor.dark;
            chart.options.plugins.tooltip.titleColor = tooltipTitleColor.dark;
            chart.options.plugins.tooltip.bodyColor = tooltipBodyColor.dark;
            chart.options.plugins.tooltip.backgroundColor = tooltipBgColor.dark;
            chart.options.plugins.tooltip.borderColor = tooltipBorderColor.dark;
        } else {
            chart.options.scales.x.ticks.color = textColor.light;
            chart.options.scales.y.ticks.color = textColor.light;
            chart.options.scales.x.grid.color = gridColor.light;
            chart.options.scales.y.grid.color = gridColor.light;
            chart.options.plugins.tooltip.titleColor = tooltipTitleColor.light;
            chart.options.plugins.tooltip.bodyColor = tooltipBodyColor.light;
            chart.options.plugins.tooltip.backgroundColor = tooltipBgColor.light;
            chart.options.plugins.tooltip.borderColor = tooltipBorderColor.light;
        }
        chart.update('none');
    });
};

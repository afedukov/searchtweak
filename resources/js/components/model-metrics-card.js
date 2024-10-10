import {Chart, LineController, LineElement, Filler, PointElement, LinearScale, TimeScale, Tooltip} from 'chart.js';
import 'chartjs-adapter-moment';
import {tailwindConfig, formatNumber, hexToRGB} from '../utils';

Chart.register(LineController, LineElement, Filler, PointElement, LinearScale, TimeScale, Tooltip);

export const modelMetricsCards = () => {
    const canvasElements = document.querySelectorAll('canvas[data-model-metrics-card]');

    canvasElements.forEach(function(canvas) {
        modelMetricsCard(canvas);
    });
}

export const modelMetricsCard = (canvas) => {
    const modelId = canvas.getAttribute('data-model-metrics-card');
    const metrics = JSON.parse(canvas.getAttribute('data-model-metrics-metrics'));
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

    const datasets = metrics.map((item, index) => {
        let hexColor = tailwindConfig().theme.colors.indigo[500];
        const color = item.color.split('-');
        if (color.length === 2) {
            hexColor = tailwindConfig().theme.colors[color[0]][color[1]] || tailwindConfig().theme.colors.indigo[500];
        }

        return {
            label: item.name,
            data: item.dataset,
            fill: true,
            backgroundColor: `rgba(${hexToRGB(hexColor)}, 0.08)`,
            borderColor: hexColor,
            borderWidth: 2,
            tension: 0,
            pointRadius: 5,
            pointHoverRadius: 6,
            pointBackgroundColor: hexColor,
            pointHoverBackgroundColor: hexColor,
            pointBorderWidth: 0,
            pointHoverBorderWidth: 0,
            clip: 20,
        };
    });

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: datasets,
        },
        options: {
            parsing: {
                xAxisKey: 'label',
                yAxisKey: 'value',
            },
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
                    display: true,
                    type: 'time',
                    time: {
                        parser: 'YYYY-MM-DD HH:mm',
                        unit: 'day',
                        tooltipFormat: 'MMM D, YYYY HH:mm',
                    },
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
                htmlLegend: {
                    containerID: 'model-metric-legend-' + modelId,
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
        plugins: [{
            id: 'htmlLegend',
            afterUpdate(c, args, options) {
                const legendContainer = document.getElementById(options.containerID);
                const ul = legendContainer.querySelector('ul');
                if (!ul) return;
                // Remove old legend items
                while (ul.firstChild) {
                    ul.firstChild.remove();
                }
                // Reuse the built-in legendItems generator
                const items = c.options.plugins.legend.labels.generateLabels(c);
                items.forEach((item) => {
                    const metricValue = document.getElementById('value-' + modelId + '-' + item.text);
                    if (metricValue) {
                        metricValue.style.display = item.hidden ? 'none' : 'block';
                    }

                    const li = document.createElement('li');
                    li.style.marginLeft = tailwindConfig().theme.margin[3];
                    // Button element
                    const button = document.createElement('button');
                    button.style.display = 'inline-flex';
                    button.style.alignItems = 'center';
                    button.style.opacity = item.hidden ? '.3' : '';
                    button.onclick = () => {
                        c.setDatasetVisibility(item.datasetIndex, !c.isDatasetVisible(item.datasetIndex));
                        c.update();
                    };
                    // Color box
                    const box = document.createElement('span');
                    box.style.display = 'block';
                    box.style.width = tailwindConfig().theme.width[3];
                    box.style.height = tailwindConfig().theme.height[3];
                    box.style.borderRadius = tailwindConfig().theme.borderRadius.full;
                    box.style.marginRight = tailwindConfig().theme.margin[2];
                    box.style.borderWidth = '3px';
                    box.style.borderColor = c.data.datasets[item.datasetIndex].borderColor;
                    box.style.pointerEvents = 'none';
                    // Label
                    const label = document.createElement('span');
                    label.classList.add('text-slate-500', 'dark:text-slate-400');
                    label.style.fontSize = tailwindConfig().theme.fontSize.sm[0];
                    label.style.lineHeight = tailwindConfig().theme.fontSize.sm[1].lineHeight;
                    const labelText = document.createTextNode(item.text);
                    label.appendChild(labelText);
                    li.appendChild(button);
                    button.appendChild(box);
                    button.appendChild(label);
                    ul.appendChild(li);
                });
            },
        }],
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

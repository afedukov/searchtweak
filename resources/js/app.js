import './bootstrap';

// Toaster @see https://github.com/masmerise/livewire-toaster
import '../../vendor/masmerise/livewire-toaster/resources/js';

// Import Chart.js
import {
    Chart, BarController, BarElement, LinearScale, CategoryScale, Tooltip, Legend,
} from 'chart.js';

Chart.register(BarController, BarElement, LinearScale, CategoryScale, Tooltip, Legend);

import mediumZoom from 'medium-zoom';

// Import flatpickr
import flatpickr from 'flatpickr';

// Import Flowbite
import 'flowbite';
import {initDropdowns, initPopovers} from 'flowbite';

import {metricCards, metricCard} from './components/metric-card';
import {modelMetricCards, modelMetricCard} from "./components/model-metric-card.js";
import {modelMetricsCards, modelMetricsCard} from "./components/model-metrics-card.js";
import {leaderboardChart, leaderboardCharts} from "./components/leaderboard-chart.js";

mediumZoom('[data-zoomable]', {
    margin: 100,
})

// Define Chart.js default settings
/* eslint-disable prefer-destructuring */
Chart.defaults.font.family = '"Inter", sans-serif';
Chart.defaults.font.weight = '500';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.displayColors = false;
Chart.defaults.plugins.tooltip.mode = 'nearest';
Chart.defaults.plugins.tooltip.intersect = false;
Chart.defaults.plugins.tooltip.position = 'nearest';
Chart.defaults.plugins.tooltip.caretSize = 0;
Chart.defaults.plugins.tooltip.caretPadding = 20;
Chart.defaults.plugins.tooltip.cornerRadius = 4;
Chart.defaults.plugins.tooltip.padding = 8;

// Register Chart.js plugin to add a bg option for chart area
Chart.register({
  id: 'chartAreaPlugin',
  // eslint-disable-next-line object-shorthand
  beforeDraw: (chart) => {
    if (chart.config.options.chartArea && chart.config.options.chartArea.backgroundColor) {
      const ctx = chart.canvas.getContext('2d');
      const { chartArea } = chart;
      ctx.save();
      ctx.fillStyle = chart.config.options.chartArea.backgroundColor;
      // eslint-disable-next-line max-len
      ctx.fillRect(chartArea.left, chartArea.top, chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);
      ctx.restore();
    }
  },
});

document.addEventListener('DOMContentLoaded', () => {
  // Light switcher
  const lightSwitches = document.querySelectorAll('.light-switch');
  if (lightSwitches.length > 0) {
    lightSwitches.forEach((lightSwitch, i) => {
      if (localStorage.getItem('dark-mode') === 'true') {
        lightSwitch.checked = true;
      }
      lightSwitch.addEventListener('change', () => {
        const { checked } = lightSwitch;
        lightSwitches.forEach((el, n) => {
          if (n !== i) {
            el.checked = checked;
          }
        });
        document.documentElement.classList.add('[&_*]:!transition-none');
        if (lightSwitch.checked) {
          document.documentElement.classList.add('dark');
          document.querySelector('html').style.colorScheme = 'dark';
          localStorage.setItem('dark-mode', true);
          document.dispatchEvent(new CustomEvent('darkMode', { detail: { mode: 'on' } }));
        } else {
          document.documentElement.classList.remove('dark');
          document.querySelector('html').style.colorScheme = 'light';
          localStorage.setItem('dark-mode', false);
          document.dispatchEvent(new CustomEvent('darkMode', { detail: { mode: 'off' } }));
        }
        setTimeout(() => {
          document.documentElement.classList.remove('[&_*]:!transition-none');
        }, 1);
      });
    });
  }

  metricCards();
  modelMetricCards();
  modelMetricsCards();
  leaderboardCharts();
});

Livewire.hook('morph.added', ({ el }) => {
    // Dropdowns
    if (el.hasAttribute('data-dropdown-toggle') || el.querySelector('[data-dropdown-toggle]')) {
        initDropdowns();
    }

    // Popovers
    if (el.hasAttribute('data-popover') || el.querySelector('[data-popover]')) {
        initPopovers();
    }

    // Metric cards
    if (el.hasAttribute('data-metric-card')) {
        metricCard(el);
    } else {
        const child = el.querySelector('[data-metric-card]');
        if (child) {
            metricCard(child);
        }
    }

    // Model metric cards
    if (el.hasAttribute('data-model-metric-card')) {
        modelMetricCard(el);
    } else {
        const child = el.querySelector('[data-model-metric-card]');
        if (child) {
            modelMetricCard(child);
        }
    }

    // Model metrics cards
    if (el.hasAttribute('data-model-metrics-card')) {
        modelMetricsCard(el);
    } else {
        const child = el.querySelector('[data-model-metrics-card]');
        if (child) {
            modelMetricsCard(child);
        }
    }

    // Leaderboard Charts
    if (el.hasAttribute('data-leaderboard-chart')) {
        leaderboardChart(el);
    } else {
        const child = el.querySelector('[data-leaderboard-chart]');
        if (child) {
            leaderboardChart(child);
        }
    }
})

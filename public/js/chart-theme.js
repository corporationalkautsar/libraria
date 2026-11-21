/* Shared offline Chart.js theme for Libraria.
   Loaded after vendor/js/chart.umd.js. Works without internet. */
(function () {
    'use strict';

    if (typeof Chart === 'undefined') return;

    var palette = [
        '#96603a', '#c99668', '#ddb995', '#b47b4b',
        '#d19a45', '#6b7f8c', '#8b6f9e', '#9a8a7c'
    ];

    Chart.defaults.color = '#77685f';
    Chart.defaults.font.family = '"Segoe UI", Inter, system-ui, -apple-system, sans-serif';
    Chart.defaults.font.size = 11;
    Chart.defaults.borderColor = '#f3ebe3';

    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.boxHeight = 8;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.legend.labels.font = { size: 11, weight: 600 };

    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(62, 35, 19, .95)';
    Chart.defaults.plugins.tooltip.titleFont = { size: 12, weight: 700 };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 11 };
    Chart.defaults.plugins.tooltip.padding = 11;
    Chart.defaults.plugins.tooltip.cornerRadius = 9;
    Chart.defaults.plugins.tooltip.displayColors = true;
    Chart.defaults.plugins.tooltip.boxWidth = 8;
    Chart.defaults.plugins.tooltip.boxHeight = 8;
    Chart.defaults.plugins.tooltip.usePointStyle = true;

    window.ChartTheme = {
        palette: palette,
        bar: function (data, color, hoverColor) {
            return {
                backgroundColor: color || palette[0],
                hoverBackgroundColor: hoverColor || palette[2],
                borderRadius: 7,
                borderSkipped: false,
                maxBarThickness: 28,
                data: data
            };
        },
        barOptions: function () {
            return {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, maxTicksLimit: 6, padding: 8 },
                        grid: { color: '#f2e9e0', drawBorder: false },
                        border: { display: false }
                    },
                    x: {
                        ticks: { padding: 6 },
                        grid: { display: false, drawBorder: false },
                        border: { display: false }
                    }
                }
            };
        },
        doughnutOptions: function () {
            return {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { displayColors: true }
                }
            };
        }
    };
})();

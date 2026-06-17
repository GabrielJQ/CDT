import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(ChartDataLabels);
Chart.defaults.plugins.datalabels.display = false;

window.Chart = Chart;

window.dispatchEvent(new CustomEvent('chartjs-ready'));

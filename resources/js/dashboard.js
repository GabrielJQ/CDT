import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(ChartDataLabels);
Chart.defaults.plugins.datalabels.display = false;

window.Chart = Chart;

window.dispatchEvent(new CustomEvent('chartjs-ready'));

var __dashCharts = {};

function destroyCharts() {
    for (var key in __dashCharts) {
        if (__dashCharts.hasOwnProperty(key)) {
            __dashCharts[key].destroy();
        }
    }
    __dashCharts = {};
}

function pct(value, total) {
    return total > 0 ? Math.round(value / total * 100) : 0;
}

document.addEventListener('DOMContentLoaded', function () {
    window.__dashCharts = window.__dashCharts || __dashCharts;

    function readData() {
        var el = document.getElementById('dashboard-data');
        return el ? JSON.parse(el.textContent) : null;
    }

    function initCharts() {
        if (typeof Chart === 'undefined') {
            window.addEventListener('chartjs-ready', initCharts);
            return;
        }

        var data = readData();
        if (!data) return;

        destroyCharts();

        var total = data.totalCount || 0;
        var connKpis = data.connectivityKpis || {};
        var critical = data.criticalSummary || {};
        var geo = data.geoStats || {};
        var aperturas = data.aperturasPorMes || [];
        var directorio = data.directorioStats || {};
        var auditoria = data.auditoriaKpis || {};

        var isDark = document.documentElement.classList.contains('dark');
        var gridColor = isDark ? 'rgba(255,255,255,0.06)' : '#e5e7eb';
        var tickColor = isDark ? 'rgba(255,255,255,0.5)' : '#000000';
        var textColor = isDark ? '#ffffff' : '#000000';
        function labelColor() { return textColor; }
        Chart.defaults.font.family = "'Montserrat', 'Instrument Sans', system-ui, sans-serif";
        Chart.defaults.color = tickColor;
        Chart.defaults.plugins.legend.labels.boxWidth = 12;
        Chart.defaults.plugins.legend.labels.padding = 8;

        var chartOpts = {
            responsive: true,
            maintainAspectRatio: true,
            animation: { duration: 500, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: true, position: 'bottom', labels: { font: { size: 10 }, usePointStyle: true } },
                tooltip: {
                    enabled: true,
                    bodyFont: { size: 11 },
                    titleFont: { size: 11 },
                    backgroundColor: isDark ? 'rgba(30,41,59,0.95)' : 'rgba(0,0,0,0.8)',
                    padding: 8,
                    cornerRadius: 6,
                },
                datalabels: { display: false },
            },
        };

        // 1. Conectividad — horizontal stacked bar 100%
        var connTotal = connKpis._total || total;
        var connKeys = ['TELEFONIA', 'Señal de celular', 'INTERNET'];
        var connLabels = ['Teléfono fijo', 'Señal Cel.', 'Internet'];
        var connSi = connKeys.map(function (k) { return connKpis[k] ? connKpis[k].yes : 0; });
        var connNo = connKeys.map(function (k) { return connTotal - (connKpis[k] ? connKpis[k].yes : 0); });
        var connMax = connTotal > 0 ? connTotal : 1;

        __dashCharts.connectivity = new Chart(document.getElementById('chart-connectivity'), {
            type: 'bar',
            data: {
                labels: connLabels,
                datasets: [
                    { label: 'Sí', data: connSi, backgroundColor: '#22c55e', borderRadius: { topLeft: 3, bottomLeft: 3 } },
                    { label: 'No', data: connNo, backgroundColor: '#fca5a5', borderRadius: { topRight: 3, bottomRight: 3 } },
                ],
            },
            options: {
                ...chartOpts,
                indexAxis: 'y',
                scales: {
                    x: { stacked: true, max: connMax, grid: { color: gridColor }, ticks: { font: { size: 9 } } },
                    y: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                },
                plugins: {
                    ...chartOpts.plugins,
                    datalabels: {
                        display: function (ctx) {
                            var v = ctx.dataset.data[ctx.dataIndex];
                            return v > 0 && (v / connMax) > 0.06;
                        },
                        formatter: function (v) { return pct(v, connMax) + '%'; },
                        color: labelColor,
                        font: { weight: 'bold', size: 10 },
                        anchor: 'center',
                        align: 'center',
                    },
                },
            },
        });

        // 2. Criticidad — horizontal stacked bar 100%
        var critRojo = critical.rojo || 0;
        var critAmarillo = critical.amarillo || 0;
        var critVerde = critical.verde || 0;
        var critTotal = critRojo + critAmarillo + critVerde;
        var critMax = critTotal > 0 ? critTotal : 1;

        __dashCharts.critical = new Chart(document.getElementById('chart-critical'), {
            type: 'bar',
            data: {
                labels: ['Criticidad'],
                datasets: [
                    { label: 'Críticas (4+)', data: [critRojo], backgroundColor: '#ef4444', borderRadius: { topLeft: 3, bottomLeft: 3 } },
                    { label: 'Monitoreo (2-3)', data: [critAmarillo], backgroundColor: '#eab308' },
                    { label: 'Normales (0-1)', data: [critVerde], backgroundColor: '#22c55e', borderRadius: { topRight: 3, bottomRight: 3 } },
                ],
            },
            options: {
                ...chartOpts,
                indexAxis: 'y',
                scales: {
                    x: { stacked: true, max: critMax, grid: { color: gridColor }, ticks: { font: { size: 9 }, display: false } },
                    y: { stacked: true, grid: { display: false }, ticks: { display: false } },
                },
                plugins: {
                    ...chartOpts.plugins,
                    legend: { ...chartOpts.plugins.legend, position: 'bottom' },
                    datalabels: {
                        display: function (ctx) {
                            var v = ctx.dataset.data[ctx.dataIndex];
                            return v > 0 && (v / critMax) > 0.05;
                        },
                        formatter: function (v) { return pct(v, critMax) + '%'; },
                        color: labelColor,
                        font: { weight: 'bold', size: 12 },
                        anchor: 'center',
                        align: 'center',
                    },
                },
            },
        });

        // 3. Mapa — horizontal stacked bar 100%
        var geoOK = geo.OK || 0;
        var geoSinCoord = geo.SIN_COORDENADAS || geo.sinCoordenadas || 0;
        var geoFueraMx = geo.FUERA_MEXICO || 0;
        var geoFueraEdo = geo.FUERA_ESTADO || 0;
        var geoTotal = geoOK + geoSinCoord + geoFueraMx + geoFueraEdo;
        var geoMax = geoTotal > 0 ? geoTotal : 1;

        __dashCharts.mapa = new Chart(document.getElementById('chart-mapa'), {
            type: 'bar',
            data: {
                labels: ['Georreferencia'],
                datasets: [
                    { label: 'Válidas', data: [geoOK], backgroundColor: '#22c55e', borderRadius: { topLeft: 3, bottomLeft: 3 } },
                    { label: 'Sin coordenadas', data: [geoSinCoord], backgroundColor: '#9ca3af' },
                    { label: 'Fuera de México', data: [geoFueraMx], backgroundColor: '#ef4444' },
                    { label: 'Fuera de territorio', data: [geoFueraEdo], backgroundColor: '#f59e0b', borderRadius: { topRight: 3, bottomRight: 3 } },
                ],
            },
            options: {
                ...chartOpts,
                indexAxis: 'y',
                scales: {
                    x: { stacked: true, max: geoMax, grid: { color: gridColor }, ticks: { font: { size: 9 }, display: false } },
                    y: { stacked: true, grid: { display: false }, ticks: { display: false } },
                },
                plugins: {
                    ...chartOpts.plugins,
                    datalabels: {
                        display: function (ctx) {
                            var v = ctx.dataset.data[ctx.dataIndex];
                            return v > 0 && (v / geoMax) > 0.06;
                        },
                        formatter: function (v) { return pct(v, geoMax) + '%'; },
                        color: labelColor,
                        font: { weight: 'bold', size: 11 },
                        anchor: 'center',
                        align: 'center',
                    },
                },
            },
        });

        // 4. Aperturas — line chart
        if (aperturas && aperturas.length > 0) {
            var aptLabels = aperturas.map(function (m) { return m.label; });
            var aptData = aperturas.map(function (m) { return m.count; });

            __dashCharts.aperturas = new Chart(document.getElementById('chart-aperturas'), {
                type: 'line',
                data: {
                    labels: aptLabels,
                    datasets: [{
                        label: 'Aperturas',
                        data: aptData,
                        borderColor: '#a855f7',
                        backgroundColor: 'rgba(168, 85, 247, 0.10)',
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#a855f7',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }],
                },
                options: {
                    ...chartOpts,
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { font: { size: 9 }, stepSize: 1 } },
                    },
                    plugins: {
                        ...chartOpts.plugins,
                        datalabels: {
                            display: function (ctx) { return ctx.dataset.data[ctx.dataIndex] > 0; },
                            align: 'top',
                            anchor: 'end',
                            offset: 4,
                            color: '#a855f7',
                            font: { weight: 'bold', size: 10 },
                            formatter: function (v) { return v; },
                        },
                    },
                },
            });
        }

        // 5. Directorio — doughnut
        var dirCompletos = directorio.completos || 0;
        var dirIncompletos = directorio.incompletos || 0;
        var dirTotal = dirCompletos + dirIncompletos;
        var dirPct = dirTotal > 0 ? Math.round(dirCompletos / dirTotal * 100) : 0;

        var centerTextPlugin = {
            id: 'centerText',
            afterDraw: function (chart) {
                var opts = chart.config.options.plugins.centerText;
                if (!opts || !('text' in opts)) return;
                var ctx = chart.ctx;
                var area = chart.chartArea;
                var cx = (area.left + area.right) / 2;
                var cy = (area.top + area.bottom) / 2;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = opts.font || 'bold 24px Montserrat, sans-serif';
                ctx.fillStyle = opts.color || '#9ca3af';
                ctx.fillText(opts.text, cx, cy);
                ctx.restore();
            },
        };

        __dashCharts.directorio = new Chart(document.getElementById('chart-directorio'), {
            type: 'doughnut',
            data: {
                labels: ['Completos', 'Incompletos'],
                datasets: [{
                    data: [dirCompletos, dirIncompletos],
                    backgroundColor: ['#22c55e', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: isDark ? '#1f2937' : '#ffffff',
                }],
            },
            options: {
                ...chartOpts,
                cutout: '70%',
                plugins: {
                    ...chartOpts.plugins,
                    centerText: {
                        text: dirPct + '%',
                        font: 'bold 28px Montserrat, sans-serif',
                        color: textColor,
                    },
                },
            },
            plugins: [centerTextPlugin],
        });

        // 6. Auditoría — horizontal bar
        var auditItems = [
            { label: 'Comités vencidos', value: auditoria.comitesVencidos || 0, color: isDark ? '#ef4444' : '#691C32' },
            { label: 'Auditorías > $500 mil', value: auditoria.auditoriaAlta || 0, color: isDark ? '#f59e0b' : '#988256' },
            { label: 'Rotación baja (<0.5)', value: auditoria.rotacionBaja || 0, color: isDark ? '#22c55e' : '#13322B' },
            { label: 'Aud. pendiente (>3 meses)', value: auditoria.auditoriaPendiente || 0, color: isDark ? '#9ca3af' : '#4D4D4D' },
        ];
        auditItems.sort(function (a, b) { return b.value - a.value; });

        __dashCharts.auditoria = new Chart(document.getElementById('chart-auditoria'), {
            type: 'bar',
            data: {
                labels: auditItems.map(function (i) { return i.label; }),
                datasets: [{
                    label: 'Tiendas',
                    data: auditItems.map(function (i) { return i.value; }),
                    backgroundColor: auditItems.map(function (i) { return i.color; }),
                    borderRadius: 3,
                }],
            },
            options: {
                ...chartOpts,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, grid: { color: gridColor }, ticks: { font: { size: 9 } } },
                    y: { grid: { display: false }, ticks: { font: { size: 10 } } },
                },
                plugins: {
                    ...chartOpts.plugins,
                    legend: { display: false },
                    datalabels: {
                        display: function (ctx) { return ctx.dataset.data[ctx.dataIndex] > 0; },
                        anchor: 'end',
                        align: 'end',
                        offset: 4,
                        color: textColor,
                        font: { weight: 'bold', size: 10 },
                        formatter: function (v) { return v; },
                    },
                },
            },
        });
    }

    initCharts();

    Livewire.on('dashboard-rendered', function () {
        initCharts();
    });

    var temaBtn = document.getElementById('tema-toggle');
    if (temaBtn) {
        temaBtn.addEventListener('click', function () {
            setTimeout(initCharts, 50);
        });
    }
});

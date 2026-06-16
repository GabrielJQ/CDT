@extends('layouts.app', ['pageTitle' => 'Dashboard'])

@section('title', 'Dashboard — CDT')

@section('content')
    <livewire:dashboard-content />
@endsection

@push('footer')
@vite('resources/js/dashboard.js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.__dashCharts = window.__dashCharts || {};

    function readData() {
        var el = document.getElementById('dashboard-data');
        return el ? JSON.parse(el.textContent) : null;
    }

    function destroyCharts() {
        for (var key in window.__dashCharts) {
            if (window.__dashCharts.hasOwnProperty(key)) {
                window.__dashCharts[key].destroy();
            }
        }
        window.__dashCharts = {};
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

        Chart.defaults.font.family = "'Montserrat', 'Instrument Sans', system-ui, sans-serif";
        Chart.defaults.color = '#9ca3af';
        Chart.defaults.plugins.legend.labels.boxWidth = 12;
        Chart.defaults.plugins.legend.labels.padding = 8;

        var chartOpts = {
            responsive: true,
            maintainAspectRatio: true,
            animation: false,
            plugins: {
                legend: { display: true, position: 'bottom', labels: { font: { size: 10 } } },
                tooltip: { enabled: true, bodyFont: { size: 11 }, titleFont: { size: 11 } },
            },
        };

        // 1. Connectivity — stacked bar (Sí/No for T, C, I)
        var connTotal = connKpis._total || total;
        var connSi = [], connNo = [];
        ['TELEFONIA', 'Señal de celular', 'INTERNET'].forEach(function (key) {
            var k = connKpis[key] || { yes: 0 };
            connSi.push(k.yes);
            connNo.push(connTotal - k.yes);
        });

        window.__dashCharts.connectivity = new Chart(document.getElementById('chart-connectivity'), {
            type: 'bar',
            data: {
                labels: ['Teléfono fijo', 'Señal Cel.', 'Internet'],
                datasets: [
                    { label: 'Sí', data: connSi, backgroundColor: '#22c55e', borderRadius: 3 },
                    { label: 'No', data: connNo, backgroundColor: '#fca5a5', borderRadius: 3 },
                ],
            },
            options: {
                ...chartOpts,
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 9 } } },
                },
            },
        });

        // 2. Critical stores — doughnut (R/A/V)
        window.__dashCharts.critical = new Chart(document.getElementById('chart-critical'), {
            type: 'doughnut',
            data: {
                labels: ['Críticas (4+ factores)', 'Monitoreo (2-3 factores)', 'Normales (0-1 factores)'],
                datasets: [{
                    data: [critical.rojo || 0, critical.amarillo || 0, critical.verde || 0],
                    backgroundColor: ['#ef4444', '#eab308', '#22c55e'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: { ...chartOpts, cutout: '55%', plugins: { ...chartOpts.plugins, legend: { display: true, position: 'bottom', labels: { font: { size: 10 } } } } },
        });

        // 3. Mapa — doughnut (estatus geográfico)
        window.__dashCharts.mapa = new Chart(document.getElementById('chart-mapa'), {
            type: 'doughnut',
            data: {
                labels: ['Válidas', 'Sin coordenadas', 'Fuera de México', 'Fuera de territorio'],
                datasets: [{
                    data: [geo.OK || 0, geo.SIN_COORDENADAS || geo.sinCoordenadas || 0, geo.FUERA_MEXICO || 0, geo.FUERA_ESTADO || 0],
                    backgroundColor: ['#10b981', '#9ca3af', '#ef4444', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: { ...chartOpts, cutout: '55%', plugins: { ...chartOpts.plugins, legend: { display: true, position: 'bottom', labels: { font: { size: 10 } } } } },
        });

        // 4. Aperturas — bar (12 months)
        if (aperturas && aperturas.length > 0) {
            window.__dashCharts.aperturas = new Chart(document.getElementById('chart-aperturas'), {
                type: 'bar',
                data: {
                    labels: aperturas.map(function (m) { return m.label; }),
                    datasets: [{
                        label: 'Aperturas',
                        data: aperturas.map(function (m) { return m.count; }),
                        backgroundColor: '#a855f7',
                        borderRadius: 3,
                    }],
                },
                options: {
                    ...chartOpts,
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 9 }, stepSize: 1 } },
                    },
                },
            });
        }

        // 5. Directorio — doughnut (Completos/Incompletos)
        window.__dashCharts.directorio = new Chart(document.getElementById('chart-directorio'), {
            type: 'doughnut',
            data: {
                labels: ['Completos', 'Incompletos'],
                datasets: [{
                    data: [directorio.completos || 0, directorio.incompletos || 0],
                    backgroundColor: ['#22c55e', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: { ...chartOpts, cutout: '55%', plugins: { ...chartOpts.plugins, legend: { display: true, position: 'bottom', labels: { font: { size: 10 } } } } },
        });

        // 6. Auditoría — horizontal bar (4 metrics)
        window.__dashCharts.auditoria = new Chart(document.getElementById('chart-auditoria'), {
            type: 'bar',
            data: {
                labels: ['Comités vencidos', 'Auditorías > $500 mil', 'Rotación baja (<0.5)', 'Aud. pendiente (>3 meses)'],
                datasets: [{
                    label: 'Tiendas',
                    data: [auditoria.comitesVencidos || 0, auditoria.auditoriaAlta || 0, auditoria.rotacionBaja || 0, auditoria.auditoriaPendiente || 0],
                    backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#6b7280'],
                    borderRadius: 3,
                }],
            },
            options: {
                ...chartOpts,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 9 } } },
                    y: { grid: { display: false }, ticks: { font: { size: 10 } } },
                },
            },
        });
    }

    initCharts();

    Livewire.on('dashboard-rendered', function () {
        initCharts();
    });
});
</script>
@endpush

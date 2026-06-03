@extends('layouts.app', ['pageTitle' => 'Dashboard'])

@section('title', 'Dashboard — CDT')

@section('content')
    {{-- KPI bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 lg:gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-3 lg:px-4 py-2 lg:py-3">
            <div class="text-lg lg:text-xl font-bold text-gray-800 dark:text-gray-100">{{ $totalCount }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">🏪 Total de tiendas</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-3 lg:px-4 py-2 lg:py-3">
            <div class="text-lg lg:text-xl font-bold text-red-600">{{ $criticalSummary['rojo'] }}</div>
            <div class="text-xs text-red-500">⚠️ Tiendas críticas</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-3 lg:px-4 py-2 lg:py-3">
            <div class="text-lg lg:text-xl font-bold text-orange-600">{{ $sinConectividad }}</div>
            <div class="text-xs text-orange-500">📡 Sin conectividad</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-3 lg:px-4 py-2 lg:py-3">
            <div class="text-lg lg:text-xl font-bold text-blue-600">{{ $aperturasEsteMes }}</div>
            <div class="text-xs text-blue-500">📅 Aperturas este mes</div>
        </div>
    </div>

    {{-- 2x3 chart grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-5 mb-6">

        {{-- Conectividad --}}
        <a href="{{ url('/conectividad') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-blue-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm lg:text-base font-bold text-gray-800 dark:text-gray-100 group-hover:text-blue-600 transition">📡 Conectividad</h3>
                <span class="text-xs text-gray-400 group-hover:text-blue-600 transition">Ver más →</span>
            </div>
            @if(!empty($connectivityKpis))
                <canvas id="chart-connectivity" class="w-full max-h-52"></canvas>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500 py-8 text-center">Sin datos</p>
            @endif
        </a>

        {{-- Info Tiendas --}}
        <a href="{{ url('/informacion-tiendas') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-red-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm lg:text-base font-bold text-gray-800 dark:text-gray-100 group-hover:text-red-600 transition">⚠️ Info. Tiendas</h3>
                <span class="text-xs text-gray-400 group-hover:text-red-600 transition">Ver más →</span>
            </div>
            @if($criticalSummary && ($criticalSummary['rojo'] + $criticalSummary['amarillo'] + $criticalSummary['verde']) > 0)
                <canvas id="chart-critical" class="w-full max-h-52"></canvas>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500 py-8 text-center">Sin datos</p>
            @endif
        </a>

        {{-- Mapa --}}
        <a href="{{ url('/mapa') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-emerald-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm lg:text-base font-bold text-gray-800 dark:text-gray-100 group-hover:text-emerald-600 transition">🗺️ Mapa</h3>
                <span class="text-xs text-gray-400 group-hover:text-emerald-600 transition">Ver más →</span>
            </div>
            @if($geoStats && (($geoStats['OK'] ?? 0) + ($geoStats['FUERA_ESTADO'] ?? 0) + ($geoStats['FUERA_MEXICO'] ?? 0) + ($geoStats['SIN_COORDENADAS'] ?? 0)) > 0)
                <canvas id="chart-mapa" class="w-full max-h-52"></canvas>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500 py-8 text-center">Sin datos</p>
            @endif
        </a>

        {{-- Aperturas --}}
        <a href="{{ url('/aperturas') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-purple-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm lg:text-base font-bold text-gray-800 dark:text-gray-100 group-hover:text-purple-600 transition">🏗️ Aperturas</h3>
                <span class="text-xs text-gray-400 group-hover:text-purple-600 transition">Ver más →</span>
            </div>
            @if(!empty($aperturasPorMes))
                <canvas id="chart-aperturas" class="w-full max-h-52"></canvas>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500 py-8 text-center">Sin datos</p>
            @endif
        </a>

        {{-- Directorio --}}
        <a href="{{ url('/directorio') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-amber-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm lg:text-base font-bold text-gray-800 dark:text-gray-100 group-hover:text-amber-600 transition">📋 Directorio</h3>
                <span class="text-xs text-gray-400 group-hover:text-amber-600 transition">Ver más →</span>
            </div>
            @if($directorioStats && ($directorioStats['completos'] + $directorioStats['incompletos']) > 0)
                <canvas id="chart-directorio" class="w-full max-h-52"></canvas>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500 py-8 text-center">Sin datos</p>
            @endif
        </a>

        {{-- Auditoría --}}
        <a href="{{ url('/auditoria') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-purple-600 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm lg:text-base font-bold text-gray-800 dark:text-gray-100 group-hover:text-purple-600 transition">🔍 Auditoría</h3>
                <span class="text-xs text-gray-400 group-hover:text-purple-600 transition">Ver más →</span>
            </div>
            @if($auditoriaKpis)
                <canvas id="chart-auditoria" class="w-full max-h-52"></canvas>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500 py-8 text-center">Sin datos</p>
            @endif
        </a>

    </div>

    @isset($updatedAt)
        <p class="text-xs text-gray-400 dark:text-gray-500 text-right">Última actualización: {{ $updatedAt }}</p>
    @endisset
@endsection

@push('footer')
@vite('resources/js/dashboard.js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function initCharts() {
        if (typeof Chart === 'undefined') {
            window.addEventListener('chartjs-ready', initCharts);
            return;
        }

        var total = {{ $totalCount }};
        var connKpis = @json($connectivityKpis);
        var critical = @json($criticalSummary);
        var geo = @json($geoStats);
        var aperturas = @json($aperturasPorMes);
        var directorio = @json($directorioStats);
        var auditoria = @json($auditoriaKpis);

        Chart.defaults.font.family = "'Instrument Sans', system-ui, sans-serif";
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
        var connLabels = { TELEFONIA: 'Teléfono', 'Señal de celular': 'Señal Cel.', INTERNET: 'Internet' };
        var connSi = [], connNo = [];
        ['TELEFONIA', 'Señal de celular', 'INTERNET'].forEach(function (key) {
            var k = connKpis[key] || { yes: 0 };
            connSi.push(k.yes);
            connNo.push(connTotal - k.yes);
        });

        new Chart(document.getElementById('chart-connectivity'), {
            type: 'bar',
            data: {
                labels: ['Teléfono', 'Señal Cel.', 'Internet'],
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
        new Chart(document.getElementById('chart-critical'), {
            type: 'doughnut',
            data: {
                labels: ['Críticas', 'Monitoreo', 'Normales'],
                datasets: [{
                    data: [critical.rojo, critical.amarillo, critical.verde],
                    backgroundColor: ['#ef4444', '#eab308', '#22c55e'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: { ...chartOpts, cutout: '55%', plugins: { ...chartOpts.plugins, legend: { display: true, position: 'bottom', labels: { font: { size: 10 } } } } },
        });

        // 3. Mapa — doughnut (Válidas / Fuera de México / Sin coordenadas)
        new Chart(document.getElementById('chart-mapa'), {
            type: 'doughnut',
            data: {
                labels: ['Válidas en México', 'Fuera de México', 'Sin coordenadas'],
                datasets: [{
                    data: [(geo.OK || 0) + (geo.FUERA_ESTADO || 0), geo.FUERA_MEXICO || 0, geo.SIN_COORDENADAS || 0],
                    backgroundColor: ['#10b981', '#ef4444', '#9ca3af'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: { ...chartOpts, cutout: '55%', plugins: { ...chartOpts.plugins, legend: { display: true, position: 'bottom', labels: { font: { size: 10 } } } } },
        });

        // 4. Aperturas — bar (12 months)
        if (aperturas && aperturas.length > 0) {
            new Chart(document.getElementById('chart-aperturas'), {
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
        new Chart(document.getElementById('chart-directorio'), {
            type: 'doughnut',
            data: {
                labels: ['Completos', 'Incompletos'],
                datasets: [{
                    data: [directorio.completos, directorio.incompletos],
                    backgroundColor: ['#22c55e', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: { ...chartOpts, cutout: '55%', plugins: { ...chartOpts.plugins, legend: { display: true, position: 'bottom', labels: { font: { size: 10 } } } } },
        });

        // 6. Auditoría — horizontal bar (4 metrics)
        new Chart(document.getElementById('chart-auditoria'), {
            type: 'bar',
            data: {
                labels: ['Comités venc.', 'Auditoría >$500k', 'Rotación baja', 'Aud. pendiente'],
                datasets: [{
                    label: 'Tiendas',
                    data: [auditoria.comitesVencidos, auditoria.auditoriaAlta, auditoria.rotacionBaja, auditoria.auditoriaPendiente],
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
});
</script>
@endpush

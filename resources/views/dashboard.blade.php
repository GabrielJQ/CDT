@extends('layouts.app', ['pageTitle' => 'Dashboard'])

@section('title', 'Dashboard — CDT')

@section('content')
    @php
        $criticalPct = $totalCount > 0 ? round($criticalSummary['rojo'] / $totalCount * 100, 1) : 0;
        $sinConectividadPct = $totalCount > 0 ? round($sinConectividad / $totalCount * 100, 1) : 0;
        $aperturasPct = $totalCount > 0 ? round($aperturasEsteMes / $totalCount * 100, 1) : 0;
        $geoSinCoordenadas = $geoStats['sinCoordenadas'] ?? 0;
        $geoTotal = ($geoStats['conCoordenadas'] ?? 0) + $geoSinCoordenadas;
        $geoPct = $geoTotal > 0 ? round($geoSinCoordenadas / $geoTotal * 100, 1) : 0;
    @endphp

    <div class="page-shell">
        <section class="page-hero">
            <div class="page-hero-content">
                <div>
                    <p class="eyebrow">Centro de monitoreo</p>
                    <h1 class="page-heading">Prioridad operativa de tiendas</h1>
                    <p class="page-subheading">Vista ejecutiva para detectar criticidad, brechas de conectividad, problemas de georreferencia y seguimiento operativo por modulo.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ url('/informacion-tiendas?nivel=rojo') }}" class="btn-guinda">Ver criticas</a>
                    <a href="{{ url('/directorio') }}" class="btn-secondary">Abrir directorio</a>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="kpi-card">
                <p class="kpi-label">Total de tiendas</p>
                <p class="kpi-value">{{ number_format($totalCount) }}</p>
                <p class="kpi-meta">Base activa del filtro global</p>
            </div>
            <div class="kpi-card">
                <p class="kpi-label">Tiendas criticas</p>
                <p class="kpi-value text-red-600">{{ number_format($criticalSummary['rojo']) }}</p>
                <p class="kpi-meta">{{ $criticalPct }}% con 4+ factores</p>
            </div>
            <div class="kpi-card">
                <p class="kpi-label">Sin conectividad</p>
                <p class="kpi-value text-orange-600">{{ number_format($sinConectividad) }}</p>
                <p class="kpi-meta">{{ $sinConectividadPct }}% sin servicios</p>
            </div>
            <div class="kpi-card">
                <p class="kpi-label">Aperturas del mes</p>
                <p class="kpi-value text-blue-600">{{ number_format($aperturasEsteMes) }}</p>
                <p class="kpi-meta">{{ $aperturasPct }}% del total filtrado</p>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="priority-panel">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="eyebrow">Prioridad</p>
                        <h2 class="text-lg font-extrabold text-gray-900 dark:text-gray-100">Frentes que requieren atencion</h2>
                    </div>
                    <span class="status-pill {{ $criticalSummary['rojo'] > 0 ? 'status-critical' : 'status-ok' }}">{{ $criticalSummary['rojo'] > 0 ? 'Accion' : 'Estable' }}</span>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <a href="{{ url('/informacion-tiendas?nivel=rojo') }}" class="priority-item">
                        <div>
                            <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Criticidad</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tiendas con acumulacion de factores operativos.</p>
                        </div>
                        <span class="status-pill status-critical">{{ number_format($criticalSummary['rojo']) }}</span>
                    </a>
                    <a href="{{ url('/conectividad?telefono=no&senial=no&internet=no') }}" class="priority-item">
                        <div>
                            <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Conectividad</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sucursales sin telefono, senal celular ni internet.</p>
                        </div>
                        <span class="status-pill status-warning">{{ number_format($sinConectividad) }}</span>
                    </a>
                    <a href="{{ url('/mapa?estado_geo=SIN_COORDENADAS') }}" class="priority-item">
                        <div>
                            <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Georreferencia</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Registros que impiden visualizar cobertura territorial.</p>
                        </div>
                        <span class="status-pill {{ $geoSinCoordenadas > 0 ? 'status-warning' : 'status-ok' }}">{{ number_format($geoSinCoordenadas) }}</span>
                    </a>
                </div>
            </div>

            <div class="priority-panel">
                <p class="eyebrow">Lectura rapida</p>
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-gray-100">Resumen del filtro actual</h2>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Tiendas en monitoreo preventivo</span>
                        <strong class="text-gray-900 dark:text-gray-100">{{ number_format($criticalSummary['amarillo']) }}</strong>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Tiendas normales</span>
                        <strong class="text-green-600">{{ number_format($criticalSummary['verde']) }}</strong>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Sin coordenadas</span>
                        <strong class="text-amber-600">{{ number_format($geoSinCoordenadas) }} <span class="text-xs font-semibold text-gray-400">({{ $geoPct }}%)</span></strong>
                    </div>
                </div>
                @isset($updatedAt)
                    <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">Ultima actualizacion: {{ $updatedAt }}</p>
                @endisset
            </div>
        </section>

        <section>
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <p class="eyebrow">Modulos</p>
                    <h2 class="text-xl font-extrabold text-gray-900 dark:text-gray-100">Exploracion por dominio</h2>
                </div>
                <span class="hidden text-xs font-semibold text-gray-400 md:block">Cada tarjeta abre el modulo con filtros y exportacion</span>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <a href="{{ url('/conectividad') }}" class="module-card">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="module-title">Conectividad</h3>
                        <span class="module-link">Ver mas</span>
                    </div>
                    @if(!empty($connectivityKpis))
                        <canvas id="chart-connectivity" class="w-full max-h-52"></canvas>
                    @else
                        <p class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
                    @endif
                </a>

                <a href="{{ url('/informacion-tiendas') }}" class="module-card">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="module-title">Informacion de tiendas</h3>
                        <span class="module-link">Ver mas</span>
                    </div>
                    @if($criticalSummary && ($criticalSummary['rojo'] + $criticalSummary['amarillo'] + $criticalSummary['verde']) > 0)
                        <canvas id="chart-critical" class="w-full max-h-52"></canvas>
                    @else
                        <p class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
                    @endif
                </a>

                <a href="{{ url('/mapa') }}" class="module-card">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="module-title">Mapa territorial</h3>
                        <span class="module-link">Ver mas</span>
                    </div>
                    @if($geoStats && ($geoStats['conCoordenadas'] + $geoStats['sinCoordenadas']) > 0)
                        <canvas id="chart-mapa" class="w-full max-h-52"></canvas>
                    @else
                        <p class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
                    @endif
                </a>

                <a href="{{ url('/aperturas') }}" class="module-card">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="module-title">Aperturas</h3>
                        <span class="module-link">Ver mas</span>
                    </div>
                    @if(!empty($aperturasPorMes))
                        <canvas id="chart-aperturas" class="w-full max-h-52"></canvas>
                    @else
                        <p class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
                    @endif
                </a>

                <a href="{{ url('/directorio') }}" class="module-card">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="module-title">Directorio</h3>
                        <span class="module-link">Ver mas</span>
                    </div>
                    @if($directorioStats && ($directorioStats['completos'] + $directorioStats['incompletos']) > 0)
                        <canvas id="chart-directorio" class="w-full max-h-52"></canvas>
                    @else
                        <p class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
                    @endif
                </a>

                <a href="{{ url('/auditoria') }}" class="module-card">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="module-title">Auditoria operativa</h3>
                        <span class="module-link">Ver mas</span>
                    </div>
                    @if($auditoriaKpis)
                        <canvas id="chart-auditoria" class="w-full max-h-52"></canvas>
                    @else
                        <p class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
                    @endif
                </a>
            </div>
        </section>
    </div>
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
        var connLabels = { TELEFONIA: 'Teléfono fijo', 'Señal de celular': 'Señal Cel.', INTERNET: 'Internet' };
        var connSi = [], connNo = [];
        ['TELEFONIA', 'Señal de celular', 'INTERNET'].forEach(function (key) {
            var k = connKpis[key] || { yes: 0 };
            connSi.push(k.yes);
            connNo.push(connTotal - k.yes);
        });

        new Chart(document.getElementById('chart-connectivity'), {
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
        new Chart(document.getElementById('chart-critical'), {
            type: 'doughnut',
            data: {
                labels: ['Críticas (4+ factores)', 'Monitoreo (2-3 factores)', 'Normales (0-1 factores)'],
                datasets: [{
                    data: [critical.rojo, critical.amarillo, critical.verde],
                    backgroundColor: ['#ef4444', '#eab308', '#22c55e'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: { ...chartOpts, cutout: '55%', plugins: { ...chartOpts.plugins, legend: { display: true, position: 'bottom', labels: { font: { size: 10 } } } } },
        });

        // 3. Mapa — doughnut (Con/Sin coordenadas)
        new Chart(document.getElementById('chart-mapa'), {
            type: 'doughnut',
            data: {
                labels: ['Con coordenadas', 'Sin coordenadas'],
                datasets: [{
                    data: [geo.conCoordenadas, geo.sinCoordenadas],
                    backgroundColor: ['#10b981', '#9ca3af'],
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
                labels: ['Comités vencidos', 'Auditorías > $500 mil', 'Rotación baja (<0.5)', 'Aud. pendiente (>3 meses)'],
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

@extends('layouts.app', ['pageTitle' => 'Dashboard'])

@section('title', 'Dashboard — CDT')

@section('content')
    @isset($error)
        <div class="bg-red-100 dark:bg-red-900/50 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6">{{ $error }}</div>
    @endisset

    {{-- Row 1 — 4 Metric KPIs --}}
    <div class="grid grid-cols-2 gap-3 lg:gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <div class="text-2xl lg:text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $totalCount }}</div>
            <div class="text-xs lg:text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500 mt-1">🏪 Total de tiendas</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <div class="text-2xl lg:text-3xl font-bold text-red-600">{{ $criticalSummary['rojo'] }}</div>
            <div class="text-xs lg:text-sm text-red-500 mt-1">⚠️ Tiendas críticas</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <div class="text-2xl lg:text-3xl font-bold text-gray-800 dark:text-gray-100">{{ $sinConectividad }}</div>
            <div class="text-xs lg:text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500 mt-1">📡 Sin conectividad</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <div class="text-2xl lg:text-3xl font-bold text-blue-600">{{ $aperturasEsteMes }}</div>
            <div class="text-xs lg:text-sm text-blue-500 mt-1">📅 Aperturas este mes</div>
        </div>
    </div>

    {{-- Row 2 — Auditoría KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 border-l-4 border-red-500">
            <div class="text-2xl lg:text-3xl font-bold text-red-600">{{ $auditoriaKpis['comitesVencidos'] }}</div>
            <div class="text-xs lg:text-sm text-red-500 mt-1">🏛️ Comités vencidos</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 border-l-4 border-orange-500">
            <div class="text-2xl lg:text-3xl font-bold text-orange-600">{{ $auditoriaKpis['auditoriaAlta'] }}</div>
            <div class="text-xs lg:text-sm text-orange-500 mt-1">🔍 Auditoría > $500k</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 border-l-4 border-amber-500">
            <div class="text-2xl lg:text-3xl font-bold text-amber-600">{{ $auditoriaKpis['rotacionBaja'] }}</div>
            <div class="text-xs lg:text-sm text-amber-500 mt-1">📉 Rotación baja (&lt;1.5)</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 border-l-4 border-gray-400">
            <div class="text-2xl lg:text-3xl font-bold text-gray-600 dark:text-gray-300">{{ $auditoriaKpis['auditoriaPendiente'] }}</div>
            <div class="text-xs lg:text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500 mt-1">📅 Aud. pendiente (&gt;3 meses)</div>
        </div>
    </div>

    {{-- Row 3 — Module Access Cards with inline mini charts --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-5 mb-6">
        {{-- Conectividad --}}
        <a href="{{ url('/conectividad') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-blue-500 group">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100 group-hover:text-blue-600 transition">📡 Conectividad</h3>
                <span class="text-xs text-blue-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if(!empty($connectivityKpis))
                <div class="flex items-center gap-3">
                    <div class="flex-1 grid grid-cols-3 gap-1.5 text-center">
                        @foreach(['TELEFONIA', 'Señal de celular', 'INTERNET'] as $key)
                            @php $k = $connectivityKpis[$key] ?? null; @endphp
                            @if($k)
                                <div class="p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $k['pctYes'] }}%</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ $k['icon'] }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="w-20 h-16 flex-shrink-0">
                        <canvas id="chart-connectivity"></canvas>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
            @endif
        </a>

        {{-- Información de Tiendas --}}
        <a href="{{ url('/informacion-tiendas') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-red-500 group">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100 group-hover:text-red-600 transition">⚠️ Info. Tiendas</h3>
                <span class="text-xs text-red-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($criticalSummary)
                <div class="flex items-center gap-3">
                    <div class="flex-1 grid grid-cols-3 gap-1.5 text-center">
                        <div class="p-1.5 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <div class="text-sm font-bold text-red-600">{{ $criticalSummary['rojo'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">🔴 Críticas</div>
                        </div>
                        <div class="p-1.5 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <div class="text-sm font-bold text-yellow-600">{{ $criticalSummary['amarillo'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">🟡 Monitoreo</div>
                        </div>
                        <div class="p-1.5 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="text-sm font-bold text-green-600">{{ $criticalSummary['verde'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">🟢 Normales</div>
                        </div>
                    </div>
                    <div class="w-20 h-16 flex-shrink-0">
                        <canvas id="chart-critical"></canvas>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
            @endif
        </a>

        {{-- Mapa --}}
        <a href="{{ url('/mapa') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-emerald-500 group">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100 group-hover:text-emerald-600 transition">🗺️ Mapa</h3>
                <span class="text-xs text-emerald-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($geoStats)
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-1.5 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                        <div class="text-sm font-bold text-emerald-600">{{ $geoStats['conCoordenadas'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">🟢 Geolocalizadas</div>
                    </div>
                    <div class="p-1.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="text-sm font-bold text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ $geoStats['sinCoordenadas'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">⚪ Sin coord.</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
            @endif
        </a>

        {{-- Aperturas --}}
        <a href="{{ url('/aperturas') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-purple-500 group">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100 group-hover:text-purple-600 transition">🏗️ Aperturas</h3>
                <span class="text-xs text-purple-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($aperturasKpi)
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-1.5 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <div class="text-sm font-bold text-purple-600">{{ $aperturasKpi['total'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Total</div>
                    </div>
                    <div class="p-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="text-sm font-bold text-blue-600">{{ $aperturasKpi['esteAnio'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Éste año</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
            @endif
        </a>

        {{-- Directorio --}}
        <a href="{{ url('/directorio') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-amber-500 group">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100 group-hover:text-amber-600 transition">📋 Directorio</h3>
                <span class="text-xs text-amber-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($directorioStats)
                <div class="flex items-center gap-3">
                    <div class="flex-1 grid grid-cols-2 gap-1.5 text-center">
                        <div class="p-1.5 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="text-sm font-bold text-green-600">{{ $directorioStats['completos'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">🟢 Completos</div>
                        </div>
                        <div class="p-1.5 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                            <div class="text-sm font-bold text-amber-600">{{ $directorioStats['incompletos'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">🟡 Incompletos</div>
                        </div>
                    </div>
                    <div class="w-20 h-16 flex-shrink-0">
                        <canvas id="chart-directorio"></canvas>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
            @endif
        </a>

        {{-- Auditoría --}}
        <a href="{{ url('/auditoria') }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5 hover:shadow-lg transition border-l-4 border-purple-500 group">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100 group-hover:text-purple-600 transition">🔍 Auditoría</h3>
                <span class="text-xs text-purple-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($auditoriaKpis)
                <div class="flex items-center gap-3">
                    <div class="flex-1 grid grid-cols-2 gap-1.5 text-center">
                        <div class="p-1.5 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <div class="text-sm font-bold text-red-600">{{ $auditoriaKpis['comitesVencidos'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">🔴 Comités venc.</div>
                        </div>
                        <div class="p-1.5 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                            <div class="text-sm font-bold text-orange-600">{{ $auditoriaKpis['auditoriaAlta'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">🔍 > $500k</div>
                        </div>
                    </div>
                    <div class="w-20 h-16 flex-shrink-0">
                        <canvas id="chart-auditoria"></canvas>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
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
        var directorio = @json($directorioStats);
        var auditoria = @json($auditoriaKpis);

        Chart.defaults.font.family = "'Instrument Sans', system-ui, sans-serif";

        var miniOpts = {
            responsive: true,
            maintainAspectRatio: true,
            animation: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
        };

        // 1. Critical stores — mini doughnut
        var critTotal = critical.rojo + critical.amarillo + critical.verde;
        if (critTotal > 0) {
            new Chart(document.getElementById('chart-critical'), {
                type: 'doughnut',
                data: {
                    labels: ['Críticas', 'Monitoreo', 'Normales'],
                    datasets: [{
                        data: [critical.rojo, critical.amarillo, critical.verde],
                        backgroundColor: ['#ef4444', '#eab308', '#22c55e'],
                        borderWidth: 0,
                    }],
                },
                options: { ...miniOpts, cutout: '65%' },
            });
        }

        // 2. Connectivity — mini stacked bar
        var connTotal = connKpis._total || total;
        var connLabels = { TELEFONIA: 'T', 'Señal de celular': 'C', INTERNET: 'I' };
        var connSi = [];
        var connNo = [];
        var connOrder = ['TELEFONIA', 'Señal de celular', 'INTERNET'];
        connOrder.forEach(function (key) {
            var k = connKpis[key] || { yes: 0 };
            connSi.push(k.yes);
            connNo.push(connTotal - k.yes);
        });

        new Chart(document.getElementById('chart-connectivity'), {
            type: 'bar',
            data: {
                labels: connOrder.map(function (k) { return connLabels[k]; }),
                datasets: [
                    { label: 'Sí', data: connSi, backgroundColor: '#22c55e', borderRadius: 2 },
                    { label: 'No', data: connNo, backgroundColor: '#fca5a5', borderRadius: 2 },
                ],
            },
            options: {
                ...miniOpts,
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 } } },
                    y: { stacked: true, beginAtZero: true, display: false },
                },
            },
        });

        // 3. Directorio — mini doughnut
        var dirTotal = directorio.completos + directorio.incompletos;
        if (dirTotal > 0) {
            new Chart(document.getElementById('chart-directorio'), {
                type: 'doughnut',
                data: {
                    labels: ['Completos', 'Incompletos'],
                    datasets: [{
                        data: [directorio.completos, directorio.incompletos],
                        backgroundColor: ['#22c55e', '#f59e0b'],
                        borderWidth: 0,
                    }],
                },
                options: { ...miniOpts, cutout: '65%' },
            });
        }

        // 4. Auditoría — mini horizontal bar
        new Chart(document.getElementById('chart-auditoria'), {
            type: 'bar',
            data: {
                labels: ['CV', '>500k', 'RB', 'AP'],
                datasets: [{
                    data: [auditoria.comitesVencidos, auditoria.auditoriaAlta, auditoria.rotacionBaja, auditoria.auditoriaPendiente],
                    backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#6b7280'],
                    borderRadius: 2,
                }],
            },
            options: {
                ...miniOpts,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, display: false },
                    y: { grid: { display: false }, ticks: { font: { size: 8 } } },
                },
            },
        });
    }
    initCharts();
});
</script>
@endpush

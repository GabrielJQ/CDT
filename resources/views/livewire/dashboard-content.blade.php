@php
    $criticalPct = $totalCount > 0 ? round(($criticalSummary['rojo'] ?? 0) / $totalCount * 100, 1) : 0;
    $sinConectividadPct = $totalCount > 0 ? round($sinConectividad / $totalCount * 100, 1) : 0;
    $aperturasPct = $totalCount > 0 ? round($aperturasEsteMes / $totalCount * 100, 1) : 0;
    $geoSinCoordenadas = $geoStats['SIN_COORDENADAS'] ?? ($geoStats['sinCoordenadas'] ?? 0);
    $geoFueraMexico = $geoStats['FUERA_MEXICO'] ?? 0;
    $geoIncidencias = $geoStats['incidencias'] ?? ($geoSinCoordenadas + $geoFueraMexico);
    $geoTotal = ($geoStats['OK'] ?? 0) + $geoSinCoordenadas + $geoFueraMexico + ($geoStats['FUERA_ESTADO'] ?? 0);
    $geoPct = $geoTotal > 0 ? round($geoIncidencias / $geoTotal * 100, 1) : 0;
@endphp

<div class="page-shell" wire:loading.class="opacity-70" wire:target="refresh">
    @isset($error)
        <x-alert type="error">{{ $error }}</x-alert>
    @endisset

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
            <p class="kpi-value text-red-600">{{ number_format($criticalSummary['rojo'] ?? 0) }}</p>
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
                <span class="status-pill {{ ($criticalSummary['rojo'] ?? 0) > 0 ? 'status-critical' : 'status-ok' }}">{{ ($criticalSummary['rojo'] ?? 0) > 0 ? 'Accion' : 'Estable' }}</span>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <a href="{{ url('/informacion-tiendas?nivel=rojo') }}" class="priority-item">
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Criticidad</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tiendas con acumulacion de factores operativos.</p>
                    </div>
                    <span class="status-pill status-critical">{{ number_format($criticalSummary['rojo'] ?? 0) }} · {{ $criticalPct }}%</span>
                </a>
                <a href="{{ url('/conectividad?telefono=no&senial=no&internet=no') }}" class="priority-item">
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Conectividad</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sucursales sin telefono, senal celular ni internet.</p>
                    </div>
                    <span class="status-pill status-warning">{{ number_format($sinConectividad) }} · {{ $sinConectividadPct }}%</span>
                </a>
                <a href="{{ url('/mapa?estado_geo=INCIDENCIAS') }}" class="priority-item">
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Georreferencia</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sin coordenadas o fuera de Mexico.</p>
                    </div>
                    <span class="status-pill {{ $geoIncidencias > 0 ? 'status-warning' : 'status-ok' }}">{{ number_format($geoIncidencias) }} · {{ $geoPct }}%</span>
                </a>
            </div>
        </div>

        <div class="priority-panel">
            <p class="eyebrow">Lectura rapida</p>
            <h2 class="text-lg font-extrabold text-gray-900 dark:text-gray-100">Resumen del filtro actual</h2>
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Tiendas en monitoreo preventivo</span>
                    <strong class="text-gray-900 dark:text-gray-100">{{ number_format($criticalSummary['amarillo'] ?? 0) }} <span class="text-xs font-semibold text-gray-400">({{ $totalCount > 0 ? round(($criticalSummary['amarillo'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></strong>
                </div>
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Tiendas normales</span>
                    <strong class="text-green-600">{{ number_format($criticalSummary['verde'] ?? 0) }} <span class="text-xs font-semibold text-gray-400">({{ $totalCount > 0 ? round(($criticalSummary['verde'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></strong>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Incidencias de georreferencia</span>
                    <strong class="text-amber-600">{{ number_format($geoIncidencias) }} <span class="text-xs font-semibold text-gray-400">({{ $geoPct }}%)</span></strong>
                </div>
            </div>
            @if ($updatedAt)
                <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">Ultima actualizacion: {{ $updatedAt }}</p>
            @endif
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
                @if (! empty($connectivityKpis))
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
                @if (($criticalSummary['rojo'] ?? 0) + ($criticalSummary['amarillo'] ?? 0) + ($criticalSummary['verde'] ?? 0) > 0)
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
                @php $hasGeoStats = ($geoStats['OK'] ?? 0) + ($geoStats['SIN_COORDENADAS'] ?? 0) + ($geoStats['FUERA_MEXICO'] ?? 0) + ($geoStats['FUERA_ESTADO'] ?? 0) > 0; @endphp
                @if ($hasGeoStats)
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
                @if (! empty($aperturasPorMes))
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
                @php $hasDirectorio = ($directorioStats['completos'] ?? 0) + ($directorioStats['incompletos'] ?? 0) > 0; @endphp
                @if ($hasDirectorio)
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
                @if (! empty($auditoriaKpis))
                    <canvas id="chart-auditoria" class="w-full max-h-52"></canvas>
                @else
                    <p class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">Sin datos</p>
                @endif
            </a>
        </div>
    </section>

    <script id="dashboard-data" type="application/json">{!! $chartDataJson !!}</script>
</div>

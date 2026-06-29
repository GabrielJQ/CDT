<?php

use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioGeo;
use App\Servicios\ServicioPostgresql;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

new class extends Component
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado', 'Nombre_UniOpe', 'Nombre_Regional',
        'Latitud', 'Longitud', 'Vta_Mes', 'Cap_Tot',
    ];

    private const SORTABLE_COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado',
    ];

    private const EXCLUDED_SORT_COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio',
    ];

    public string $almacen = '';

    public string $estado_geo = '';

    public string $tiendaSalud = '';

    public ?string $sort = null;

    public string $direction = 'asc';

    public int $page = 1;

    public int $perPage = 50;

    public bool $showCritical = true;

    protected $queryString = [
        'almacen' => ['except' => ''],
        'estado_geo' => ['except' => ''],
        'tiendaSalud' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    private function regionFilters(): array
    {
        return app(ServicioAlcanceUsuario::class)->filtroEfectivo(request());
    }

    private function filters(): array
    {
        return [
            'almacen' => $this->almacen,
            'estado_geo' => $this->estado_geo,
            'tienda_salud' => $this->tiendaSalud,
        ];
    }

    private function allStoresCached(): array
    {
        $regionFilters = $this->regionFilters();
        $cacheKey = 'mapa_all_'.md5(serialize($regionFilters).'_'.$this->tiendaSalud);

        return Cache::remember($cacheKey, 120, function () use ($regionFilters) {
            return app(ServicioPostgresql::class)->obtenerMapa(
                $regionFilters,
                ['tienda_salud' => $this->tiendaSalud],
                self::COLUMNS,
            );
        });
    }

    public function updated($property): void
    {
        if (in_array($property, ['almacen', 'estado_geo', 'tiendaSalud', 'perPage'], true)) {
            $this->page = 1;
        }

        if (in_array($property, ['almacen', 'estado_geo', 'tiendaSalud'], true)) {
            $this->dispatch('mapa-filters-updated',
                tienda_salud: $this->tiendaSalud,
                estado_geo: $this->estado_geo,
                almacen: $this->almacen,
            );
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true) || in_array($column, self::EXCLUDED_SORT_COLUMNS, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }

        $this->page = 1;
    }

    public function clearFilters(): void
    {
        $this->almacen = '';
        $this->estado_geo = '';
        $this->tiendaSalud = '';
        $this->sort = null;
        $this->direction = 'asc';
        $this->page = 1;
    }

    public function previousTablePage(int $totalPages): void
    {
        $this->page = max(1, min($this->page - 1, $totalPages));
    }

    public function nextTablePage(int $totalPages): void
    {
        $this->page = min($totalPages, $this->page + 1);
    }

    public function goToTablePage(int $page, int $totalPages): void
    {
        $this->page = max(1, min($page, $totalPages));
    }

    public function columnLabel(string $column): string
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'Estado' => 'Estado',
        ][$column] ?? $column;
    }

    public function sortArrow(string $column): string
    {
        if (in_array($column, self::EXCLUDED_SORT_COLUMNS, true)) {
            return '';
        }

        if ($this->sort !== $column) {
            return '↕';
        }

        return $this->direction === 'asc' ? '▲' : '▼';
    }

    public function renderCell(string $column, array $store): string
    {
        $val = $store[$column] ?? '';

        if ($column === 'Nombre_Almacen') {
            return $this->renderStoreName($val, ! empty($store['es_tienda_salud_bienestar']));
        }

        if ($column === 'No_Tienda_Actual') {
            return '<span class="font-mono text-gray-700 dark:text-gray-300 block text-center">' . ($val ?: '—') . '</span>';
        }

        if (in_array($column, ['Municipio', 'Estado'], true)) {
            return e($val ?: '—');
        }

        return e($val ?: '');
    }

    private function renderStoreName(string $name, bool $esTiendaSalud): string
    {
        $name = e($name ?: '—');
        if ($esTiendaSalud) {
            $dot = '<span class="inline-block w-3 h-3 rounded-full bg-purple-500 flex-shrink-0 ring-2 ring-purple-300 dark:ring-purple-700" title="Tienda de Salud"></span>';
            $badge = '<span class="text-[10px] font-semibold text-purple-700 dark:text-purple-300 bg-purple-100 dark:bg-purple-900/50 px-1.5 py-0.5 rounded leading-tight">Tienda de Salud</span>';

            return '<span class="inline-flex items-center gap-1.5 flex-wrap">'.$dot.'<strong class="text-gray-900 dark:text-gray-100">'.$name.'</strong>'.$badge.'</span>';
        }

        return '<strong class="text-gray-900 dark:text-gray-100">'.$name.'</strong>';
    }

    private function geoMismatchLabel(array $stores, array $regionFilter): string
    {
        if (! empty($regionFilter['uo'])) {
            $uoName = '';
            foreach ($stores as $store) {
                $value = trim((string) ($store['Nombre_UniOpe'] ?? ''));
                if ($value !== '') {
                    $uoName = $value;

                    break;
                }
            }

            return $uoName !== '' ? 'No corresponde a ' . $uoName : 'No corresponde a la UO filtrada';
        }

        if (! empty($regionFilter['region'])) {
            $regionName = '';
            foreach ($stores as $store) {
                $value = trim((string) ($store['Nombre_Regional'] ?? ''));
                if ($value !== '') {
                    $regionName = $value;

                    break;
                }
            }

            return $regionName !== '' ? 'No corresponde a ' . $regionName : 'No corresponde a la region filtrada';
        }

        return 'No corresponde al estado registrado';
    }

    public function computed(): array
    {
        $allStores = $this->allStoresCached();

        $totalCount = count($allStores);

        $geoLabels = ServicioGeo::GEO_LABELS;
        $regionFilters = $this->regionFilters();
        $geoLabels['FUERA_ESTADO']['label'] = $this->geoMismatchLabel($allStores, $regionFilters);

        $stats = app(ServicioGeo::class)->calcularStats($allStores);

        $postgres = app(ServicioPostgresql::class);

        $filteredCount = $totalCount;
        if ($this->almacen !== '') {
            $filteredCount = $postgres->contarMapaFiltrado($regionFilters, $this->filters());
        }

        $incidencias = $postgres->obtenerIncidenciasMapaPaginadas(
            $regionFilters,
            ['almacen' => $this->almacen, 'tienda_salud' => $this->tiendaSalud],
            self::COLUMNS,
            $this->sort,
            $this->direction,
            $this->page,
            $this->perPage,
        );

        $criticalesPage = $incidencias['items'];
        $totalCriticales = $incidencias['total'];
        $totalPages = max(1, (int) ceil($totalCriticales / $this->perPage));
        $this->page = min($this->page, $totalPages);
        $offset = ($this->page - 1) * $this->perPage;

        return [
            'stores' => $allStores,
            'criticales' => $criticalesPage,
            'criticalesTotal' => $totalCriticales,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'filteredCount' => $filteredCount,
            'stats' => $stats,
            'geoLabels' => $geoLabels,
            'geoMismatchLabel' => $geoLabels['FUERA_ESTADO']['label'],
            'updatedAt' => now()->toDateTimeString(),
            'from' => $totalCriticales > 0 ? $offset + 1 : 0,
            'to' => min($offset + $this->perPage, $totalCriticales),
        ];
    }
};
?>

@php
    $data = $this->computed();
    extract($data);
@endphp

<div class="page-shell" wire:loading.class="opacity-70" wire:target="almacen,estado_geo,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage">

    <section class="page-hero">
        <div class="page-hero-content">
            <div>
                <p class="eyebrow">Cobertura territorial</p>
                <h1 class="page-heading">Mapa de tiendas</h1>
                <p class="page-subheading">Ubica tiendas con coordenadas válidas y prioriza registros con problemas de georreferencia para mejorar el análisis territorial.</p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Total</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $totalCount }}</p>
        </div>
        @foreach(['OK' => 'border-green-500', 'SIN_COORDENADAS' => 'border-gray-400', 'FUERA_MEXICO' => 'border-red-500', 'FUERA_ESTADO' => 'border-orange-400'] as $status => $border)
            @php $g = $geoLabels[$status] ?? []; @endphp
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 {{ $border }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $g['icon'] ?? '' }} {{ $g['label'] ?? $status }}</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats[$status] ?? 0 }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats[$status] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
        @endforeach
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Almacén</label>
                <input type="text" wire:model.live.debounce.400ms="almacen" placeholder="Buscar..." class="input-filter">
            </div>
            <div class="min-w-[180px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estado geolocalización</label>
                <select wire:model.live="estado_geo" class="input-filter">
                    <option value="">Todos</option>
                    <option value="INCIDENCIAS">⚠️ Incidencias (sin coordenadas + fuera de México)</option>
                    @foreach($geoLabels as $key => $g)
                        <option value="{{ $key }}">{{ $g['icon'] }} {{ $g['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[160px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Tipo de tienda</label>
                <select wire:model.live="tiendaSalud" class="input-filter">
                    <option value="">Todas</option>
                    <option value="salud">Tiendas de Salud / Bienestar</option>
                    <option value="regular">Tiendas Bienestar</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Limpiar</button>
            </div>
        </div>
    </div>

    <x-export-button route="export.mapa" :params="['almacen' => 'almacen', 'estado_geo' => 'estado_geo', 'tienda_salud' => 'tiendaSalud']" class="mb-6" />

    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Mostrando <strong>{{ $filteredCount }}</strong> tiendas filtradas. El mapa carga los puntos visibles según la zona actual
        @if($filteredCount !== $totalCount)
            (filtradas de <strong>{{ $totalCount }}</strong>)
        @endif
        <span wire:loading class="ml-2 text-[#988256] font-semibold">Actualizando...</span>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_20rem]">
        <div class="institutional-card p-2">
            <div wire:ignore id="map"></div>
        </div>
        <aside class="priority-panel">
            <p class="eyebrow">Incidencias</p>
            <h2 class="text-lg font-extrabold text-gray-900 dark:text-gray-100">Calidad de coordenadas</h2>
            <div class="mt-4 space-y-3">
                <div class="priority-item">
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Sin coordenadas</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Registros que no pueden mostrarse en el mapa.</p>
                    </div>
                    <span class="status-pill {{ ($stats['SIN_COORDENADAS'] ?? 0) > 0 ? 'status-warning' : 'status-ok' }}">{{ $stats['SIN_COORDENADAS'] ?? 0 }} · {{ $totalCount > 0 ? round(($stats['SIN_COORDENADAS'] ?? 0) / $totalCount * 100, 1) : 0 }}%</span>
                </div>
                <div class="priority-item">
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Fuera de México</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Coordenadas fuera del rango geográfico esperado.</p>
                    </div>
                    <span class="status-pill {{ ($stats['FUERA_MEXICO'] ?? 0) > 0 ? 'status-critical' : 'status-ok' }}">{{ $stats['FUERA_MEXICO'] ?? 0 }} · {{ $totalCount > 0 ? round(($stats['FUERA_MEXICO'] ?? 0) / $totalCount * 100, 1) : 0 }}%</span>
                </div>
                <div class="priority-item">
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">{{ $geoMismatchLabel }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tiendas con posible error de captura territorial.</p>
                    </div>
                    <span class="status-pill {{ ($stats['FUERA_ESTADO'] ?? 0) > 0 ? 'status-warning' : 'status-ok' }}">{{ $stats['FUERA_ESTADO'] ?? 0 }} · {{ $totalCount > 0 ? round(($stats['FUERA_ESTADO'] ?? 0) / $totalCount * 100, 1) : 0 }}%</span>
                </div>
            </div>
        </aside>
    </div>

    @if(count($criticales) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">⚠️ Tiendas con incidencias de georreferencia</p>
                <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 font-semibold px-2.5 py-0.5 rounded-full">{{ $criticalesTotal }}</span>
            </div>
            <div x-data="{ page: @entangle('page') }" x-init="$watch('page', () => $nextTick(() => $el.scrollTop = 0))" class="max-h-[65vh] overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Almacén</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tienda #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Municipio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase cursor-pointer select-none hover:text-gray-800 dark:hover:text-gray-100" wire:click="sortBy('Estado')">Estado <span class="ml-1 text-[10px]">{{ $this->sortArrow('Estado') }}</span></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase hidden md:table-cell">Latitud</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase hidden md:table-cell">Longitud</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Problema</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($criticales as $store)
                        @php
                            $geo = $store['_geo'] ?? [];
                            $gLabel = $geoLabels[$geo['status'] ?? ''] ?? [];
                            $badgeClass = $geo['status'] === 'SIN_COORDENADAS' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : ($geo['status'] === 'FUERA_MEXICO' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300');
                        @endphp
                        <tr class="{{ ! empty($store['es_tienda_salud_bienestar']) ? 'bg-purple-50/80 dark:bg-purple-900/10' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">{!! $this->renderCell('Nombre_Almacen', $store) !!}</td>
                            <td class="px-4 py-3 text-center font-mono text-gray-700 dark:text-gray-300">{!! $this->renderCell('No_Tienda_Actual', $store) !!}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{!! $this->renderCell('Municipio', $store) !!}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{!! $this->renderCell('Estado', $store) !!}</td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-600 dark:text-gray-300 hidden md:table-cell">{{ $geo['lat'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-600 dark:text-gray-300 hidden md:table-cell">{{ $geo['lon'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="geo-badge {{ $badgeClass }}">
                                    {{ $gLabel['icon'] ?? '' }} {{ $geo['mensaje'] ?? $geo['status'] ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @if($totalPages > 1)
                <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700 text-sm">
                    <button type="button" wire:click="previousTablePage({{ $totalPages }})" @disabled($page <= 1) class="btn-secondary {{ $page <= 1 ? 'pointer-events-none opacity-40' : '' }}">Anterior</button>
                    <span class="text-gray-500 dark:text-gray-400">Página {{ $page }} de {{ $totalPages }}</span>
                    <button type="button" wire:click="nextTablePage({{ $totalPages }})" @disabled($page >= $totalPages) class="btn-secondary {{ $page >= $totalPages ? 'pointer-events-none opacity-40' : '' }}">Siguiente</button>
                </div>
            @endif
        </div>
    @elseif($filteredCount > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 text-center text-gray-500 dark:text-gray-400">
            ✅ Todas las tiendas filtradas tienen coordenadas válidas.
        </div>
    @endif

</div>

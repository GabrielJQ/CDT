<?php

use App\Servicios\ServicioFecha;
use App\Servicios\ServicioPostgresql;
use Livewire\Component;

new class extends Component
{
    private const COLUMNS = ['Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura'];

    private const SORTABLE_COLUMNS = ['Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura', '_fecha_apertura', '_antiguedad'];

    private const EXCLUDED_SORT_COLUMNS = ['Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio'];

    public string $almacen = '';

    public string $desde = '';

    public string $hasta = '';

    public ?string $sort = null;

    public string $direction = 'asc';

    public int $page = 1;

    public int $perPage = 50;

    public bool $showApertura = true;

    protected $queryString = [
        'almacen' => ['except' => ''],
        'desde' => ['except' => ''],
        'hasta' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    /**
     * @return array<string, string>
     */
    private function filters(): array
    {
        $fecha = app(ServicioFecha::class);

        return [
            'almacen' => trim($this->almacen),
            'desde' => $fecha->parsear($this->desde)?->toDateString() ?? '',
            'hasta' => $fecha->parsear($this->hasta)?->toDateString() ?? '',
        ];
    }

    /**
     * @return array{region: string, uo: string}
     */
    private function regionFilters(): array
    {
        return [
            'region' => request()->cookie('region_filter', ''),
            'uo' => request()->cookie('uo_filter', ''),
        ];
    }

    /**
     * @return array{column: string|null, direction: string}
     */
    private function sortInput(): array
    {
        $direction = $this->direction === 'desc' ? 'desc' : 'asc';

        if (! $this->sort || ! in_array($this->sort, self::SORTABLE_COLUMNS, true) || in_array($this->sort, self::EXCLUDED_SORT_COLUMNS, true)) {
            return ['column' => null, 'direction' => $direction];
        }

        return ['column' => $this->sort, 'direction' => $direction];
    }

    public function updated($property): void
    {
        if (in_array($property, ['almacen', 'desde', 'hasta', 'perPage'], true)) {
            $this->page = 1;
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
        $this->desde = '';
        $this->hasta = '';
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

    /**
     * @return array<int, string>
     */
    private function activeColumns(): array
    {
        $columns = ['Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio'];

        if ($this->showApertura) {
            $columns = array_merge($columns, ['_fecha_apertura', '_antiguedad']);
        }

        return $columns;
    }

    public function columnLabel(string $column): string
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'Localidad' => 'Localidad',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            '_fecha_apertura' => 'Apertura',
            '_antiguedad' => 'Antigüedad',
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

    public function formatDate(?string $date): string
    {
        if (! $date) {
            return '<span class="text-gray-400 dark:text-gray-500">—</span>';
        }

        $parts = explode('-', substr($date, 0, 10));
        if (count($parts) !== 3) {
            return e($date);
        }

        return '<span class="font-mono text-gray-700 dark:text-gray-300">'.$parts[2].'/'.$parts[1].'/'.$parts[0].'</span>';
    }

    public function ageBadge(?string $date): string
    {
        if (! $date) {
            return '<div class="text-center text-gray-400 dark:text-gray-500">—</div>';
        }

        $openedAt = \Carbon\Carbon::parse($date);
        $diffDays = (int) $openedAt->diffInDays(now(), false);
        $diffMonths = (int) floor($diffDays / 30);

        if ($diffDays <= 0) {
            $label = 'Hoy';
            $color = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        } elseif ($diffDays < 30) {
            $label = $diffDays.' día'.($diffDays > 1 ? 's' : '');
            $color = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        } elseif ($diffMonths < 12) {
            $label = $diffMonths.' mes'.($diffMonths > 1 ? 'es' : '');
            $color = $diffMonths <= 3 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
        } else {
            $years = (int) floor($diffMonths / 12);
            $label = $years.' año'.($years > 1 ? 's' : '');
            $color = 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200';
        }

        return '<div class="text-center"><span class="badge '.$color.'">'.$label.'</span></div>';
    }

    public function renderCell(string $column, array $store): string
    {
        if ($column === 'Nombre_Almacen') {
            return $this->renderStoreName($store[$column] ?? '', ! empty($store['es_tienda_salud_bienestar']));
        }

        if (in_array($column, ['Localidad', 'Municipio'], true)) {
            return e($store[$column] ?: '—');
        }

        if ($column === 'No_Tienda_Actual') {
            return '<span class="font-mono text-gray-700 dark:text-gray-300 text-center block">'.e($store[$column] ?: '—').'</span>';
        }

        if ($column === '_fecha_apertura') {
            return '<div class="text-center font-mono text-gray-700 dark:text-gray-300">'.$this->formatDate($store['_fecha_apertura'] ?? null).'</div>';
        }

        if ($column === '_antiguedad') {
            return $this->ageBadge($store['_fecha_apertura'] ?? null);
        }

        return e($store[$column] ?? '');
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

    public function exportUrl(): string
    {
        return url('/aperturas?'.http_build_query(array_filter([
            'almacen' => trim($this->almacen),
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
            'export' => 'csv',
        ], fn ($value) => $value !== null && $value !== '')));
    }

    /**
     * @return array<string, mixed>
     */
    public function tableData(): array
    {
        $postgres = app(ServicioPostgresql::class);
        $result = $postgres->obtenerAperturasPaginada(
            $this->regionFilters(),
            $this->filters(),
            $this->page,
            $this->perPage,
            self::COLUMNS,
            $this->sortInput(),
        );

        $totalPages = max(1, (int) ceil(($result['filtered'] ?? 0) / $this->perPage));
        $this->page = min($this->page, $totalPages);

        return [
            'stores' => $result['rows'],
            'kpis' => $result['kpis'],
            'totalCount' => $result['total'],
            'filteredCount' => $result['filtered'],
            'totalPages' => $totalPages,
            'from' => $result['filtered'] > 0 ? (($this->page - 1) * $this->perPage) + 1 : 0,
            'to' => min($this->page * $this->perPage, $result['filtered']),
            'columns' => $this->activeColumns(),
        ];
    }
};
?>

@php
    $tableData = $this->tableData();
    extract($tableData);
@endphp

<div class="page-shell" wire:loading.class="opacity-70" wire:target="almacen,desde,hasta,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showApertura">
    <div class="institutional-card mb-6 flex flex-col gap-4 border-l-4 border-[#988256] p-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[#988256]">Módulo operativo</p>
            <h3 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-gray-100">Apertura de Tiendas</h3>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Consulta fechas de apertura y antigüedad de tiendas. Los filtros, KPIs y paginación se actualizan sin recargar la página.</p>
        </div>
        <a href="{{ $this->exportUrl() }}" class="btn-export self-start lg:self-center" wire:navigate.hover="false">Exportar CSV</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Tiendas mostradas</p>
            <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($filteredCount) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">de {{ number_format($totalCount) }} totales</span></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Abiertas este mes</p>
            <p class="text-3xl font-bold text-green-600">{{ number_format($kpis['esteMes'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ ($kpis['total'] ?? 0) > 0 ? round($kpis['esteMes'] / $kpis['total'] * 100, 1) : 0 }}%)</span></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-amber-500">
            <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Abiertas este año</p>
            <p class="text-3xl font-bold text-amber-600">{{ number_format($kpis['esteAnio'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ ($kpis['total'] ?? 0) > 0 ? round($kpis['esteAnio'] / $kpis['total'] * 100, 1) : 0 }}%)</span></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-gray-400">
            <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">⚠️ Sin fecha de apertura</p>
            <p class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ number_format($kpis['sinFecha'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ ($kpis['total'] ?? 0) > 0 ? round($kpis['sinFecha'] / $kpis['total'] * 100, 1) : 0 }}%)</span></p>
        </div>
    </div>

    <div class="filter-panel">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Almacén</label>
                <input type="text" wire:model.live.debounce.400ms="almacen" placeholder="Buscar..." class="input-filter">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Desde</label>
                <input type="date" wire:model.live="desde" class="input-filter">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Hasta</label>
                <input type="date" wire:model.live="hasta" class="input-filter">
            </div>
            <button type="button" wire:click="clearFilters" class="btn-secondary">Limpiar</button>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold self-center">Columnas:</span>
        <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer dark:text-gray-200" data-group="General">
            <input type="checkbox" checked disabled class="opacity-50"> 📋 General
        </label>
        <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Apertura">
            <input type="checkbox" wire:model.live="showApertura"> 📅 Apertura
        </label>
    </div>

    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Mostrando <strong>{{ number_format($from) }}</strong>–<strong>{{ number_format($to) }}</strong> de <strong>{{ number_format($filteredCount) }}</strong> tiendas
        @if($filteredCount !== $totalCount)
            <span class="text-gray-400 dark:text-gray-500">(filtradas de {{ number_format($totalCount) }})</span>
        @endif
        <span wire:loading class="ml-2 text-[#988256] font-semibold">Actualizando...</span>
    </div>

    <div class="table-shell">
        <table id="aper-table" class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    @foreach($columns as $column)
                        @php
                            $sortable = ! in_array($column, ['Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio'], true);
                            $align = in_array($column, ['No_Tienda_Actual', '_fecha_apertura', '_antiguedad'], true) ? 'text-center' : 'text-left';
                        @endphp
                        <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800 {{ $align }} {{ $sortable ? 'cursor-pointer select-none hover:text-gray-800 dark:hover:text-gray-100' : '' }}" @if($sortable) wire:click="sortBy('{{ $column }}')" title="Ordenar columna" @endif>
                            {{ $this->columnLabel($column) }}
                            @if($sortable)
                                <span class="ml-1 text-[10px]">{{ $this->sortArrow($column) }}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($stores as $store)
                    @php
                        $openedAt = $store['_fecha_apertura'] ?? null;
                        $isRecent = $openedAt && \Carbon\Carbon::parse($openedAt)->gte(now()->subMonths(3));
                        $purpleBg = ! empty($store['es_tienda_salud_bienestar']) ? 'bg-purple-50/80 dark:bg-purple-900/10' : '';
                    @endphp
                    <tr class="{{ $purpleBg ?: ($isRecent ? 'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30') }}">
                        @foreach($columns as $column)
                            @php $align = in_array($column, ['No_Tienda_Actual', '_fecha_apertura', '_antiguedad'], true) ? 'text-center' : 'text-left'; @endphp
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 {{ $align }}">{!! $this->renderCell($column, $store) !!}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">No se encontraron tiendas</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-4">
        <button type="button" wire:click="previousTablePage({{ $totalPages }})" @disabled($page <= 1) class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 disabled:opacity-30 disabled:cursor-not-allowed transition">← Anterior</button>
        <div class="flex gap-1">
            @php
                $startPage = max(1, $page - 3);
                $endPage = min($totalPages, $page + 3);
            @endphp
            @if($startPage > 1)
                <button type="button" wire:click="goToTablePage(1, {{ $totalPages }})" class="page-btn px-2.5 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition hover:bg-gray-100 dark:hover:bg-gray-700/30 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">1</button>
                @if($startPage > 2)
                    <span class="text-gray-400 dark:text-gray-500 px-1 self-end">...</span>
                @endif
            @endif
            @for($tablePage = $startPage; $tablePage <= $endPage; $tablePage++)
                <button type="button" wire:click="goToTablePage({{ $tablePage }}, {{ $totalPages }})" class="page-btn px-2.5 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition hover:bg-gray-100 dark:hover:bg-gray-700/30 {{ $tablePage === $page ? 'active' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}">{{ $tablePage }}</button>
            @endfor
            @if($endPage < $totalPages)
                @if($endPage < $totalPages - 1)
                    <span class="text-gray-400 dark:text-gray-500 px-1 self-end">...</span>
                @endif
                <button type="button" wire:click="goToTablePage({{ $totalPages }}, {{ $totalPages }})" class="page-btn px-2.5 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition hover:bg-gray-100 dark:hover:bg-gray-700/30 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">{{ $totalPages }}</button>
            @endif
        </div>
        <button type="button" wire:click="nextTablePage({{ $totalPages }})" @disabled($page >= $totalPages) class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 disabled:opacity-30 disabled:cursor-not-allowed transition">Siguiente →</button>
    </div>
</div>

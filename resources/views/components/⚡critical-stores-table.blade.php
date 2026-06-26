<?php

use App\Livewire\ConTablaLivewire;
use App\Presenters\IndicadorPresenter;
use App\Presenters\RenderTiendaPresentador;
use App\Servicios\ServicioPostgresql;
use Livewire\Component;

new class extends Component
{
    use ConTablaLivewire;

    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio',
    ];

    private const DB_COLUMNS = [
        'Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Cap_Tot', 'Cap_Dic', 'Vigencia',
        'Imp_Res_Audi_Mes', 'Pagare_Fecha', 'Vta_Mes', 'Asam_Prog_Mes', 'Asam_Real_Mes',
    ];

    private const SORTABLE_COLUMNS = [
        'Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Factores', 'Detalle',
    ];

    public string $almacen = '';

    public string $nivel = '';

    public string $indicador = '';

    public string $tiendaSalud = '';

    public bool $showFactores = true;

    public bool $showDetalle = true;

    protected $queryString = [
        'almacen' => ['except' => ''],
        'nivel' => ['except' => ''],
        'indicador' => ['except' => ''],
        'tiendaSalud' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return self::SORTABLE_COLUMNS;
    }

    /** @return array<string, string> */
    private function filters(): array
    {
        return [
            'almacen' => trim($this->almacen),
            'nivel' => $this->nivel,
            'indicador' => $this->indicador,
            'tienda_salud' => $this->tiendaSalud,
        ];
    }

    /** @return list<string> */
    protected function filterProperties(): array
    {
        return ['almacen', 'nivel', 'indicador', 'tiendaSalud'];
    }

    protected function clearFilterValues(): void
    {
        $this->almacen = '';
        $this->nivel = '';
        $this->indicador = '';
        $this->tiendaSalud = '';
    }

    /** @return array<int, string> */
    private function activeColumns(): array
    {
        $columns = ['Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio'];

        if ($this->showFactores) {
            $columns[] = 'Factores';
        }

        if ($this->showDetalle) {
            $columns[] = 'Detalle';
        }

        return $columns;
    }

    public function columnLabel(string $column): string
    {
        return [
            'Estado' => 'Estado',
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'Factores' => 'Factores',
            'Detalle' => 'Detalle',
        ][$column] ?? $column;
    }

    public function renderCell(string $column, array $store): string
    {
        $e = $store['_critico'] ?? [];

        if ($column === 'Estado') {
            $level = $e['level'] ?? 'verde';
            $count = $e['count'] ?? 0;
            $badge = IndicadorPresenter::levelBadge($level, $count);

            return '<span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold '.$badge['classes'].'">'.$badge['label'].'</span>';
        }

        if ($column === 'Nombre_Almacen') {
            return RenderTiendaPresentador::renderStoreName($store[$column] ?? '', ! empty($store['es_tienda_salud_bienestar']));
        }

        if ($column === 'No_Tienda_Actual') {
            $val = $store[$column] ?? '';

            return '<span class="font-mono text-gray-700 dark:text-gray-300 block text-center">'.($val ? number_format((float) $val) : '—').'</span>';
        }

        if ($column === 'Municipio') {
            return e($store[$column] ?: '—');
        }

        if ($column === 'Factores') {
            return implode(' ', array_map(function (string $key) use ($e): string {
                $active = ! empty($e['conditions'][$key]);
                $rawLabel = $e['labels'][$key]['label'] ?? IndicadorPresenter::factorLabel($key);
                $cleanLabel = IndicadorPresenter::cleanLabel($rawLabel);
                $title = $active ? '🔴 '.$cleanLabel : '⚪ '.$cleanLabel;

                if ($active) {
                    return '<span class="text-base cursor-help" title="'.e($title).'">🔴</span>';
                }

                return '<span class="text-base text-gray-300 cursor-help" title="'.e($title).'">⚪</span>';
            }, IndicadorPresenter::factorKeys()));
        }

        if ($column === 'Detalle') {
            if (empty($e['conditions']) || empty($e['labels'])) {
                return '<span class="text-gray-400 dark:text-gray-500 text-xs">Sin incidencias</span>';
            }

            $activeKeys = array_values(array_filter(IndicadorPresenter::factorKeys(), fn (string $k): bool => ! empty($e['conditions'][$k])));

            if (empty($activeKeys)) {
                return '<span class="text-gray-400 dark:text-gray-500 text-xs">Sin incidencias</span>';
            }

            $chips = array_map(function (string $k) use ($e): string {
                $info = $e['labels'][$k] ?? [];
                $style = IndicadorPresenter::factorStyle($k);
                $label = IndicadorPresenter::cleanLabel($info['label'] ?? $k);
                $detail = $info['detail'] ?? '';

                $html = '<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-lg border '.$style[0].'">';
                $html .= $style[1].' '.e($label);

                if ($detail !== '') {
                    $html .= '<span class="font-normal opacity-70 ml-0.5">'.e($detail).'</span>';
                }

                $html .= '</span>';

                return $html;
            }, $activeKeys);

            return '<div class="flex flex-wrap gap-1.5 max-w-md">'.implode('', $chips).'</div>';
        }

        return e($store[$column] ?? '');
    }

    public function isSortable(string $column): bool
    {
        return in_array($column, $this->sortableColumns(), true) && ! in_array($column, $this->excludedSortColumns(), true);
    }

    public function exportUrl(): string
    {
        return url('/informacion-tiendas?'.http_build_query(array_filter([
            'almacen' => trim($this->almacen),
            'nivel' => $this->nivel,
            'indicador' => $this->indicador,
            'tienda_salud' => $this->tiendaSalud,
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
        $result = $postgres->obtenerCriticidadPaginada(
            $this->regionFilters(),
            $this->filters(),
            $this->page,
            $this->perPage,
            self::DB_COLUMNS,
            $this->sortInput(),
        );

        $totalPages = max(1, (int) ceil(($result['filtered'] ?? 0) / $this->perPage));
        $this->page = min($this->page, $totalPages);

        return [
            'stores' => $result['rows'],
            'summary' => $result['summary'],
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

<div class="page-shell" wire:loading.class="opacity-70" wire:target="almacen,nivel,indicador,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showFactores,showDetalle">
    <div class="institutional-card mb-6 flex flex-col gap-4 border-l-4 border-[#988256] p-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[#988256]">Módulo operativo</p>
            <h3 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-gray-100">Información de Tiendas</h3>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Consulta el nivel de criticidad de las tiendas basado en factores como capital, comités, auditoría, pagarés, rotación y asambleas. Al usar los filtros se actualiza la tabla automáticamente.</p>
        </div>
        <a href="{{ $this->exportUrl() }}" class="btn-export self-start lg:self-center" wire:navigate.hover="false">Exportar CSV</a>
    </div>

    @if(!empty($summary))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-red-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🔴 Críticas</p>
                <p class="text-3xl font-bold text-red-600">{{ number_format($summary['rojo']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($summary['rojo'] / $totalCount * 100) : 0 }}%)</span></p>
            </div>
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-yellow-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🟡 Monitoreo</p>
                <p class="text-3xl font-bold text-yellow-600">{{ number_format($summary['amarillo']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($summary['amarillo'] / $totalCount * 100) : 0 }}%)</span></p>
            </div>
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-green-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🟢 Normales</p>
                <p class="text-3xl font-bold text-green-600">{{ number_format($summary['verde']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($summary['verde'] / $totalCount * 100) : 0 }}%)</span></p>
            </div>
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Total tiendas</p>
                <p class="text-3xl font-bold text-blue-600">{{ number_format($totalCount) }}</p>
                <p class="text-sm font-normal text-gray-400 dark:text-gray-500">{{ $filteredCount !== $totalCount ? 'Filtradas: '.number_format($filteredCount) : 'Sin filtros' }}</p>
            </div>
        </div>
    @endif

    @if(!empty($summary['desgloseLabels']))
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 mb-6">
            <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">📊 Factores más recurrentes</p>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($summary['desgloseLabels'] as $factor)
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $factor['count'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $factor['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="filter-panel">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Almacén</label>
                <input type="text" wire:model.live.debounce.400ms="almacen" placeholder="Buscar..." class="input-filter">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Nivel</label>
                <select wire:model.live="nivel" class="input-filter">
                    <option value="">Todos</option>
                    <option value="rojo">🔴 Crítico</option>
                    <option value="amarillo">🟡 Monitoreo</option>
                    <option value="verde">🟢 Normal</option>
                </select>
            </div>
            <div class="min-w-[190px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Indicador</label>
                <select wire:model.live="indicador" class="input-filter">
                    <option value="">Todos</option>
                    @foreach(IndicadorPresenter::factorLabels() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
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

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold self-center">Columnas:</span>
        <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer dark:text-gray-200" data-group="General">
            <input type="checkbox" checked disabled class="opacity-50"> 📋 General
        </label>
        <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Factores">
            <input type="checkbox" wire:model.live="showFactores"> 🔴 Factores
        </label>
        <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Detalle">
            <input type="checkbox" wire:model.live="showDetalle"> 📝 Detalle
        </label>
    </div>

    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Mostrando <strong>{{ number_format($from) }}</strong>–<strong>{{ number_format($to) }}</strong> de <strong>{{ number_format($filteredCount) }}</strong> tiendas
        @if($filteredCount !== $totalCount)
            <span class="text-gray-400 dark:text-gray-500">(filtradas de {{ number_format($totalCount) }})</span>
        @endif
        <span wire:loading class="ml-2 text-[#988256] font-semibold">Actualizando...</span>
    </div>

    <div x-data="{ page: @entangle('page') }" x-init="$watch('page', () => $nextTick(() => $el.scrollTop = 0))" class="max-h-[65vh] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200" style="table-layout:auto">
            <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                <tr>
                    @foreach($columns as $column)
                        @php
                            $sortable = $this->isSortable($column);
                            $align = in_array($column, ['Estado', 'No_Tienda_Actual', 'Factores', 'Detalle'], true) ? 'text-center' : 'text-left';
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
                        $level = $store['_critico']['level'] ?? 'verde';
                        $rowBg = match ($level) {
                            'rojo' => ' bg-red-50 dark:bg-red-900/20',
                            'amarillo' => ' bg-amber-50 dark:bg-amber-900/20',
                            default => '',
                        };
                        $purpleBg = ! empty($store['es_tienda_salud_bienestar']) ? ' bg-purple-50/80 dark:bg-purple-900/10' : '';
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30{{ $rowBg }}{{ $purpleBg }}">
                        @foreach($columns as $column)
                            @php
                                $align = in_array($column, ['Estado', 'No_Tienda_Actual', 'Factores', 'Detalle'], true) ? 'text-center' : 'text-left';
                            @endphp
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 {{ $align }}">{!! $this->renderCell($column, $store) !!}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No se encontraron tiendas</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-4">
        <button type="button" wire:click="previousTablePage({{ $totalPages }})" @disabled($page <= 1) class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 disabled:opacity-30 disabled:cursor-not-allowed transition">
            ← Anterior
        </button>
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
        <button type="button" wire:click="nextTablePage({{ $totalPages }})" @disabled($page >= $totalPages) class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/30 disabled:opacity-30 disabled:cursor-not-allowed transition">
            Siguiente →
        </button>
    </div>
</div>

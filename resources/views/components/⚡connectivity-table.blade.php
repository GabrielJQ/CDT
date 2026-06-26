<?php

use App\Livewire\ConTablaLivewire;
use App\Servicios\ServicioPostgresql;
use Livewire\Component;

new class extends Component
{
    use ConTablaLivewire;

    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
    ];

    public string $almacen = '';

    public string $telefono = '';

    public string $senial = '';

    public string $compania = '';

    public string $internet = '';

    public string $tiendaSalud = '';

    public bool $showConnectivity = true;

    protected $queryString = [
        'almacen' => ['except' => ''],
        'telefono' => ['except' => ''],
        'senial' => ['except' => ''],
        'compania' => ['except' => ''],
        'internet' => ['except' => ''],
        'tiendaSalud' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return self::COLUMNS;
    }

    /** @return array<string, string> */
    private function filters(): array
    {
        return [
            'almacen' => trim($this->almacen),
            'telefono' => $this->telefono,
            'senial' => $this->senial,
            'compania' => $this->compania,
            'internet' => $this->internet,
            'tienda_salud' => $this->tiendaSalud,
        ];
    }

    /** @return list<string> */
    protected function filterProperties(): array
    {
        return ['almacen', 'telefono', 'senial', 'compania', 'internet', 'tiendaSalud'];
    }

    protected function clearFilterValues(): void
    {
        $this->almacen = '';
        $this->telefono = '';
        $this->senial = '';
        $this->compania = '';
        $this->internet = '';
        $this->tiendaSalud = '';
    }

    /** @return array<int, string> */
    private function activeColumns(): array
    {
        $columns = ['Nombre_Almacen', 'No_Tienda_Actual', 'Municipio'];

        if ($this->showConnectivity) {
            $columns = array_merge($columns, ['TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET']);
        }

        return $columns;
    }

    public function columnLabel(string $column): string
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'TELEFONIA' => '📞 Teléfono fijo',
            'Señal de celular' => '📱 Señal Celular',
            'Compañía' => 'Compañía',
            'INTERNET' => '🌐 Internet',
        ][$column] ?? $column;
    }

    private function yesNoBadge(?string $value): string
    {
        $normalized = strtoupper(trim($value ?? ''));

        return match ($normalized) {
            'S' => '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Sí</span>',
            'N' => '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">No</span>',
            default => '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300">—</span>',
        };
    }

    public function renderCell(string $column, array $store): string
    {
        if ($column === 'Nombre_Almacen') {
            return $this->renderStoreName($store[$column] ?? '', ! empty($store['es_tienda_salud_bienestar']));
        }

        if ($column === 'No_Tienda_Actual') {
            $number = $store[$column] ?? '';

            return '<span class="font-mono text-gray-700 dark:text-gray-300">'.($number ? number_format((float) $number) : '—').'</span>';
        }

        if ($column === 'Municipio') {
            return e($store[$column] ?: '—');
        }

        if (in_array($column, ['TELEFONIA', 'Señal de celular', 'INTERNET'], true)) {
            return '<div class="text-center">'.$this->yesNoBadge($store[$column] ?? '').'</div>';
        }

        if ($column === 'Compañía') {
            $company = trim($store[$column] ?? '');

            return '<span class="text-gray-700 dark:text-gray-300">'.e($company ?: '—').'</span>';
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
        return $this->buildExportUrl('/conectividad', [
            'almacen' => trim($this->almacen),
            'telefono' => $this->telefono,
            'senial' => $this->senial,
            'compania' => $this->compania,
            'internet' => $this->internet,
            'tienda_salud' => $this->tiendaSalud,
        ]);
    }

    /** @return array<string, mixed> */
    public function tableData(): array
    {
        $postgres = app(ServicioPostgresql::class);
        $result = $postgres->obtenerConectividadPaginada(
            $this->regionFilters(),
            $this->filters(),
            $this->page,
            $this->perPage,
            $this->sortInput(),
        );

        $totalPages = max(1, (int) ceil(($result['filtered'] ?? 0) / $this->perPage));
        $this->page = min($this->page, $totalPages);

        return [
            'stores' => $result['rows'],
            'kpis' => $result['kpis'],
            'companias' => $result['companias'],
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

<div class="page-shell" wire:loading.class="opacity-70" wire:target="almacen,telefono,senial,compania,internet,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showConnectivity">
    <div class="institutional-card mb-6 flex flex-col gap-4 border-l-4 border-[#988256] p-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[#988256]">Módulo operativo</p>
            <h3 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-gray-100">Conectividad</h3>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Consulta disponibilidad de telefonía fija, señal celular, compañía e internet por tienda. Al usar los filtros se actualiza la tabla automáticamente.</p>
        </div>
        <a href="{{ $this->exportUrl() }}" class="btn-export self-start lg:self-center" wire:navigate.hover="false">Exportar CSV</a>
    </div>

    @if(!empty($kpis))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Tiendas mostradas</p>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($filteredCount) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">de {{ number_format($totalCount) }} totales</span></p>
            </div>
            @foreach(['TELEFONIA', 'Señal de celular', 'INTERNET'] as $key)
                @php $k = $kpis[$key] ?? null; @endphp
                @if($k)
                    @php $barYes = $kpis['_total'] > 0 ? round($k['yes'] / $kpis['_total'] * 100) : 0; @endphp
                    <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-green-500">
                        <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $k['icon'] }} {{ $k['label'] }}</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($k['yes']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">/ {{ number_format($kpis['_total']) }} ({{ $barYes }}%)</span></p>
                        <div class="mt-2 flex gap-4 text-xs">
                            <span class="text-green-600 font-semibold">Sí: {{ number_format($k['yes']) }}</span>
                            <span class="text-red-500 font-semibold">No: {{ number_format($k['no']) }}</span>
                            @if($k['undef'] > 0)
                                <span class="text-gray-400 dark:text-gray-500 font-semibold">—: {{ number_format($k['undef']) }}</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if(!empty($kpis['_compania']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 mb-6">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">📡 Distribución por Compañía (tiendas con señal celular)</p>
                <div class="flex flex-wrap gap-6">
                    @foreach($kpis['_compania'] as $comp => $info)
                        <div class="flex-1 min-w-[120px]">
                            <div class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $comp }}</div>
                            <div class="text-2xl font-bold text-blue-600">{{ $info['pct'] }}%</div>
                            <div class="text-xs text-gray-400 dark:text-gray-500">{{ number_format($info['count']) }} tiendas</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    <div class="filter-panel">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Almacén</label>
                <input type="text" wire:model.live.debounce.400ms="almacen" placeholder="Buscar..." class="input-filter">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">📞 Teléfono fijo</label>
                <select wire:model.live="telefono" class="input-filter">
                    <option value="">Todos</option>
                    <option value="si">Sí</option>
                    <option value="no">No</option>
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">📱 Señal Celular</label>
                <select wire:model.live="senial" class="input-filter">
                    <option value="">Todos</option>
                    <option value="si">Sí</option>
                    <option value="no">No</option>
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Compañía</label>
                <select wire:model.live="compania" class="input-filter">
                    <option value="">Todas</option>
                    @foreach($companias as $company)
                        <option value="{{ $company }}">{{ $company }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">🌐 Internet</label>
                <select wire:model.live="internet" class="input-filter">
                    <option value="">Todos</option>
                    <option value="si">Sí</option>
                    <option value="no">No</option>
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
        <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Conectividad">
            <input type="checkbox" wire:model.live="showConnectivity"> 📡 Conectividad
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
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200">
            <thead class="sticky top-0 z-10">
                <tr>
                    @foreach($columns as $column)
                        @php
                            $sortable = ! in_array($column, ['Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio'], true);
                            $align = in_array($column, ['No_Tienda_Actual', 'TELEFONIA', 'Señal de celular', 'INTERNET'], true) ? 'text-center' : 'text-left';
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
                    <tr class="{{ ! empty($store['es_tienda_salud_bienestar']) ? 'bg-purple-50/80 dark:bg-purple-900/10' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        @foreach($columns as $column)
                            @php $align = in_array($column, ['No_Tienda_Actual', 'TELEFONIA', 'Señal de celular', 'INTERNET'], true) ? 'text-center' : 'text-left'; @endphp
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

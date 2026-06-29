<?php

use App\Servicios\ServicioAlcanceUsuario;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $estado = '';

    public string $uo = '';

    public string $estatus = '';

    public string $buscar = '';

    public ?string $sort = null;

    public string $direction = 'asc';

    public int $page = 1;

    public int $perPage = 50;

    protected $queryString = [
        'estado' => ['except' => ''],
        'uo' => ['except' => ''],
        'estatus' => ['except' => ''],
        'buscar' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    private const SORTABLE_COLUMNS = ['no_tienda', 'almacen', 'municipio', 'estado', 'unidad_operativa', 'encargado', 'tipo_anaquel', 'estatus'];

    private const EXCLUDED_SORT = ['no_tienda', 'almacen', 'municipio'];

    private const DISPLAY_COLUMNS = ['no_tienda', 'almacen', 'municipio', 'estado', 'unidad_operativa', 'encargado', 'tipo_anaquel', 'estatus'];

    public array $estados = [];

    public array $unidadesOperativas = [];

    public array $estatusList = [];

    public int $totalCount = 0;

    private function uoFilter(): array
    {
        $filtro = app(ServicioAlcanceUsuario::class)->filtroEfectivo(request());
        $region = $filtro['region'];
        $uo = $filtro['uo'];

        if ($region === '__NO_ACCESS__' || $uo === '__NO_ACCESS__') {
            return ['__NO_ACCESS__'];
        }

        if (empty($region) && empty($uo)) {
            return [];
        }

        $conn = DB::connection(config('database.imports'));

        $query = $conn->table('tiendas_casa_x_casa')
            ->join('tiendas', function ($join) use ($conn) {
                $join->on('tiendas_casa_x_casa.no_tienda', '=', $conn->raw('"tiendas"."No_Tienda_Actual"'))
                    ->on('tiendas_casa_x_casa.almacen', '=', $conn->raw('"tiendas"."Nombre_Almacen"'))
                    ->on('tiendas_casa_x_casa.estado', '=', $conn->raw('"tiendas"."Estado"'))
                    ->on('tiendas_casa_x_casa.municipio', '=', $conn->raw('"tiendas"."Municipio"'));
            })
            ->where('tiendas.es_activo', true)
            ->where('tiendas_casa_x_casa.es_activo', true);

        if (! empty($uo)) {
            $query->where('tiendas.Clave_UniOpe', $uo);
            if (! empty($region)) {
                $query->where('tiendas.Clave_Regional', $region);
            }
        } else {
            $query->where('tiendas.Clave_Regional', $region);
        }

        return $query->distinct()->pluck('tiendas_casa_x_casa.unidad_operativa')->toArray();
    }

    private function baseQuery()
    {
        $conn = DB::connection(config('database.imports'));
        $query = $conn->table('tiendas_casa_x_casa')->where('es_activo', true);

        $uoFilter = $this->uoFilter();
        if (! empty($uoFilter)) {
            $query->whereIn('unidad_operativa', $uoFilter);
        }

        if ($this->estado !== '') {
            $query->where('estado', $this->estado);
        }
        if ($this->uo !== '') {
            $query->where('unidad_operativa', $this->uo);
        }
        if ($this->estatus !== '') {
            $query->where('estatus', $this->estatus);
        }
        if ($this->buscar !== '') {
            $buscar = $this->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('almacen', 'ILIKE', "%{$buscar}%")
                    ->orWhere('no_tienda', 'ILIKE', "%{$buscar}%")
                    ->orWhere('municipio', 'ILIKE', "%{$buscar}%")
                    ->orWhere('encargado', 'ILIKE', "%{$buscar}%");
            });
        }

        return $query;
    }

    private function sortInput(): array
    {
        $direction = $this->direction === 'desc' ? 'desc' : 'asc';

        if (! $this->sort || ! in_array($this->sort, self::SORTABLE_COLUMNS, true) || in_array($this->sort, self::EXCLUDED_SORT, true)) {
            return ['column' => null, 'direction' => $direction];
        }

        return ['column' => $this->sort, 'direction' => $direction];
    }

    public function mount(): void
    {
        $conn = DB::connection(config('database.imports'));
        $base = $conn->table('tiendas_casa_x_casa')->where('es_activo', true);
        $uoFilter = $this->uoFilter();
        if (! empty($uoFilter)) {
            $base->whereIn('unidad_operativa', $uoFilter);
        }

        $this->estados = (clone $base)->select('estado')->distinct()->orderBy('estado')->pluck('estado')->toArray();
        $this->unidadesOperativas = (clone $base)->select('unidad_operativa')->distinct()->orderBy('unidad_operativa')->pluck('unidad_operativa')->toArray();
        $this->estatusList = (clone $base)->whereNotNull('estatus')->select('estatus')->distinct()->orderBy('estatus')->pluck('estatus')->toArray();
    }

    public function updated($property): void
    {
        if (in_array($property, ['estado', 'uo', 'estatus', 'buscar', 'perPage'], true)) {
            $this->page = 1;
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true) || in_array($column, self::EXCLUDED_SORT, true)) {
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
        $this->estado = '';
        $this->uo = '';
        $this->estatus = '';
        $this->buscar = '';
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
            'no_tienda' => 'Tienda',
            'almacen' => 'Almacén',
            'municipio' => 'Municipio',
            'estado' => 'Estado',
            'unidad_operativa' => 'U. Operativa',
            'encargado' => 'Encargado',
            'tipo_anaquel' => 'Anaquel',
            'estatus' => 'Estatus',
        ][$column] ?? $column;
    }

    public function sortArrow(string $column): string
    {
        if (in_array($column, self::EXCLUDED_SORT, true)) {
            return '';
        }

        if ($this->sort !== $column) {
            return '↕';
        }

        return $this->direction === 'asc' ? '▲' : '▼';
    }

    public function tableData(): array
    {
        $query = $this->baseQuery();
        $this->totalCount = (clone $query)->count();

        $sort = $this->sortInput();
        if ($sort['column'] !== null) {
            $query->orderBy($sort['column'], $sort['direction'])->orderBy('id');
        } else {
            $query->orderBy('estado')->orderBy('municipio');
        }

        $totalPages = max(1, (int) ceil($this->totalCount / $this->perPage));
        $this->page = min($this->page, $totalPages);

        $stores = (clone $query)
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get()
            ->map(fn ($item) => (array) $item)
            ->all();

        return [
            'stores' => $stores,
            'totalCount' => $this->totalCount,
            'totalPages' => $totalPages,
            'from' => $this->totalCount > 0 ? (($this->page - 1) * $this->perPage) + 1 : 0,
            'to' => min($this->page * $this->perPage, $this->totalCount),
            'columns' => self::DISPLAY_COLUMNS,
        ];
    }
};
?>

@php
    $data = $this->tableData();
    extract($data);
@endphp

<div wire:loading.class="opacity-70" wire:target="estado,uo,estatus,buscar,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base lg:text-lg font-bold text-gray-800 dark:text-gray-100">Directorio Tiendas Salud Casa por Casa</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Consulta y filtra las tiendas del programa Casa por Casa</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estado</label>
                <select wire:model.live="estado" class="input-filter">
                    <option value="">Todos</option>
                    @foreach($estados as $e)
                        <option value="{{ $e }}">{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Unidad Operativa</label>
                <select wire:model.live="uo" class="input-filter">
                    <option value="">Todas</option>
                    @foreach($unidadesOperativas as $u)
                        <option value="{{ $u }}">{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estatus</label>
                <select wire:model.live="estatus" class="input-filter">
                    <option value="">Todos</option>
                    @foreach($estatusList as $s)
                        <option value="{{ $s }}">{{ $s ?: 'Sin estatus' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Buscar</label>
                <input type="text" wire:model.live.debounce.400ms="buscar" placeholder="Almacén, tienda, municipio..." class="input-filter">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-4">
            <x-export-button route="export.casa-x-casa-directorio" :params="['estado' => 'estado', 'uo' => 'uo', 'estatus' => 'estatus', 'buscar' => 'buscar']" />
            <button type="button" wire:click="clearFilters" class="btn-secondary">Limpiar filtros</button>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Mostrando <strong>{{ number_format($from) }}</strong>–<strong>{{ number_format($to) }}</strong> de <strong>{{ number_format($totalCount) }}</strong> tiendas
                <span wire:loading class="ml-2 text-[#988256] font-semibold">Actualizando...</span>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left table-institutional">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 text-xs uppercase">
                        @foreach($columns as $column)
                            @php
                                $isSortable = in_array($column, ['estado', 'unidad_operativa', 'encargado', 'tipo_anaquel', 'estatus']);
                            @endphp
                            <th class="py-2 pr-3 {{ $isSortable ? 'cursor-pointer select-none hover:text-gray-800 dark:hover:text-gray-100' : '' }}" @if($isSortable) wire:click="sortBy('{{ $column }}')" title="Ordenar columna" @endif>
                                <span class="inline-flex items-center gap-1">
                                    {{ $this->columnLabel($column) }}
                                    @if($isSortable)
                                        <span class="text-[10px]">{{ $this->sortArrow($column) }}</span>
                                    @endif
                                </span>
                            </th>
                        @endforeach
                        <th class="py-2 pr-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stores as $store)
                        <tr class="border-b border-gray-100 dark:border-gray-700/50 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                            <td class="py-2 pr-3 font-mono text-xs">{{ $store['no_tienda'] }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $store['almacen'] }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $store['municipio'] }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $store['estado'] }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $store['unidad_operativa'] }}</td>
                            <td class="py-2 pr-3 text-xs max-w-32 truncate" title="{{ $store['encargado'] ?? '' }}">{{ $store['encargado'] ?: '—' }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $store['tipo_anaquel'] ?: '—' }}</td>
                            <td class="py-2 pr-3 text-xs">{{ $store['estatus'] ?: '—' }}</td>
                            <td class="py-2 pr-3">
                                <a href="{{ route('casa-x-casa.show', $store['id']) }}" class="text-blue-600 hover:underline text-xs">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">No se encontraron tiendas</td>
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
</div>

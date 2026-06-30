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

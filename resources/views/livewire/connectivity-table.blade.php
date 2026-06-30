@php
    $tableData = $this->tableData();
    extract($tableData);
@endphp

<x-livewire-table
    title="Conectividad"
    description="Consulta disponibilidad de telefonía fija, señal celular, compañía e internet por tienda. Al usar los filtros se actualiza la tabla automáticamente."
    wireTarget="almacen,telefono,senial,compania,internet,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showConnectivity"
    :columns="$columns"
    :stores="$stores"
    :filteredCount="$filteredCount"
    :totalCount="$totalCount"
    :page="$page"
    :totalPages="$totalPages"
    :from="$from"
    :to="$to"
    :alignCenter="['No_Tienda_Actual', 'TELEFONIA', 'Señal de celular', 'INTERNET']"
    exportRoute="export.conectividad"
    :exportParams="['almacen' => 'almacen', 'telefono' => 'telefono', 'senial' => 'senial', 'compania' => 'compania', 'internet' => 'internet', 'tienda_salud' => 'tiendaSalud']"
    :columnLabelFn="fn($col) => $this->columnLabel($col)"
    :sortArrowFn="fn($col) => $this->sortArrow($col)"
>
    <x-slot:kpis>
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
    </x-slot:kpis>

    <x-slot:filters>
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
    </x-slot:filters>

    <x-slot:columnToggles>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold self-center">Columnas:</span>
            <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer dark:text-gray-200" data-group="General">
                <input type="checkbox" checked disabled class="opacity-50"> 📋 General
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Conectividad">
                <input type="checkbox" wire:model.live="showConnectivity"> 📡 Conectividad
            </label>
        </div>
    </x-slot:columnToggles>

    <x-slot:rows>
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
    </x-slot:rows>
</x-livewire-table>

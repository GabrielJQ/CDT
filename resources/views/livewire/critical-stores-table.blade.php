@php
    use App\Presenters\IndicadorPresenter;
    $tableData = $this->tableData();
    extract($tableData);
@endphp

<x-livewire-table
    title="Información de Tiendas"
    description="Consulta el nivel de criticidad de las tiendas basado en factores como capital, comités, auditoría, pagarés, rotación y asambleas. Al usar los filtros se actualiza la tabla automáticamente."
    wireTarget="almacen,nivel,indicador,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showFactores,showDetalle"
    :columns="$columns"
    :stores="$stores"
    :filteredCount="$filteredCount"
    :totalCount="$totalCount"
    :page="$page"
    :totalPages="$totalPages"
    :from="$from"
    :to="$to"
    :alignCenter="['Estado', 'No_Tienda_Actual', 'Factores', 'Detalle']"
    tableStyle="table-layout:auto"
    exportRoute="export.criticidad"
    :exportParams="['almacen' => 'almacen', 'nivel' => 'nivel', 'indicador' => 'indicador', 'tienda_salud' => 'tiendaSalud']"
    :columnLabelFn="fn($col) => $this->columnLabel($col)"
    :sortArrowFn="fn($col) => $this->sortArrow($col)"
    :sortableFn="fn($col) => $this->isSortable($col)"
>
    <x-slot:kpis>
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
        @endif
    </x-slot:kpis>

    <x-slot:filters>
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
    </x-slot:filters>

    <x-slot:columnToggles>
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
    </x-slot:columnToggles>

    <x-slot:rows>
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
    </x-slot:rows>
</x-livewire-table>

@php
    $tableData = $this->tableData();
    extract($tableData);
@endphp

<x-livewire-table
    title="Apertura de Tiendas"
    description="Consulta fechas de apertura y antigüedad de tiendas. Al usar los filtros se actualiza la tabla automáticamente."
    wireTarget="almacen,desde,hasta,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showApertura"
    :columns="$columns"
    :stores="$stores"
    :filteredCount="$filteredCount"
    :totalCount="$totalCount"
    :page="$page"
    :totalPages="$totalPages"
    :from="$from"
    :to="$to"
    :alignCenter="['No_Tienda_Actual', '_fecha_apertura', '_antiguedad']"
    tableId="aper-table"
    exportRoute="export.aperturas"
    :exportParams="['almacen' => 'almacen', 'desde' => 'desde', 'hasta' => 'hasta', 'tienda_salud' => 'tiendaSalud']"
    :columnLabelFn="fn($col) => $this->columnLabel($col)"
    :sortArrowFn="fn($col) => $this->sortArrow($col)"
>
    <x-slot:kpis>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Tiendas mostradas</p>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($filteredCount) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">de {{ number_format($totalCount) }} totales</span></p>
            </div>
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-green-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Abiertas este mes</p>
                <p class="text-3xl font-bold text-green-600">{{ number_format($kpis['esteMes'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ ($kpis['total'] ?? 0) > 0 ? round($kpis['esteMes'] / $kpis['total'] * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-amber-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Abiertas este año</p>
                <p class="text-3xl font-bold text-amber-600">{{ number_format($kpis['esteAnio'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ ($kpis['total'] ?? 0) > 0 ? round($kpis['esteAnio'] / $kpis['total'] * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-gray-400">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">⚠️ Sin fecha de apertura</p>
                <p class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ number_format($kpis['sinFecha'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ ($kpis['total'] ?? 0) > 0 ? round($kpis['sinFecha'] / $kpis['total'] * 100, 1) : 0 }}%)</span></p>
            </div>
        </div>
    </x-slot:kpis>

    <x-slot:filters>
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
                <div class="min-w-[160px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Tipo de tienda</label>
                    <select wire:model.live="tiendaSalud" class="input-filter">
                        <option value="">Todas</option>
                        <option value="salud">Tiendas de Salud / Bienestar</option>
                        <option value="regular">Tiendas Bienestar</option>
                    </select>
                </div>
                <button type="button" wire:click="clearFilters" class="btn-secondary">Limpiar</button>
            </div>
        </div>
    </x-slot:filters>

    <x-slot:columnToggles>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold self-center">Columnas:</span>
            <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer dark:text-gray-200" data-group="General">
                <input type="checkbox" checked disabled class="opacity-50"> 📋 General
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Apertura">
                <input type="checkbox" wire:model.live="showApertura"> 📅 Apertura
            </label>
        </div>
    </x-slot:columnToggles>

    <x-slot:rows>
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
    </x-slot:rows>
</x-livewire-table>

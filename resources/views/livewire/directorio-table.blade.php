@php
    $tableData = $this->tableData();
    extract($tableData);
    $MONEY_COLUMNS = ['Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto', 'Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu', 'Bon_Mes', 'Imp_Res_Audi_Mes'];
    $ALIGN_CENTER = ['No_Tienda_Actual', 'TELEFONIA', 'Señal de celular', 'INTERNET', 'Asam_Real_Mes', 'Audit_Realiza_Mes'];
@endphp

<x-livewire-table
    title="Directorio de Tiendas"
    description="Consulta todas las tiendas con información de contacto, ventas, capital, comités, auditoría y ubicación. Al usar los filtros se actualiza la tabla automáticamente."
    wireTarget="q,incompletos,sinCapital,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showContacto,showVentas,showCapital,showComite,showAuditoria,showUbicacion"
    :columns="$columns"
    :stores="$stores"
    :filteredCount="$filteredCount"
    :totalCount="$totalCount"
    :page="$page"
    :totalPages="$totalPages"
    :from="$from"
    :to="$to"
    :alignCenter="$ALIGN_CENTER"
    :alignRight="$MONEY_COLUMNS"
    tableStyle="table-layout:auto"
    exportRoute="export.directorio"
    :exportParams="['q' => 'q', 'incompletos' => 'incompletos', 'sinCapital' => 'sinCapital', 'tienda_salud' => 'tiendaSalud']"
    :columnLabelFn="fn($col) => $this->columnLabel($col)"
    :sortArrowFn="fn($col) => $this->sortArrow($col)"
    :sortableFn="fn($col) => $this->isSortable($col)"
>
    <x-slot:kpis>
        @if(!empty($stats))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Tiendas mostradas</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($filteredCount) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">de {{ number_format($totalCount) }} totales</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-red-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🔴 Incompletos</p>
                    <p class="text-3xl font-bold text-red-600">{{ number_format($stats['incompletos']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($stats['incompletos'] / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-orange-400">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">💰 Sin capital</p>
                    <p class="text-3xl font-bold text-orange-600">{{ number_format($stats['sinCapital']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($stats['sinCapital'] / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-purple-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏛️ Comités incomp.</p>
                    <p class="text-3xl font-bold text-purple-600">{{ number_format($stats['comitesIncompletos'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats['comitesIncompletos'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🗳️ Asambleas mes</p>
                    <p class="text-3xl font-bold text-indigo-600">{{ number_format($stats['asambleasMes'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats['asambleasMes'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-pink-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">💸 Faltante cap.</p>
                    <p class="text-3xl font-bold text-pink-600">{{ number_format($stats['tiendasFaltante'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats['tiendasFaltante'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📄 Pagarés vencidos</p>
                    <p class="text-3xl font-bold text-blue-600">{{ number_format($stats['pagaresVencidos'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats['pagaresVencidos'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
            </div>
        @endif
    </x-slot:kpis>

    <x-slot:filters>
        <div class="filter-panel">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Buscar almacén o tienda</label>
                    <input type="text" wire:model.live.debounce.400ms="q" placeholder="Escribe para filtrar..." class="input-filter">
                </div>
                <div class="flex gap-3 items-end pb-1">
                    <label class="col-toggle flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" wire:model.live="incompletos"> 🔴 Solo incompletos
                    </label>
                    <label class="col-toggle flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" wire:model.live="sinCapital"> 💰 Sin capital
                    </label>
                    <button type="button" wire:click="clearFilters" class="btn-secondary">Limpiar</button>
                </div>
                <div class="min-w-[160px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Tipo de tienda</label>
                    <select wire:model.live="tiendaSalud" class="input-filter">
                        <option value="">Todas</option>
                        <option value="salud">Tiendas de Salud / Bienestar</option>
                        <option value="regular">Tiendas Bienestar</option>
                    </select>
                </div>
            </div>
        </div>
    </x-slot:filters>

    <x-slot:columnToggles>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 mb-4 flex flex-wrap gap-4">
            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold self-center">Columnas:</span>
            <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer dark:text-gray-200" data-group="ID">
                <input type="checkbox" checked disabled class="opacity-50"> 🆔 ID
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Contacto">
                <input type="checkbox" wire:model.live="showContacto"> 📞 Contacto
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Ventas">
                <input type="checkbox" wire:model.live="showVentas"> 📊 Ventas
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Capital">
                <input type="checkbox" wire:model.live="showCapital"> 💰 Capital
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Comite">
                <input type="checkbox" wire:model.live="showComite"> 🏛️ Comité
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Auditoria">
                <input type="checkbox" wire:model.live="showAuditoria"> 🔍 Auditoría
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200" data-group="Ubicacion">
                <input type="checkbox" wire:model.live="showUbicacion"> 🌐 Ubicación
            </label>
        </div>
    </x-slot:columnToggles>

    <x-slot:rows>
        @forelse($stores as $store)
            @php
                $capTot = trim($store['Cap_Tot'] ?? '');
                $noCapital = $capTot === '' || $capTot === '0';
                $purpleBg = ! empty($store['es_tienda_salud_bienestar']) ? 'bg-purple-50/80 dark:bg-purple-900/10' : '';
            @endphp
            <tr class="{{ $noCapital ? 'bg-orange-50 dark:bg-orange-900/20' : '' }} {{ $purpleBg }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                @foreach($columns as $column)
                    @php
                        $align = in_array($column, $MONEY_COLUMNS, true) ? 'text-right' : (in_array($column, $ALIGN_CENTER, true) ? 'text-center' : 'text-left');
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

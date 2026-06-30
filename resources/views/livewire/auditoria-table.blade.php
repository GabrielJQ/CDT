@php
    $tableData = $this->tableData();
    extract($tableData);
    $ALIGN_CENTER = ['No_Tienda_Actual', 'Comite', 'Fec_CRA', 'Asam_Real_Mes', 'Fch_Audit', 'Estado_Aud', 'Rotacion', 'Riesgo'];
    $ALIGN_RIGHT = ['Imp_Res_Audi_Mes'];
@endphp

<x-livewire-table
    title="Auditoría Operativa"
    description="Consulta el estatus de auditoría por tienda incluyendo comités, montos auditados, rotación y nivel de riesgo. Al usar los filtros se actualiza la tabla automáticamente."
    wireTarget="almacen,nivel,estado_comite,estado_auditoria,filtro_500k,rango_rotacion,tiempo_auditoria,asambleas_mes,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showComite,showAuditoria,showRendimiento"
    :columns="$columns"
    :stores="$stores"
    :filteredCount="$filteredCount"
    :totalCount="$totalCount"
    :page="$page"
    :totalPages="$totalPages"
    :from="$from"
    :to="$to"
    :alignCenter="$ALIGN_CENTER"
    :alignRight="$ALIGN_RIGHT"
    tableStyle="table-layout:auto"
    exportRoute="export.auditoria"
    :exportParams="['almacen' => 'almacen', 'nivel' => 'nivel', 'estado_comite' => 'estado_comite', 'estado_auditoria' => 'estado_auditoria', 'filtro_500k' => 'filtro_500k', 'rango_rotacion' => 'rango_rotacion', 'tiempo_auditoria' => 'tiempo_auditoria', 'asambleas_mes' => 'asambleas_mes', 'tienda_salud' => 'tiendaSalud']"
    :columnLabelFn="fn($col) => $this->columnLabel($col)"
    :sortArrowFn="fn($col) => $this->sortArrow($col)"
    :sortableFn="fn($col) => $this->isSortable($col)"
>
    <x-slot:kpis>
        @if (! empty($kpis))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Tiendas evaluadas</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($filteredCount) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">de {{ number_format($totalCount) }}</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-red-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏛️ Comités de CRA vencidos</p>
                    <p class="text-3xl font-bold text-red-600">{{ number_format($kpis['comitesVencidos']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['comitesVencidos'] / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-orange-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🔍 Auditorías mayores a $500,000</p>
                    <p class="text-3xl font-bold text-orange-600">{{ number_format($kpis['auditoriaAlta']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['auditoriaAlta'] / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-amber-500">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📉 Rotación menor a 0.5</p>
                    <p class="text-3xl font-bold text-amber-600">{{ number_format($kpis['rotacionBaja']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['rotacionBaja'] / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-gray-400">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Auditorías pendientes (+3 meses)</p>
                    <p class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ number_format($kpis['auditoriaPendiente']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['auditoriaPendiente'] / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
            </div>

            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Desglose de Rotación</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-gray-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rotación cero</p>
                    <p class="text-xl font-bold text-gray-600">{{ number_format($kpis['rotacionCero'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionCero'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-red-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rotación crítica (&lt;0.5)</p>
                    <p class="text-xl font-bold text-red-600">{{ number_format($kpis['rotacionCritico'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionCritico'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-amber-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rotación media (0.5 a 0.99)</p>
                    <p class="text-xl font-bold text-amber-600">{{ number_format($kpis['rotacionAmarillo'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionAmarillo'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rotación óptima (&ge;1)</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($kpis['rotacionOptimo'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionOptimo'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
            </div>

            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Tiempos de Auditoría</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-blue-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Realizadas este mes</p>
                    <p class="text-xl font-bold text-blue-600">{{ number_format($kpis['auditoriasMes'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['auditoriasMes'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-orange-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sin auditoría &gt; 3 meses (Trimestre)</p>
                    <p class="text-xl font-bold text-orange-600">{{ number_format($kpis['sinAuditoriaTrimestre'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['sinAuditoriaTrimestre'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
                <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-red-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sin auditoría &gt; 1 año</p>
                    <p class="text-xl font-bold text-red-600">{{ number_format($kpis['sinAuditoriaAnio'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['sinAuditoriaAnio'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
                </div>
            </div>
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
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Nivel de riesgo</label>
                    <select wire:model.live="nivel" class="input-filter">
                        <option value="">Todos</option>
                        <option value="rojo">🔴 Crítico</option>
                        <option value="amarillo">🟡 Monitoreo</option>
                        <option value="verde">🟢 Normal</option>
                    </select>
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estado del comité</label>
                    <select wire:model.live="estado_comite" class="input-filter">
                        <option value="">Todos</option>
                        <option value="vigente">🟢 Vigente</option>
                        <option value="proximo_a_vencer">🟡 Próximo a vencer</option>
                        <option value="vencido">🔴 Vencido</option>
                        <option value="sin_fecha">⚪ Sin fecha</option>
                    </select>
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estado de auditoría</label>
                    <select wire:model.live="estado_auditoria" class="input-filter">
                        <option value="">Todos</option>
                        <option value="al_dia">🟢 Al día</option>
                        <option value="vencida">🔴 Vencida</option>
                        <option value="sin_fecha">⚪ Sin fecha</option>
                    </select>
                </div>
                <div class="min-w-[140px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Aud. &gt; $500k</label>
                    <select wire:model.live="filtro_500k" class="input-filter">
                        <option value="">Todos</option>
                        <option value="si">🔴 Sí</option>
                        <option value="no">🟢 No</option>
                    </select>
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Rango Rotación</label>
                    <select wire:model.live="rango_rotacion" class="input-filter">
                        <option value="">Todos</option>
                        <option value="cero">Cero</option>
                        <option value="critico">Crítico (&lt;0.5)</option>
                        <option value="amarillo">Amarillo (0.5 a 0.99)</option>
                        <option value="optimo">Óptimo (&ge;1)</option>
                    </select>
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Tiempo Auditoría</label>
                    <select wire:model.live="tiempo_auditoria" class="input-filter">
                        <option value="">Todos</option>
                        <option value="mes">Realizada en mes</option>
                        <option value="trimestre">Sin aud. &gt; 3 meses</option>
                        <option value="anio">Sin aud. &gt; 1 año</option>
                    </select>
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Asambleas (Mes)</label>
                    <select wire:model.live="asambleas_mes" class="input-filter">
                        <option value="">Todas</option>
                        <option value="si">Con asambleas</option>
                        <option value="no">Sin asambleas</option>
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
            <label class="col-toggle flex items-center gap-1.5 text-sm font-medium cursor-pointer dark:text-gray-200">
                <input type="checkbox" checked disabled class="opacity-50"> 📋 General
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200">
                <input type="checkbox" wire:model.live="showComite"> 🏛️ Comité
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200">
                <input type="checkbox" wire:model.live="showAuditoria"> 🔍 Auditoría
            </label>
            <label class="col-toggle flex items-center gap-1.5 text-sm cursor-pointer dark:text-gray-200">
                <input type="checkbox" wire:model.live="showRendimiento"> 📊 Rendimiento
            </label>
        </div>
    </x-slot:columnToggles>

    <x-slot:rows>
        @forelse($stores as $store)
            @php
                $level = ($store['_audit'] ?? [])['level'] ?? 'verde';
                $rowClass = $level === 'rojo' ? 'bg-red-50 dark:bg-red-900/20' : ($level === 'amarillo' ? 'bg-amber-50 dark:bg-amber-900/20' : '');
                $purpleBg = ! empty($store['es_tienda_salud_bienestar']) ? ' bg-purple-50/80 dark:bg-purple-900/10' : '';
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ $rowClass }}{{ $purpleBg }}">
                @foreach($columns as $column)
                    @php
                        $align = 'text-left';
                        if (in_array($column, $ALIGN_CENTER, true)) {
                            $align = 'text-center';
                        } elseif (in_array($column, $ALIGN_RIGHT, true)) {
                            $align = 'text-right';
                        }
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

<?php

use App\Livewire\ConTablaLivewire;
use App\Presenters\RenderTiendaPresentador;
use App\Servicios\ServicioPostgresql;
use Livewire\Component;

new class extends Component
{
    use ConTablaLivewire;

    private const COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Vigencia', 'Comite',
        'Fec_CRA', 'Asam_Real_Mes', 'Fch_Audit', 'Estado_Aud', 'Imp_Res_Audi_Mes', 'Rotacion', 'Riesgo',
    ];

    private const DB_COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Vigencia', 'Imp_Res_Audi_Mes',
        'Cap_Dic', 'Vta_Mes', 'Fch_Audit', 'Audit_Realiza_Mes', 'Asam_Real_Mes',
    ];

    public string $almacen = '';

    public string $nivel = '';

    public string $estado_comite = '';

    public string $estado_auditoria = '';

    public string $filtro_500k = '';

    public string $rango_rotacion = '';

    public string $tiempo_auditoria = '';

    public string $asambleas_mes = '';

    public string $tiendaSalud = '';

    public bool $showComite = true;

    public bool $showAuditoria = true;

    public bool $showRendimiento = true;

    protected $queryString = [
        'almacen' => ['except' => ''],
        'nivel' => ['except' => ''],
        'estado_comite' => ['except' => ''],
        'estado_auditoria' => ['except' => ''],
        'filtro_500k' => ['except' => ''],
        'rango_rotacion' => ['except' => ''],
        'tiempo_auditoria' => ['except' => ''],
        'asambleas_mes' => ['except' => ''],
        'tiendaSalud' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
        'showComite' => ['except' => true],
        'showAuditoria' => ['except' => true],
        'showRendimiento' => ['except' => true],
    ];

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return self::COLUMNS;
    }

    /** @return list<string> */
    protected function filterProperties(): array
    {
        return ['almacen', 'nivel', 'estado_comite', 'estado_auditoria', 'filtro_500k', 'rango_rotacion', 'tiempo_auditoria', 'asambleas_mes', 'tiendaSalud'];
    }

    protected function clearFilterValues(): void
    {
        $this->almacen = '';
        $this->nivel = '';
        $this->estado_comite = '';
        $this->estado_auditoria = '';
        $this->filtro_500k = '';
        $this->rango_rotacion = '';
        $this->tiempo_auditoria = '';
        $this->asambleas_mes = '';
        $this->tiendaSalud = '';
    }

    private function filters(): array
    {
        return [
            'almacen' => trim($this->almacen),
            'nivel' => $this->nivel,
            'estado_comite' => $this->estado_comite,
            'estado_auditoria' => $this->estado_auditoria,
            'filtro_500k' => $this->filtro_500k,
            'rango_rotacion' => $this->rango_rotacion,
            'tiempo_auditoria' => $this->tiempo_auditoria,
            'asambleas_mes' => $this->asambleas_mes,
            'tienda_salud' => $this->tiendaSalud,
        ];
    }

    private function activeColumns(): array
    {
        $columns = ['Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio'];

        if ($this->showComite) {
            $columns = array_merge($columns, ['Vigencia', 'Comite', 'Fec_CRA', 'Asam_Real_Mes']);
        }

        if ($this->showAuditoria) {
            $columns = array_merge($columns, ['Fch_Audit', 'Estado_Aud', 'Imp_Res_Audi_Mes']);
        }

        if ($this->showRendimiento) {
            $columns = array_merge($columns, ['Rotacion', 'Riesgo']);
        }

        return $columns;
    }

    public function columnLabel(string $column): string
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Localidad' => 'Localidad',
            'Municipio' => 'Municipio',
            'Vigencia' => 'Vigencia',
            'Comite' => 'Comité',
            'Fec_CRA' => 'Fecha CRA',
            'Asam_Real_Mes' => 'Asam. Mes',
            'Fch_Audit' => 'Fch. Audit',
            'Estado_Aud' => 'Estado Aud.',
            'Imp_Res_Audi_Mes' => 'Imp. Res. Audi.',
            'Rotacion' => 'Rotación',
            'Riesgo' => 'Riesgo',
        ][$column] ?? $column;
    }

    private function mesesLabel(int $meses): string
    {
        $m = (int) round($meses);

        if ($m >= 12) {
            $a = intdiv($m, 12);
            $r = $m % 12;
            $label = $a.' año'.($a > 1 ? 's' : '');
            if ($r > 0) {
                $label .= ' '.$r.' mes'.($r > 1 ? 'es' : '');
            }

            return $label;
        }

        return $m.' mes'.($m > 1 ? 'es' : '');
    }

    public function renderCell(string $column, array $store): string
    {
        $audit = $store['_audit'] ?? [];

        if ($column === 'Nombre_Almacen') {
            return RenderTiendaPresentador::renderStoreName($store[$column] ?? '', ! empty($store['es_tienda_salud_bienestar']));
        }

        if (in_array($column, ['Localidad', 'Municipio'], true)) {
            return e($store[$column] ?: '—');
        }

        if ($column === 'No_Tienda_Actual') {
            $n = $store[$column] ?? '';

            return '<span class="font-mono text-gray-700 dark:text-gray-300 block text-center">'.($n ?: '—').'</span>';
        }

        if ($column === 'Vigencia') {
            return RenderTiendaPresentador::formatDate($audit['vigencia'] ?? null);
        }

        if ($column === 'Comite') {
            $ec = $audit['estadoComite'] ?? 'sin_fecha';
            $badges = [
                'vigente' => ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', '🟢 Vigente'],
                'proximo_a_vencer' => ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', '🟡 Próximo a vencer'],
                'vencido' => ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', '🔴 Vencido'],
                'sin_fecha' => ['bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300', '⚪ Sin fecha'],
            ];
            $b = $badges[$ec] ?? $badges['sin_fecha'];

            return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold '.$b[0].'">'.$b[1].'</span>';
        }

        if ($column === 'Fec_CRA') {
            return RenderTiendaPresentador::formatDate($store[$column] ?? '');
        }

        if ($column === 'Asam_Real_Mes') {
            $v = (int) ($store[$column] ?? 0);
            $dateAsam = $store['Asam_Fch_'] ?? '';
            $html = '';
            if ($v > 0) {
                $html = '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">'.$v.' asamblea(s)</span>';
            } else {
                $html = '<span class="text-gray-400 dark:text-gray-500">0</span>';
            }
            if ($dateAsam && $dateAsam !== '0' && $dateAsam !== '#N/A') {
                $html .= '<br><span class="text-xs text-gray-500 dark:text-gray-400">📅 '.RenderTiendaPresentador::formatDate($dateAsam).'</span>';
            }

            return $html;
        }

        if ($column === 'Fch_Audit') {
            return RenderTiendaPresentador::formatDate($audit['fchAudit'] ?? null);
        }

        if ($column === 'Estado_Aud') {
            $fch = $audit['fchAudit'] ?? null;
            $meses = $audit['mesesSinAuditoria'] ?? null;
            $color = '';
            $label = '';
            $sub = '';
            if (! $fch) {
                $color = 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300';
                $label = '⚪ Sin fecha';
            } elseif ((int) $meses >= 3) {
                $color = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
                $label = '🔴 Vencida';
                $sub = $this->mesesLabel((int) $meses);
            } else {
                $color = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                $label = '🟢 Al día';
                $sub = $this->mesesLabel((int) $meses);
            }
            $html = '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold '.$color.'">'.$label.'</span>';
            if ($sub) {
                $html .= '<br><span class="text-xs text-gray-400 dark:text-gray-500">'.$sub.'</span>';
            }

            return $html;
        }

        if ($column === 'Imp_Res_Audi_Mes') {
            $imp = (float) ($audit['impuesto'] ?? 0);
            if ($imp > 0) {
                return '<span class="font-mono text-gray-700 dark:text-gray-300 text-right block">'.RenderTiendaPresentador::formatMoney($imp).'</span>';
            }

            return '<span class="text-gray-400 dark:text-gray-500">—</span>';
        }

        if ($column === 'Rotacion') {
            $r = (float) ($audit['rotacion'] ?? 0);
            if ($r > 0) {
                return '<span class="font-mono text-gray-700 dark:text-gray-300">'.number_format($r, 2).'</span>';
            }

            return '<span class="text-gray-400 dark:text-gray-500">—</span>';
        }

        if ($column === 'Riesgo') {
            $level = $audit['level'] ?? 'verde';
            $badges = [
                'rojo' => ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', '🔴 Crítico'],
                'amarillo' => ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', '🟡 Monitoreo'],
                'verde' => ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', '🟢 Normal'],
            ];
            $b = $badges[$level] ?? $badges['verde'];

            return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold '.$b[0].'">'.$b[1].'</span>';
        }

        return e($store[$column] ?? '');
    }

    public function isSortable(string $column): bool
    {
        return in_array($column, $this->sortableColumns(), true) && ! in_array($column, $this->excludedSortColumns(), true);
    }

    public function tableData(): array
    {
        $postgres = app(ServicioPostgresql::class);
        $result = $postgres->obtenerAuditoriaPaginada(
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

<div class="page-shell" wire:loading.class="opacity-70" wire:target="almacen,nivel,estado_comite,estado_auditoria,filtro_500k,rango_rotacion,tiempo_auditoria,asambleas_mes,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showComite,showAuditoria,showRendimiento">
    <x-module-header title="Auditoría Operativa" description="Consulta el estatus de auditoría por tienda incluyendo comités, montos auditados, rotación y nivel de riesgo. Al usar los filtros se actualiza la tabla automáticamente." />

    @if (! empty($kpis))
        {{-- KPIs ROW 1 --}}
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

        {{-- KPIs ROW 2 --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-gray-400">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Auditorías pendientes (+3 meses)</p>
                <p class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ number_format($kpis['auditoriaPendiente']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['auditoriaPendiente'] / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
        </div>

        {{-- Desglose Rotación --}}
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

        {{-- Desglose Auditoría --}}
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

    <x-export-button route="export.auditoria" :params="['almacen' => 'almacen', 'nivel' => 'nivel', 'estado_comite' => 'estado_comite', 'estado_auditoria' => 'estado_auditoria', 'filtro_500k' => 'filtro_500k', 'rango_rotacion' => 'rango_rotacion', 'tiempo_auditoria' => 'tiempo_auditoria', 'asambleas_mes' => 'asambleas_mes', 'tienda_salud' => 'tiendaSalud']" class="mb-6" />

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

    <x-table-summary :from="$from" :to="$to" :filteredCount="$filteredCount" :totalCount="$totalCount" />

    <div x-data="{ page: @entangle('page') }" x-init="$watch('page', () => $nextTick(() => $el.scrollTop = 0))" class="max-h-[65vh] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200" style="table-layout:auto">
            <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                <tr>
                    @foreach($columns as $column)
                        @php
                            $sortable = $this->isSortable($column);
                            $align = 'text-left';
                            if (in_array($column, ['No_Tienda_Actual', 'Comite', 'Fec_CRA', 'Asam_Real_Mes', 'Fch_Audit', 'Estado_Aud', 'Rotacion', 'Riesgo'], true)) {
                                $align = 'text-center';
                            } elseif (in_array($column, ['Imp_Res_Audi_Mes'], true)) {
                                $align = 'text-right';
                            }
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
                        $level = ($store['_audit'] ?? [])['level'] ?? 'verde';
                        $rowClass = $level === 'rojo' ? 'bg-red-50 dark:bg-red-900/20' : ($level === 'amarillo' ? 'bg-amber-50 dark:bg-amber-900/20' : '');
                        $purpleBg = ! empty($store['es_tienda_salud_bienestar']) ? ' bg-purple-50/80 dark:bg-purple-900/10' : '';
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ $rowClass }}{{ $purpleBg }}">
                        @foreach($columns as $column)
                            @php
                                $align = 'text-left';
                                if (in_array($column, ['No_Tienda_Actual', 'Comite', 'Fec_CRA', 'Asam_Real_Mes', 'Fch_Audit', 'Estado_Aud', 'Rotacion', 'Riesgo'], true)) {
                                    $align = 'text-center';
                                } elseif (in_array($column, ['Imp_Res_Audi_Mes'], true)) {
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
            </tbody>
        </table>
    </div>

    <x-table-pagination :page="$page" :totalPages="$totalPages" />
</div>

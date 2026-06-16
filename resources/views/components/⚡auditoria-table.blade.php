<?php

use App\Servicios\ServicioPostgresql;
use Livewire\Component;

new class extends Component
{
    private const COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Vigencia', 'Comite',
        'Fec_CRA', 'Asam_Real_Mes', 'Fch_Audit', 'Estado_Aud', 'Imp_Res_Audi_Mes', 'Rotacion', 'Riesgo',
    ];

    private const DB_COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Vigencia', 'Imp_Res_Audi_Mes',
        'Cap_Dic', 'Vta_Mes', 'Fch_Audit', 'Audit_Realiza_Mes', 'Asam_Real_Mes',
    ];

    private const EXCLUDED_SORT_COLUMNS = ['Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio'];

    public string $almacen = '';

    public string $nivel = '';

    public string $estado_comite = '';

    public string $estado_auditoria = '';

    public string $filtro_500k = '';

    public string $rango_rotacion = '';

    public string $tiempo_auditoria = '';

    public string $asambleas_mes = '';

    public ?string $sort = null;

    public string $direction = 'asc';

    public int $page = 1;

    public int $perPage = 50;

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
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
        'showComite' => ['except' => true],
        'showAuditoria' => ['except' => true],
        'showRendimiento' => ['except' => true],
    ];

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
        ];
    }

    private function regionFilters(): array
    {
        return [
            'region' => request()->cookie('region_filter', ''),
            'uo' => request()->cookie('uo_filter', ''),
        ];
    }

    private function sortInput(): array
    {
        if (! $this->sort || ! in_array($this->sort, self::COLUMNS, true) || in_array($this->sort, self::EXCLUDED_SORT_COLUMNS, true)) {
            return ['column' => null, 'direction' => $this->direction === 'desc' ? 'desc' : 'asc'];
        }

        return ['column' => $this->sort, 'direction' => $this->direction === 'desc' ? 'desc' : 'asc'];
    }

    public function updated($property): void
    {
        if (in_array($property, ['almacen', 'nivel', 'estado_comite', 'estado_auditoria', 'filtro_500k', 'rango_rotacion', 'tiempo_auditoria', 'asambleas_mes', 'perPage'], true)) {
            $this->page = 1;
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::COLUMNS, true) || in_array($column, self::EXCLUDED_SORT_COLUMNS, true)) {
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
        $this->almacen = '';
        $this->nivel = '';
        $this->estado_comite = '';
        $this->estado_auditoria = '';
        $this->filtro_500k = '';
        $this->rango_rotacion = '';
        $this->tiempo_auditoria = '';
        $this->asambleas_mes = '';
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

    private function formatMoney(float $num): string
    {
        return '$'.number_format($num, 2);
    }

    private function formatDate(?string $date): string
    {
        if (! $date) {
            return '—';
        }

        return substr($date, 0, 10);
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
            return '<strong class="text-gray-900 dark:text-gray-100">'.e($store[$column] ?: '—').'</strong>';
        }

        if (in_array($column, ['Localidad', 'Municipio'], true)) {
            return e($store[$column] ?: '—');
        }

        if ($column === 'No_Tienda_Actual') {
            $n = $store[$column] ?? '';

            return '<span class="font-mono text-gray-700 dark:text-gray-300 block text-center">'.($n ?: '—').'</span>';
        }

        if ($column === 'Vigencia') {
            $d = $audit['vigencia'] ?? null;

            if ($d) {
                return '<span class="font-mono text-gray-700 dark:text-gray-300">'.$this->formatDate($d).'</span>';
            }

            return '<span class="text-gray-400 dark:text-gray-500">—</span>';
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
            $d = $store[$column] ?? '';
            if ($d) {
                return '<span class="font-mono text-gray-700 dark:text-gray-300">'.$this->formatDate($d).'</span>';
            }

            return '<span class="text-gray-400 dark:text-gray-500">—</span>';
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
                $html .= '<br><span class="text-xs text-gray-500 dark:text-gray-400">📅 '.$this->formatDate($dateAsam).'</span>';
            }

            return $html;
        }

        if ($column === 'Fch_Audit') {
            $d = $audit['fchAudit'] ?? null;
            if ($d) {
                return '<span class="font-mono text-gray-700 dark:text-gray-300">'.$this->formatDate($d).'</span>';
            }

            return '<span class="text-gray-400 dark:text-gray-500">—</span>';
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
                return '<span class="font-mono text-gray-700 dark:text-gray-300 text-right block">'.$this->formatMoney($imp).'</span>';
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

    public function sortArrow(string $column): string
    {
        if (in_array($column, self::EXCLUDED_SORT_COLUMNS, true)) {
            return '';
        }

        if ($this->sort !== $column) {
            return '↕';
        }

        return $this->direction === 'asc' ? '▲' : '▼';
    }

    public function isSortable(string $column): bool
    {
        return ! in_array($column, self::EXCLUDED_SORT_COLUMNS, true);
    }

    public function exportUrl(): string
    {
        return url('/auditoria?'.http_build_query(array_filter([
            'almacen' => trim($this->almacen),
            'nivel' => $this->nivel,
            'estado_comite' => $this->estado_comite,
            'estado_auditoria' => $this->estado_auditoria,
            'filtro_500k' => $this->filtro_500k,
            'rango_rotacion' => $this->rango_rotacion,
            'tiempo_auditoria' => $this->tiempo_auditoria,
            'asambleas_mes' => $this->asambleas_mes,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
            'export' => 'csv',
        ], fn ($value) => $value !== null && $value !== '' && $value !== false)));
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
    <div class="institutional-card mb-6 flex flex-col gap-4 border-l-4 border-[#988256] p-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[#988256]">Módulo operativo</p>
            <h3 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-gray-100">Auditoría Operativa</h3>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Consulta el estatus de auditoría por tienda incluyendo comités, montos auditados, rotación y nivel de riesgo. Los filtros, KPIs y paginación se actualizan sin recargar la página.</p>
        </div>
        <a href="{{ $this->exportUrl() }}" class="btn-export self-start lg:self-center" wire:navigate.hover="false">Exportar CSV</a>
    </div>

    @if (! empty($kpis))
        {{-- KPIs ROW 1 --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Tiendas evaluadas</p>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($filteredCount) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">de {{ number_format($totalCount) }}</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-red-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏛️ Comités de CRA vencidos</p>
                <p class="text-3xl font-bold text-red-600">{{ number_format($kpis['comitesVencidos']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['comitesVencidos'] / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-orange-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🔍 Auditorías mayores a $500,000</p>
                <p class="text-3xl font-bold text-orange-600">{{ number_format($kpis['auditoriaAlta']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['auditoriaAlta'] / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-amber-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📉 Rotación menor a 0.5</p>
                <p class="text-3xl font-bold text-amber-600">{{ number_format($kpis['rotacionBaja']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['rotacionBaja'] / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
        </div>

        {{-- KPIs ROW 2 --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-gray-400">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📅 Auditorías pendientes (+3 meses)</p>
                <p class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ number_format($kpis['auditoriaPendiente']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($kpis['auditoriaPendiente'] / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
        </div>

        {{-- Desglose Rotación --}}
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Desglose de Rotación</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-gray-500">
                <p class="text-xs text-gray-500 dark:text-gray-400">Rotación cero</p>
                <p class="text-xl font-bold text-gray-600">{{ number_format($kpis['rotacionCero'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionCero'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-red-500">
                <p class="text-xs text-gray-500 dark:text-gray-400">Rotación crítica (&lt;0.5)</p>
                <p class="text-xl font-bold text-red-600">{{ number_format($kpis['rotacionCritico'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionCritico'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-amber-500">
                <p class="text-xs text-gray-500 dark:text-gray-400">Rotación media (0.5 a 0.99)</p>
                <p class="text-xl font-bold text-amber-600">{{ number_format($kpis['rotacionAmarillo'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionAmarillo'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-green-500">
                <p class="text-xs text-gray-500 dark:text-gray-400">Rotación óptima (&ge;1)</p>
                <p class="text-xl font-bold text-green-600">{{ number_format($kpis['rotacionOptimo'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['rotacionOptimo'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
        </div>

        {{-- Desglose Auditoría --}}
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">Tiempos de Auditoría</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-blue-500">
                <p class="text-xs text-gray-500 dark:text-gray-400">Realizadas este mes</p>
                <p class="text-xl font-bold text-blue-600">{{ number_format($kpis['auditoriasMes'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['auditoriasMes'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-orange-500">
                <p class="text-xs text-gray-500 dark:text-gray-400">Sin auditoría &gt; 3 meses (Trimestre)</p>
                <p class="text-xl font-bold text-orange-600">{{ number_format($kpis['sinAuditoriaTrimestre'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($kpis['sinAuditoriaTrimestre'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-3 border-l-4 border-red-500">
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
            <div class="flex gap-2">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Limpiar</button>
            </div>
        </div>
    </div>

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

    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Mostrando <strong>{{ number_format($from) }}</strong>–<strong>{{ number_format($to) }}</strong> de <strong>{{ number_format($filteredCount) }}</strong> tiendas
        @if($filteredCount !== $totalCount)
            <span class="text-gray-400 dark:text-gray-500">(filtradas de {{ number_format($totalCount) }})</span>
        @endif
        <span wire:loading class="ml-2 text-[#988256] font-semibold">Actualizando...</span>
    </div>

    <div class="table-shell">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200" style="table-layout:auto">
            <thead class="bg-gray-50 dark:bg-gray-800">
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
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ $rowClass }}">
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

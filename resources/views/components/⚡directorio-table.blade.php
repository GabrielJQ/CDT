<?php

use App\Servicios\ServicioPostgresql;
use Livewire\Component;

new class extends Component
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura', 'TELEFONIA', 'Señal de celular',
        'Compañía', 'INTERNET', 'CORREO', 'Direccion', 'Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu',
        'Bon_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia',
        'Fch_Audit', 'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Nom_Pre_CRA',
        'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
        'Asam_Real_Mes',
    ];

    private const TRACKED_COLUMNS = [
        'TELEFONIA', 'CORREO', 'Señal de celular', 'Compañía', 'INTERNET',
        'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic',
        'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia', 'Fch_Audit', 'Imp_Res_Audi_Mes',
        'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Direccion',
        'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA',
        'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
    ];

    private const MONEY_COLUMNS = [
        'Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto',
        'Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu', 'Bon_Mes',
        'Imp_Res_Audi_Mes',
    ];

    private const SORTABLE_COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura', 'TELEFONIA', 'Señal de celular',
        'Compañía', 'INTERNET', 'CORREO', 'Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu',
        'Bon_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia',
        'Fch_Audit', 'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes', 'Nom_Pre_CRA',
        'Asam_Real_Mes',
    ];

    private const EXCLUDED_SORT_COLUMNS = ['Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio'];

    public string $q = '';

    public bool $incompletos = false;

    public bool $sinCapital = false;

    public string $tiendaSalud = '';

    public ?string $sort = null;

    public string $direction = 'asc';

    public int $page = 1;

    public int $perPage = 50;

    public bool $showContacto = false;

    public bool $showVentas = false;

    public bool $showCapital = false;

    public bool $showComite = false;

    public bool $showAuditoria = false;

    public bool $showUbicacion = false;

    protected $queryString = [
        'q' => ['except' => ''],
        'incompletos' => ['except' => false],
        'sinCapital' => ['except' => false],
        'tiendaSalud' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
        'showContacto' => ['except' => false],
        'showVentas' => ['except' => false],
        'showCapital' => ['except' => false],
        'showComite' => ['except' => false],
        'showAuditoria' => ['except' => false],
        'showUbicacion' => ['except' => false],
    ];

    private function filters(): array
    {
        return [
            'q' => trim($this->q),
            'incompletos' => $this->incompletos,
            'sinCapital' => $this->sinCapital,
            'tienda_salud' => $this->tiendaSalud,
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
        if (! $this->sort || ! in_array($this->sort, self::SORTABLE_COLUMNS, true) || in_array($this->sort, self::EXCLUDED_SORT_COLUMNS, true)) {
            return ['column' => null, 'direction' => $this->direction === 'desc' ? 'desc' : 'asc'];
        }

        return ['column' => $this->sort, 'direction' => $this->direction === 'desc' ? 'desc' : 'asc'];
    }

    public function updated($property): void
    {
        if (in_array($property, ['q', 'incompletos', 'sinCapital', 'tiendaSalud', 'perPage'], true)) {
            $this->page = 1;
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true) || in_array($column, self::EXCLUDED_SORT_COLUMNS, true)) {
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
        $this->q = '';
        $this->incompletos = false;
        $this->sinCapital = false;
        $this->tiendaSalud = '';
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
        $columns = ['Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura'];

        if ($this->showContacto) {
            $columns = array_merge($columns, ['TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET', 'CORREO']);
        }

        if ($this->showVentas) {
            $columns = array_merge($columns, ['Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu', 'Bon_Mes']);
        }

        if ($this->showCapital) {
            $columns = array_merge($columns, ['Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto', 'Pagare_Fecha']);
        }

        if ($this->showComite) {
            $columns = array_merge($columns, ['Fec_CRA', 'Vigencia', 'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA', 'Asam_Real_Mes']);
        }

        if ($this->showAuditoria) {
            $columns = array_merge($columns, ['Fch_Audit', 'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes']);
        }

        if ($this->showUbicacion) {
            $columns = array_merge($columns, ['Latitud', 'Longitud', 'Direccion']);
        }

        return $columns;
    }

    public function columnLabel(string $column): string
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'Fecha_Apertura' => 'Apertura',
            'TELEFONIA' => '📞 Tel.',
            'Señal de celular' => '📱 Señal',
            'Compañía' => 'Compañía',
            'INTERNET' => '🌐 Internet',
            'CORREO' => '📧 Correo',
            'Direccion' => 'Dirección',
            'Vta_Mes' => 'Vta Mes',
            'VtaNeta_Mes' => 'Vta Neta',
            'Vta_Acu' => 'Vta Acum',
            'VtaNeta_Acu' => 'Vta Neta Acum',
            'Bon_Mes' => 'Bon Mes',
            'Cap_Tot' => 'Cap Total',
            'Cap_Com' => 'Cap Com',
            'Cap_Dic' => 'Cap Dic',
            'Pagare_Monto' => 'Pagaré',
            'Pagare_Fecha' => 'Pagaré Fecha',
            'Fec_CRA' => 'Fec CRA',
            'Vigencia' => 'Vigencia',
            'Nom_Pre_CRA' => 'Presidente',
            'Nom_Pre_Sup_CRA' => 'Pres. Suplente',
            'Nom_Sec_CRA' => 'Secretario',
            'Nom_Sec_Sup_CRA' => 'Sec. Suplente',
            'Nom_Tes_CRA' => 'Tesorero',
            'Nom_Vcv_CRA' => 'Vocal',
            'Nom_Voc_Gen_CRA' => 'Vocal General',
            'Asam_Real_Mes' => 'Asam. Mes',
            'Fch_Audit' => 'Fch Audit',
            'Imp_Res_Audi_Mes' => 'Impuesto',
            'Audit_Realiza_Mes' => 'Auditoría',
            'Latitud' => 'Latitud',
            'Longitud' => 'Longitud',
        ][$column] ?? $column;
    }

    private function isEmpty(?string $val): bool
    {
        return $val === '' || $val === null || $val === '0' || trim($val) === '';
    }

    private function formatMoney(string $val): string
    {
        $num = (float) str_replace([',', '$', ' '], '', $val);

        return '$'.number_format($num, 2);
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
        $val = $store[$column] ?? '';

        if ($column === 'Nombre_Almacen') {
            return $this->renderStoreName($val, ! empty($store['es_tienda_salud_bienestar']));
        }

        if ($column === 'No_Tienda_Actual') {
            return '<span class="font-mono text-gray-700 dark:text-gray-300 block text-center">'.($val ? number_format((float) $val) : '—').'</span>';
        }

        if (in_array($column, ['Municipio', 'Localidad'], true)) {
            return e($val ?: '—');
        }

        if ($column === 'Direccion') {
            return '<span class="text-xs text-gray-600 dark:text-gray-400 max-w-xs block truncate" title="'.e($val).'">'.e($val ?: '—').'</span>';
        }

        if ($column === 'Fecha_Apertura') {
            if (! $val) {
                return '<span class="text-gray-400 dark:text-gray-500">—</span>';
            }
            $parts = explode('-', substr($val, 0, 10));

            return '<span class="font-mono text-gray-700 dark:text-gray-300">'.(count($parts) === 3 ? $parts[2].'/'.$parts[1].'/'.$parts[0] : e($val)).'</span>';
        }

        if (in_array($column, ['TELEFONIA', 'Señal de celular', 'INTERNET'], true)) {
            return '<div class="text-center">'.$this->yesNoBadge($val).'</div>';
        }

        if ($column === 'Compañía') {
            return '<span class="text-gray-700 dark:text-gray-300">'.e(trim($val) ?: '—').'</span>';
        }

        if ($column === 'CORREO') {
            return '<span class="text-xs text-gray-600 dark:text-gray-400 max-w-40 block truncate" title="'.e($val).'">'.e($val ?: '—').'</span>';
        }

        if (in_array($column, self::MONEY_COLUMNS, true)) {
            if ($this->isEmpty($val)) {
                return '<span class="text-gray-400 dark:text-gray-500">—</span>';
            }

            return '<span class="font-mono text-gray-700 dark:text-gray-300 text-right block">'.$this->formatMoney($val).'</span>';
        }

        if (in_array($column, ['Fec_CRA', 'Vigencia', 'Fch_Audit', 'Pagare_Fecha'], true)) {
            if (! $val || $val === '0') {
                return '<span class="text-gray-400 dark:text-gray-500">—</span>';
            }

            return '<span class="font-mono text-gray-700 dark:text-gray-300">'.substr($val, 0, 10).'</span>';
        }

        if (in_array($column, ['Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA'], true)) {
            if ($this->isEmpty($val)) {
                return '<span class="text-gray-400 dark:text-gray-500">—</span>';
            }

            return '<span class="text-gray-700 dark:text-gray-300">'.e($val).'</span>';
        }

        if ($column === 'Asam_Real_Mes') {
            $v = (int) ($val ?: 0);
            if ($v > 0) {
                return '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">'.$v.' asamblea(s)</span>';
            }

            return '<span class="text-gray-400 dark:text-gray-500">0</span>';
        }

        if ($column === 'Audit_Realiza_Mes') {
            $v = (int) ($val ?: 0);
            if ($v > 0) {
                return '<span class="font-mono text-gray-700 dark:text-gray-300">'.number_format($v).'</span>';
            }

            return '<span class="text-gray-400 dark:text-gray-500">—</span>';
        }

        if (in_array($column, ['Latitud', 'Longitud'], true)) {
            if ($this->isEmpty($val) || $val === '0') {
                return '<span class="text-gray-400 dark:text-gray-500">—</span>';
            }

            return '<span class="font-mono text-xs text-gray-600 dark:text-gray-400">'.e($val).'</span>';
        }

        return e($val ?: '');
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
        return in_array($column, self::SORTABLE_COLUMNS, true) && ! in_array($column, self::EXCLUDED_SORT_COLUMNS, true);
    }

    public function exportUrl(): string
    {
        return url('/directorio?'.http_build_query(array_filter([
            'q' => trim($this->q),
            'incompletos' => $this->incompletos ? '1' : null,
            'sinCapital' => $this->sinCapital ? '1' : null,
            'tienda_salud' => $this->tiendaSalud,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
            'export' => 'csv',
        ], fn ($value) => $value !== null && $value !== '')));
    }

    public function tableData(): array
    {
        $postgres = app(ServicioPostgresql::class);
        $result = $postgres->obtenerDirectorioPaginado(
            $this->regionFilters(),
            $this->filters(),
            $this->page,
            $this->perPage,
            self::COLUMNS,
            self::TRACKED_COLUMNS,
            $this->sortInput(),
        );

        $totalPages = max(1, (int) ceil(($result['filtered'] ?? 0) / $this->perPage));
        $this->page = min($this->page, $totalPages);

        return [
            'stores' => $result['rows'],
            'stats' => $result['stats'],
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

<div class="page-shell" wire:loading.class="opacity-70" wire:target="q,incompletos,sinCapital,sortBy,clearFilters,previousTablePage,nextTablePage,goToTablePage,showContacto,showVentas,showCapital,showComite,showAuditoria,showUbicacion">
    <div class="institutional-card mb-6 flex flex-col gap-4 border-l-4 border-[#988256] p-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[#988256]">Módulo operativo</p>
            <h3 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-gray-100">Directorio de Tiendas</h3>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Consulta todas las tiendas con información de contacto, ventas, capital, comités, auditoría y ubicación. Los filtros, KPIs y paginación se actualizan sin recargar la página.</p>
        </div>
        <a href="{{ $this->exportUrl() }}" class="btn-export self-start lg:self-center" wire:navigate.hover="false">Exportar CSV</a>
    </div>

    @if(!empty($stats))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Tiendas mostradas</p>
                <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($filteredCount) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">de {{ number_format($totalCount) }} totales</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-red-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🔴 Incompletos</p>
                <p class="text-3xl font-bold text-red-600">{{ number_format($stats['incompletos']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($stats['incompletos'] / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-orange-400">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">💰 Sin capital</p>
                <p class="text-3xl font-bold text-orange-600">{{ number_format($stats['sinCapital']) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round($stats['sinCapital'] / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-purple-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏛️ Comités incomp.</p>
                <p class="text-3xl font-bold text-purple-600">{{ number_format($stats['comitesIncompletos'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats['comitesIncompletos'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-indigo-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">🗳️ Asambleas mes</p>
                <p class="text-3xl font-bold text-indigo-600">{{ number_format($stats['asambleasMes'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats['asambleasMes'] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-pink-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">💸 Faltante cap.</p>
                <p class="text-3xl font-bold text-pink-600">{{ number_format($stats['tiendasFaltante'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats['tiendasFaltante'] ?? 0) / $totalCount * 100, 1) : 0 }}% · ${{ number_format($stats['importeFaltante'] ?? 0, 2) }})</span></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide">📄 Pagarés vencidos</p>
                <p class="text-3xl font-bold text-blue-600">{{ number_format($stats['pagaresVencidos'] ?? 0) }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats['pagaresVencidos'] ?? 0) / $totalCount * 100, 1) : 0 }}% · ${{ number_format($stats['importePagaresVencidos'] ?? 0, 2) }})</span></p>
            </div>
        </div>
    @endif

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

    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Mostrando <strong>{{ number_format($from) }}</strong>–<strong>{{ number_format($to) }}</strong> de <strong>{{ number_format($filteredCount) }}</strong> tiendas
        @if($filteredCount !== $totalCount)
            <span class="text-gray-400 dark:text-gray-500">(filtradas de {{ number_format($totalCount) }})</span>
        @endif
        <span wire:loading class="ml-2 text-[#988256] font-semibold">Actualizando...</span>
    </div>

    <div x-data="{ page: @entangle('page') }" x-init="$watch('page', () => $nextTick(() => $el.scrollTop = 0))" class="max-h-[65vh] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm dark:text-gray-200" style="table-layout:auto">
            <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                <tr>
                    @foreach($columns as $column)
                        @php
                            $sortable = $this->isSortable($column);
                            $align = in_array($column, self::MONEY_COLUMNS, true) ? 'text-right' : (in_array($column, ['No_Tienda_Actual', 'TELEFONIA', 'Señal de celular', 'INTERNET', 'Asam_Real_Mes', 'Audit_Realiza_Mes'], true) ? 'text-center' : 'text-left');
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
                        $capTot = trim($store['Cap_Tot'] ?? '');
                        $noCapital = $capTot === '' || $capTot === '0';
                        $purpleBg = ! empty($store['es_tienda_salud_bienestar']) ? 'bg-purple-50/80 dark:bg-purple-900/10' : '';
                    @endphp
                    <tr class="{{ $noCapital ? 'bg-orange-50 dark:bg-orange-900/20' : '' }} {{ $purpleBg }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        @foreach($columns as $column)
                            @php
                                $align = in_array($column, self::MONEY_COLUMNS, true) ? 'text-right' : (in_array($column, ['No_Tienda_Actual', 'TELEFONIA', 'Señal de celular', 'INTERNET', 'Asam_Real_Mes', 'Audit_Realiza_Mes'], true) ? 'text-center' : 'text-left');
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

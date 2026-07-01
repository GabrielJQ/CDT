<?php

namespace App\Livewire;

use App\Presenters\RenderTiendaPresentador;
use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioPostgresql;
use Livewire\Component;

class DirectorioTable extends Component
{
    use ConTablaLivewire;

    protected ServicioPostgresql $postgres;

    public function boot(ServicioPostgresql $postgres, ServicioAlcanceUsuario $alcanceUsuario): void
    {
        $this->postgres = $postgres;
        $this->setAlcanceUsuario($alcanceUsuario);
    }

    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura', 'TELEFONIA', 'Señal de celular',
        'Compañía', 'INTERNET', 'CORREO', 'Direccion', 'Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu',
        'Bon_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia',
        'Fch_Audit', 'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Nom_Pre_CRA',
        'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
        'Asam_Real_Mes',
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

    public string $q = '';

    public bool $incompletos = false;

    public bool $sinCapital = false;

    public string $tiendaSalud = '';

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

    protected function sortableColumns(): array
    {
        return self::SORTABLE_COLUMNS;
    }

    private function filters(): array
    {
        return [
            'q' => trim($this->q),
            'incompletos' => $this->incompletos,
            'sinCapital' => $this->sinCapital,
            'tienda_salud' => $this->tiendaSalud,
        ];
    }

    protected function filterProperties(): array
    {
        return ['q', 'incompletos', 'sinCapital', 'tiendaSalud'];
    }

    protected function clearFilterValues(): void
    {
        $this->q = '';
        $this->incompletos = false;
        $this->sinCapital = false;
        $this->tiendaSalud = '';
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

    public function renderCell(string $column, array $store): string
    {
        $val = $store[$column] ?? '';

        if ($column === 'Nombre_Almacen') {
            return RenderTiendaPresentador::renderStoreName($val, ! empty($store['es_tienda_salud_bienestar']));
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
            return RenderTiendaPresentador::formatDate($val);
        }

        if (in_array($column, ['TELEFONIA', 'Señal de celular', 'INTERNET'], true)) {
            return '<div class="text-center">'.RenderTiendaPresentador::yesNoBadge($val).'</div>';
        }

        if ($column === 'Compañía') {
            return '<span class="text-gray-700 dark:text-gray-300">'.e(trim($val) ?: '—').'</span>';
        }

        if ($column === 'CORREO') {
            return '<span class="text-xs text-gray-600 dark:text-gray-400 max-w-40 block truncate" title="'.e($val).'">'.e($val ?: '—').'</span>';
        }

        if (in_array($column, self::MONEY_COLUMNS, true)) {
            if (RenderTiendaPresentador::isEmpty($val)) {
                return '<span class="text-gray-400 dark:text-gray-500">—</span>';
            }

            return '<span class="font-mono text-gray-700 dark:text-gray-300 text-right block">'.RenderTiendaPresentador::formatMoney($val).'</span>';
        }

        if (in_array($column, ['Fec_CRA', 'Vigencia', 'Fch_Audit', 'Pagare_Fecha'], true)) {
            return RenderTiendaPresentador::formatDate($val);
        }

        if (in_array($column, ['Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA'], true)) {
            if (RenderTiendaPresentador::isEmpty($val)) {
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
            if (RenderTiendaPresentador::isEmpty($val) || $val === '0') {
                return '<span class="text-gray-400 dark:text-gray-500">—</span>';
            }

            return '<span class="font-mono text-xs text-gray-600 dark:text-gray-400">'.e($val).'</span>';
        }

        return e($val ?: '');
    }

    public function isSortable(string $column): bool
    {
        return in_array($column, self::SORTABLE_COLUMNS, true) && ! in_array($column, $this->excludedSortColumns(), true);
    }

    public function tableData(): array
    {
        $result = $this->postgres->obtenerDirectorioPaginado(
            $this->regionFilters(),
            $this->filters(),
            $this->page,
            $this->perPage,
            self::COLUMNS,
            ServicioPostgresql::TRACKED_DIRECTORIO_COLUMNS,
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

    public function render()
    {
        return view('livewire.directorio-table');
    }
}

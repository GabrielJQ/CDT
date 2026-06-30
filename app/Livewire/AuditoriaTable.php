<?php

namespace App\Livewire;

use App\Presenters\RenderTiendaPresentador;
use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioPostgresql;
use Livewire\Component;

class AuditoriaTable extends Component
{
    use ConTablaLivewire;

    protected ServicioPostgresql $postgres;

    public function boot(ServicioPostgresql $postgres, ServicioAlcanceUsuario $alcanceUsuario): void
    {
        $this->postgres = $postgres;
        $this->setAlcanceUsuario($alcanceUsuario);
    }

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
        'showComite' => ['except' => true],
        'showAuditoria' => ['except' => true],
        'showRendimiento' => ['except' => true],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    protected function sortableColumns(): array
    {
        return self::COLUMNS;
    }

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
        $result = $this->postgres->obtenerAuditoriaPaginada(
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

    public function render()
    {
        return view('livewire.auditoria-table');
    }
}

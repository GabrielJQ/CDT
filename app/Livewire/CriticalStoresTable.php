<?php

namespace App\Livewire;

use App\Presenters\IndicadorPresenter;
use App\Presenters\RenderTiendaPresentador;
use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioPostgresql;
use Livewire\Component;

class CriticalStoresTable extends Component
{
    use ConTablaLivewire;

    protected ServicioPostgresql $postgres;

    public function boot(ServicioPostgresql $postgres, ServicioAlcanceUsuario $alcanceUsuario): void
    {
        $this->postgres = $postgres;
        $this->setAlcanceUsuario($alcanceUsuario);
    }

    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio',
    ];

    private const DB_COLUMNS = [
        'Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Cap_Tot', 'Cap_Dic', 'Vigencia',
        'Imp_Res_Audi_Mes', 'Pagare_Fecha', 'Vta_Mes', 'Asam_Prog_Mes', 'Asam_Real_Mes',
    ];

    private const SORTABLE_COLUMNS = [
        'Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Factores', 'Detalle',
    ];

    public string $almacen = '';

    public string $nivel = '';

    public string $indicador = '';

    public string $tiendaSalud = '';

    public bool $showFactores = true;

    public bool $showDetalle = true;

    protected $queryString = [
        'almacen' => ['except' => ''],
        'nivel' => ['except' => ''],
        'indicador' => ['except' => ''],
        'tiendaSalud' => ['except' => ''],
        'showFactores' => ['except' => true],
        'showDetalle' => ['except' => true],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    protected function sortableColumns(): array
    {
        return self::SORTABLE_COLUMNS;
    }

    private function filters(): array
    {
        return [
            'almacen' => trim($this->almacen),
            'nivel' => $this->nivel,
            'indicador' => $this->indicador,
            'tienda_salud' => $this->tiendaSalud,
        ];
    }

    protected function filterProperties(): array
    {
        return ['almacen', 'nivel', 'indicador', 'tiendaSalud'];
    }

    protected function clearFilterValues(): void
    {
        $this->almacen = '';
        $this->nivel = '';
        $this->indicador = '';
        $this->tiendaSalud = '';
    }

    private function activeColumns(): array
    {
        $columns = ['Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio'];

        if ($this->showFactores) {
            $columns[] = 'Factores';
        }

        if ($this->showDetalle) {
            $columns[] = 'Detalle';
        }

        return $columns;
    }

    public function columnLabel(string $column): string
    {
        return [
            'Estado' => 'Estado',
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'Factores' => 'Factores',
            'Detalle' => 'Detalle',
        ][$column] ?? $column;
    }

    public function renderCell(string $column, array $store): string
    {
        $e = $store['_critico'] ?? [];

        if ($column === 'Estado') {
            $level = $e['level'] ?? 'verde';
            $count = $e['count'] ?? 0;
            $badge = IndicadorPresenter::levelBadge($level, $count);

            return '<span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold '.$badge['classes'].'">'.$badge['label'].'</span>';
        }

        if ($column === 'Nombre_Almacen') {
            return RenderTiendaPresentador::renderStoreName($store[$column] ?? '', ! empty($store['es_tienda_salud_bienestar']));
        }

        if ($column === 'No_Tienda_Actual') {
            $val = $store[$column] ?? '';

            return '<span class="font-mono text-gray-700 dark:text-gray-300 block text-center">'.($val ? number_format((float) $val) : '—').'</span>';
        }

        if ($column === 'Municipio') {
            return e($store[$column] ?: '—');
        }

        if ($column === 'Factores') {
            return implode(' ', array_map(function (string $key) use ($e): string {
                $active = ! empty($e['conditions'][$key]);
                $rawLabel = $e['labels'][$key]['label'] ?? IndicadorPresenter::factorLabel($key);
                $cleanLabel = IndicadorPresenter::cleanLabel($rawLabel);
                $title = $active ? '🔴 '.$cleanLabel : '⚪ '.$cleanLabel;

                if ($active) {
                    return '<span class="text-base cursor-help" title="'.e($title).'">🔴</span>';
                }

                return '<span class="text-base text-gray-300 cursor-help" title="'.e($title).'">⚪</span>';
            }, IndicadorPresenter::factorKeys()));
        }

        if ($column === 'Detalle') {
            if (empty($e['conditions']) || empty($e['labels'])) {
                return '<span class="text-gray-400 dark:text-gray-500 text-xs">Sin incidencias</span>';
            }

            $activeKeys = array_values(array_filter(IndicadorPresenter::factorKeys(), fn (string $k): bool => ! empty($e['conditions'][$k])));

            if (empty($activeKeys)) {
                return '<span class="text-gray-400 dark:text-gray-500 text-xs">Sin incidencias</span>';
            }

            $chips = array_map(function (string $k) use ($e): string {
                $info = $e['labels'][$k] ?? [];
                $style = IndicadorPresenter::factorStyle($k);
                $label = IndicadorPresenter::cleanLabel($info['label'] ?? $k);
                $detail = $info['detail'] ?? '';

                $html = '<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-lg border '.$style[0].'">';
                $html .= $style[1].' '.e($label);

                if ($detail !== '') {
                    $html .= '<span class="font-normal opacity-70 ml-0.5">'.e($detail).'</span>';
                }

                $html .= '</span>';

                return $html;
            }, $activeKeys);

            return '<div class="flex flex-wrap gap-1.5 max-w-md">'.implode('', $chips).'</div>';
        }

        return e($store[$column] ?? '');
    }

    public function isSortable(string $column): bool
    {
        return in_array($column, $this->sortableColumns(), true) && ! in_array($column, $this->excludedSortColumns(), true);
    }

    public function tableData(): array
    {
        $result = $this->postgres->obtenerCriticidadPaginada(
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
            'summary' => $result['summary'],
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
        return view('livewire.critical-stores-table');
    }
}

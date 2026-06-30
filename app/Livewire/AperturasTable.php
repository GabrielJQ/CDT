<?php

namespace App\Livewire;

use App\Presenters\RenderTiendaPresentador;
use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioFecha;
use App\Servicios\ServicioPostgresql;
use Carbon\Carbon;
use Livewire\Component;

class AperturasTable extends Component
{
    use ConTablaLivewire;

    protected ServicioPostgresql $postgres;

    protected ServicioFecha $fecha;

    public function boot(ServicioPostgresql $postgres, ServicioFecha $fecha, ServicioAlcanceUsuario $alcanceUsuario): void
    {
        $this->postgres = $postgres;
        $this->fecha = $fecha;
        $this->setAlcanceUsuario($alcanceUsuario);
    }

    private const COLUMNS = ['Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura'];

    private const SORTABLE_COLUMNS = ['Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura', '_fecha_apertura', '_antiguedad'];

    public string $almacen = '';

    public string $desde = '';

    public string $hasta = '';

    public string $tiendaSalud = '';

    public bool $showApertura = true;

    protected $queryString = [
        'almacen' => ['except' => ''],
        'desde' => ['except' => ''],
        'hasta' => ['except' => ''],
        'tiendaSalud' => ['except' => ''],
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
            'desde' => $this->fecha->parsear($this->desde)?->toDateString() ?? '',
            'hasta' => $this->fecha->parsear($this->hasta)?->toDateString() ?? '',
            'tienda_salud' => $this->tiendaSalud,
        ];
    }

    protected function filterProperties(): array
    {
        return ['almacen', 'desde', 'hasta', 'tiendaSalud'];
    }

    protected function clearFilterValues(): void
    {
        $this->almacen = '';
        $this->desde = '';
        $this->hasta = '';
        $this->tiendaSalud = '';
    }

    private function activeColumns(): array
    {
        $columns = ['Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio'];

        if ($this->showApertura) {
            $columns = array_merge($columns, ['_fecha_apertura', '_antiguedad']);
        }

        return $columns;
    }

    public function columnLabel(string $column): string
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'Localidad' => 'Localidad',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            '_fecha_apertura' => 'Apertura',
            '_antiguedad' => 'Antigüedad',
        ][$column] ?? $column;
    }

    public function ageBadge(?string $date): string
    {
        if (! $date) {
            return '<div class="text-center text-gray-400 dark:text-gray-500">—</div>';
        }

        $openedAt = Carbon::parse($date);
        $diffDays = (int) $openedAt->diffInDays(now(), false);
        $diffMonths = (int) floor($diffDays / 30);

        if ($diffDays <= 0) {
            $label = 'Hoy';
            $color = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        } elseif ($diffDays < 30) {
            $label = $diffDays.' día'.($diffDays > 1 ? 's' : '');
            $color = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        } elseif ($diffMonths < 12) {
            $label = $diffMonths.' mes'.($diffMonths > 1 ? 'es' : '');
            $color = $diffMonths <= 3 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
        } else {
            $years = (int) floor($diffMonths / 12);
            $label = $years.' año'.($years > 1 ? 's' : '');
            $color = 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200';
        }

        return '<div class="text-center"><span class="badge '.$color.'">'.$label.'</span></div>';
    }

    public function renderCell(string $column, array $store): string
    {
        if ($column === 'Nombre_Almacen') {
            return RenderTiendaPresentador::renderStoreName($store[$column] ?? '', ! empty($store['es_tienda_salud_bienestar']));
        }

        if (in_array($column, ['Localidad', 'Municipio'], true)) {
            return e($store[$column] ?: '—');
        }

        if ($column === 'No_Tienda_Actual') {
            return '<span class="font-mono text-gray-700 dark:text-gray-300 text-center block">'.e($store[$column] ?: '—').'</span>';
        }

        if ($column === '_fecha_apertura') {
            return '<div class="text-center font-mono text-gray-700 dark:text-gray-300">'.RenderTiendaPresentador::formatDate($store['_fecha_apertura'] ?? null).'</div>';
        }

        if ($column === '_antiguedad') {
            return $this->ageBadge($store['_fecha_apertura'] ?? null);
        }

        return e($store[$column] ?? '');
    }

    public function tableData(): array
    {
        $result = $this->postgres->obtenerAperturasPaginada(
            $this->regionFilters(),
            $this->filters(),
            $this->page,
            $this->perPage,
            self::COLUMNS,
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
        return view('livewire.aperturas-table');
    }
}

<?php

namespace App\Livewire;

use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioGeo;
use App\Servicios\ServicioPostgresql;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class MapaContent extends Component
{
    protected ServicioAlcanceUsuario $alcanceUsuario;

    protected ServicioPostgresql $postgres;

    protected ServicioGeo $geo;

    public function boot(ServicioAlcanceUsuario $alcanceUsuario, ServicioPostgresql $postgres, ServicioGeo $geo): void
    {
        $this->alcanceUsuario = $alcanceUsuario;
        $this->postgres = $postgres;
        $this->geo = $geo;
    }

    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado', 'Nombre_UniOpe', 'Nombre_Regional',
        'Latitud', 'Longitud', 'Vta_Mes', 'Cap_Tot',
    ];

    private const SORTABLE_COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado',
    ];

    private const EXCLUDED_SORT_COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio',
    ];

    public string $almacen = '';

    public string $estado_geo = '';

    public string $tiendaSalud = '';

    public ?string $sort = null;

    public string $direction = 'asc';

    public int $page = 1;

    public int $perPage = 50;

    public bool $showCritical = true;

    protected $queryString = [
        'almacen' => ['except' => ''],
        'estado_geo' => ['except' => ''],
        'tiendaSalud' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    private function regionFilters(): array
    {
        return $this->alcanceUsuario->filtroEfectivo(request());
    }

    private function filters(): array
    {
        return [
            'almacen' => $this->almacen,
            'estado_geo' => $this->estado_geo,
            'tienda_salud' => $this->tiendaSalud,
        ];
    }

    private function allStoresCached(): array
    {
        $regionFilters = $this->regionFilters();
        $cacheKey = 'mapa_all_'.md5(serialize($regionFilters).'_'.$this->tiendaSalud);

        return Cache::remember($cacheKey, 120, function () use ($regionFilters) {
            return $this->postgres->obtenerMapa(
                $regionFilters,
                ['tienda_salud' => $this->tiendaSalud],
                self::COLUMNS,
            );
        });
    }

    public function updated($property): void
    {
        if (in_array($property, ['almacen', 'estado_geo', 'tiendaSalud', 'perPage'], true)) {
            $this->page = 1;
        }

        if (in_array($property, ['almacen', 'estado_geo', 'tiendaSalud'], true)) {
            $this->dispatch('mapa-filters-updated',
                tienda_salud: $this->tiendaSalud,
                estado_geo: $this->estado_geo,
                almacen: $this->almacen,
            );
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
        $this->almacen = '';
        $this->estado_geo = '';
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

    public function columnLabel(string $column): string
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'Estado' => 'Estado',
        ][$column] ?? $column;
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

    public function renderCell(string $column, array $store): string
    {
        $val = $store[$column] ?? '';

        if ($column === 'Nombre_Almacen') {
            return $this->renderStoreName($val, ! empty($store['es_tienda_salud_bienestar']));
        }

        if ($column === 'No_Tienda_Actual') {
            return '<span class="font-mono text-gray-700 dark:text-gray-300 block text-center">'.($val ?: '—').'</span>';
        }

        if (in_array($column, ['Municipio', 'Estado'], true)) {
            return e($val ?: '—');
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

    private function geoMismatchLabel(array $stores, array $regionFilter): string
    {
        if (! empty($regionFilter['uo'])) {
            $uoName = '';
            foreach ($stores as $store) {
                $value = trim((string) ($store['Nombre_UniOpe'] ?? ''));
                if ($value !== '') {
                    $uoName = $value;

                    break;
                }
            }

            return $uoName !== '' ? 'No corresponde a '.$uoName : 'No corresponde a la UO filtrada';
        }

        if (! empty($regionFilter['region'])) {
            $regionName = '';
            foreach ($stores as $store) {
                $value = trim((string) ($store['Nombre_Regional'] ?? ''));
                if ($value !== '') {
                    $regionName = $value;

                    break;
                }
            }

            return $regionName !== '' ? 'No corresponde a '.$regionName : 'No corresponde a la region filtrada';
        }

        return 'No corresponde al estado registrado';
    }

    public function computed(): array
    {
        $allStores = $this->allStoresCached();

        $totalCount = count($allStores);

        $geoLabels = ServicioGeo::GEO_LABELS;
        $regionFilters = $this->regionFilters();
        $geoLabels['FUERA_ESTADO']['label'] = $this->geoMismatchLabel($allStores, $regionFilters);

        $stats = $this->geo->calcularStats($allStores);

        $filteredCount = $totalCount;
        if ($this->almacen !== '') {
            $filteredCount = $this->postgres->contarMapaFiltrado($regionFilters, $this->filters());
        }

        $incidencias = $this->postgres->obtenerIncidenciasMapaPaginadas(
            $regionFilters,
            ['almacen' => $this->almacen, 'tienda_salud' => $this->tiendaSalud],
            self::COLUMNS,
            $this->sort,
            $this->direction,
            $this->page,
            $this->perPage,
        );

        $criticalesPage = $incidencias['items'];
        $totalCriticales = $incidencias['total'];
        $totalPages = max(1, (int) ceil($totalCriticales / $this->perPage));
        $this->page = min($this->page, $totalPages);
        $offset = ($this->page - 1) * $this->perPage;

        return [
            'stores' => $allStores,
            'criticales' => $criticalesPage,
            'criticalesTotal' => $totalCriticales,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'filteredCount' => $filteredCount,
            'stats' => $stats,
            'geoLabels' => $geoLabels,
            'geoMismatchLabel' => $geoLabels['FUERA_ESTADO']['label'],
            'updatedAt' => now()->toDateTimeString(),
            'from' => $totalCriticales > 0 ? $offset + 1 : 0,
            'to' => min($offset + $this->perPage, $totalCriticales),
        ];
    }

    public function render()
    {
        return view('livewire.mapa-content');
    }
}

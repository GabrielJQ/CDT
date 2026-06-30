<?php

namespace App\Livewire;

use App\Servicios\ServicioAlcanceUsuario;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CasaXCasaDirectorio extends Component
{
    protected ServicioAlcanceUsuario $alcanceUsuario;

    public string $estado = '';

    public string $uo = '';

    public string $estatus = '';

    public string $buscar = '';

    public ?string $sort = null;

    public string $direction = 'asc';

    public int $page = 1;

    public int $perPage = 50;

    protected $queryString = [
        'estado' => ['except' => ''],
        'uo' => ['except' => ''],
        'estatus' => ['except' => ''],
        'buscar' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    private const SORTABLE_COLUMNS = ['no_tienda', 'almacen', 'municipio', 'estado', 'unidad_operativa', 'encargado', 'tipo_anaquel', 'estatus'];

    private const EXCLUDED_SORT = ['no_tienda', 'almacen', 'municipio'];

    private const DISPLAY_COLUMNS = ['no_tienda', 'almacen', 'municipio', 'estado', 'unidad_operativa', 'encargado', 'tipo_anaquel', 'estatus'];

    public array $estados = [];

    public array $unidadesOperativas = [];

    public array $estatusList = [];

    public int $totalCount = 0;

    private function uoFilter(): array
    {
        $filtro = $this->alcanceUsuario->filtroEfectivo(request());
        $region = $filtro['region'];
        $uo = $filtro['uo'];

        if ($region === '__NO_ACCESS__' || $uo === '__NO_ACCESS__') {
            return ['__NO_ACCESS__'];
        }

        if (empty($region) && empty($uo)) {
            return [];
        }

        $conn = DB::connection(config('database.imports'));

        $query = $conn->table('tiendas_casa_x_casa')
            ->join('tiendas', function ($join) use ($conn) {
                $join->on('tiendas_casa_x_casa.no_tienda', '=', $conn->raw('"tiendas"."No_Tienda_Actual"'))
                    ->on('tiendas_casa_x_casa.almacen', '=', $conn->raw('"tiendas"."Nombre_Almacen"'))
                    ->on('tiendas_casa_x_casa.estado', '=', $conn->raw('"tiendas"."Estado"'))
                    ->on('tiendas_casa_x_casa.municipio', '=', $conn->raw('"tiendas"."Municipio"'));
            })
            ->where('tiendas.es_activo', true)
            ->where('tiendas_casa_x_casa.es_activo', true);

        if (! empty($uo)) {
            $query->where('tiendas.Clave_UniOpe', $uo);
            if (! empty($region)) {
                $query->where('tiendas.Clave_Regional', $region);
            }
        } else {
            $query->where('tiendas.Clave_Regional', $region);
        }

        return $query->distinct()->pluck('tiendas_casa_x_casa.unidad_operativa')->toArray();
    }

    private function baseQuery()
    {
        $conn = DB::connection(config('database.imports'));
        $query = $conn->table('tiendas_casa_x_casa')->where('es_activo', true);

        $uoFilter = $this->uoFilter();
        if (! empty($uoFilter)) {
            $query->whereIn('unidad_operativa', $uoFilter);
        }

        if ($this->estado !== '') {
            $query->where('estado', $this->estado);
        }
        if ($this->uo !== '') {
            $query->where('unidad_operativa', $this->uo);
        }
        if ($this->estatus !== '') {
            $query->where('estatus', $this->estatus);
        }
        if ($this->buscar !== '') {
            $buscar = $this->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('almacen', 'ILIKE', "%{$buscar}%")
                    ->orWhere('no_tienda', 'ILIKE', "%{$buscar}%")
                    ->orWhere('municipio', 'ILIKE', "%{$buscar}%")
                    ->orWhere('encargado', 'ILIKE', "%{$buscar}%");
            });
        }

        return $query;
    }

    private function sortInput(): array
    {
        $direction = $this->direction === 'desc' ? 'desc' : 'asc';

        if (! $this->sort || ! in_array($this->sort, self::SORTABLE_COLUMNS, true) || in_array($this->sort, self::EXCLUDED_SORT, true)) {
            return ['column' => null, 'direction' => $direction];
        }

        return ['column' => $this->sort, 'direction' => $direction];
    }

    public function boot(ServicioAlcanceUsuario $alcanceUsuario): void
    {
        $this->alcanceUsuario = $alcanceUsuario;
    }

    public function mount(): void
    {
        $conn = DB::connection(config('database.imports'));
        $base = $conn->table('tiendas_casa_x_casa')->where('es_activo', true);
        $uoFilter = $this->uoFilter();
        if (! empty($uoFilter)) {
            $base->whereIn('unidad_operativa', $uoFilter);
        }

        $this->estados = (clone $base)->select('estado')->distinct()->orderBy('estado')->pluck('estado')->toArray();
        $this->unidadesOperativas = (clone $base)->select('unidad_operativa')->distinct()->orderBy('unidad_operativa')->pluck('unidad_operativa')->toArray();
        $this->estatusList = (clone $base)->whereNotNull('estatus')->select('estatus')->distinct()->orderBy('estatus')->pluck('estatus')->toArray();
    }

    public function updated($property): void
    {
        if (in_array($property, ['estado', 'uo', 'estatus', 'buscar', 'perPage'], true)) {
            $this->page = 1;
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true) || in_array($column, self::EXCLUDED_SORT, true)) {
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
        $this->estado = '';
        $this->uo = '';
        $this->estatus = '';
        $this->buscar = '';
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
            'no_tienda' => 'Tienda',
            'almacen' => 'Almacén',
            'municipio' => 'Municipio',
            'estado' => 'Estado',
            'unidad_operativa' => 'U. Operativa',
            'encargado' => 'Encargado',
            'tipo_anaquel' => 'Anaquel',
            'estatus' => 'Estatus',
        ][$column] ?? $column;
    }

    public function sortArrow(string $column): string
    {
        if (in_array($column, self::EXCLUDED_SORT, true)) {
            return '';
        }

        if ($this->sort !== $column) {
            return '↕';
        }

        return $this->direction === 'asc' ? '▲' : '▼';
    }

    public function tableData(): array
    {
        $query = $this->baseQuery();
        $this->totalCount = (clone $query)->count();

        $sort = $this->sortInput();
        if ($sort['column'] !== null) {
            $query->orderBy($sort['column'], $sort['direction'])->orderBy('id');
        } else {
            $query->orderBy('estado')->orderBy('municipio');
        }

        $totalPages = max(1, (int) ceil($this->totalCount / $this->perPage));
        $this->page = min($this->page, $totalPages);

        $stores = (clone $query)
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get()
            ->map(fn ($item) => (array) $item)
            ->all();

        return [
            'stores' => $stores,
            'totalCount' => $this->totalCount,
            'totalPages' => $totalPages,
            'from' => $this->totalCount > 0 ? (($this->page - 1) * $this->perPage) + 1 : 0,
            'to' => min($this->page * $this->perPage, $this->totalCount),
            'columns' => self::DISPLAY_COLUMNS,
        ];
    }

    public function render()
    {
        return view('livewire.casa-x-casa-directorio');
    }
}

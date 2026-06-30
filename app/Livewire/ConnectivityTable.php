<?php

namespace App\Livewire;

use App\Presenters\RenderTiendaPresentador;
use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioPostgresql;
use Livewire\Component;

class ConnectivityTable extends Component
{
    use ConTablaLivewire;

    protected ServicioPostgresql $postgres;

    public function boot(ServicioPostgresql $postgres, ServicioAlcanceUsuario $alcanceUsuario): void
    {
        $this->postgres = $postgres;
        $this->setAlcanceUsuario($alcanceUsuario);
    }

    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
    ];

    public string $almacen = '';

    public string $telefono = '';

    public string $senial = '';

    public string $compania = '';

    public string $internet = '';

    public string $tiendaSalud = '';

    public bool $showConnectivity = true;

    protected $queryString = [
        'almacen' => ['except' => ''],
        'telefono' => ['except' => ''],
        'senial' => ['except' => ''],
        'compania' => ['except' => ''],
        'internet' => ['except' => ''],
        'tiendaSalud' => ['except' => ''],
        'sort' => ['except' => null],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['as' => 'per_page', 'except' => 50],
    ];

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return self::COLUMNS;
    }

    /** @return array<string, string> */
    private function filters(): array
    {
        return [
            'almacen' => trim($this->almacen),
            'telefono' => $this->telefono,
            'senial' => $this->senial,
            'compania' => $this->compania,
            'internet' => $this->internet,
            'tienda_salud' => $this->tiendaSalud,
        ];
    }

    /** @return list<string> */
    protected function filterProperties(): array
    {
        return ['almacen', 'telefono', 'senial', 'compania', 'internet', 'tiendaSalud'];
    }

    protected function clearFilterValues(): void
    {
        $this->almacen = '';
        $this->telefono = '';
        $this->senial = '';
        $this->compania = '';
        $this->internet = '';
        $this->tiendaSalud = '';
    }

    /** @return array<int, string> */
    private function activeColumns(): array
    {
        $columns = ['Nombre_Almacen', 'No_Tienda_Actual', 'Municipio'];

        if ($this->showConnectivity) {
            $columns = array_merge($columns, ['TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET']);
        }

        return $columns;
    }

    public function columnLabel(string $column): string
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'TELEFONIA' => '📞 Teléfono fijo',
            'Señal de celular' => '📱 Señal Celular',
            'Compañía' => 'Compañía',
            'INTERNET' => '🌐 Internet',
        ][$column] ?? $column;
    }

    public function renderCell(string $column, array $store): string
    {
        if ($column === 'Nombre_Almacen') {
            return RenderTiendaPresentador::renderStoreName($store[$column] ?? '', ! empty($store['es_tienda_salud_bienestar']));
        }

        if ($column === 'No_Tienda_Actual') {
            $number = $store[$column] ?? '';

            return '<span class="font-mono text-gray-700 dark:text-gray-300">'.($number ? number_format((float) $number) : '—').'</span>';
        }

        if ($column === 'Municipio') {
            return e($store[$column] ?: '—');
        }

        if (in_array($column, ['TELEFONIA', 'Señal de celular', 'INTERNET'], true)) {
            return '<div class="text-center">'.RenderTiendaPresentador::yesNoBadge($store[$column] ?? '').'</div>';
        }

        if ($column === 'Compañía') {
            $company = trim($store[$column] ?? '');

            return '<span class="text-gray-700 dark:text-gray-300">'.e($company ?: '—').'</span>';
        }

        return e($store[$column] ?? '');
    }

    /** @return array<string, mixed> */
    public function tableData(): array
    {
        $result = $this->postgres->obtenerConectividadPaginada(
            $this->regionFilters(),
            $this->filters(),
            $this->page,
            $this->perPage,
            $this->sortInput(),
        );

        $totalPages = max(1, (int) ceil(($result['filtered'] ?? 0) / $this->perPage));
        $this->page = min($this->page, $totalPages);

        return [
            'stores' => $result['rows'],
            'kpis' => $result['kpis'],
            'companias' => $result['companias'],
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
        return view('livewire.connectivity-table');
    }
}

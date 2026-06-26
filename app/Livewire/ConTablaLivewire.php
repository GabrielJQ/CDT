<?php

namespace App\Livewire;

use App\Servicios\ServicioAlcanceUsuario;

trait ConTablaLivewire
{
    public ?string $sort = null;

    public string $direction = 'asc';

    public int $page = 1;

    public int $perPage = 50;

    /** @return array{region: string, uo: string} */
    protected function regionFilters(): array
    {
        return app(ServicioAlcanceUsuario::class)->filtroEfectivo(request());
    }

    /** @return list<string> */
    abstract protected function sortableColumns(): array;

    /** @return list<string> */
    protected function excludedSortColumns(): array
    {
        return ['Nombre_Almacen', 'No_Tienda_Actual', 'Localidad', 'Municipio'];
    }

    /** @return array{column: string|null, direction: string} */
    protected function sortInput(): array
    {
        $direction = $this->direction === 'desc' ? 'desc' : 'asc';

        if (! $this->sort || ! in_array($this->sort, $this->sortableColumns(), true) || in_array($this->sort, $this->excludedSortColumns(), true)) {
            return ['column' => null, 'direction' => $direction];
        }

        return ['column' => $this->sort, 'direction' => $direction];
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, $this->sortableColumns(), true) || in_array($column, $this->excludedSortColumns(), true)) {
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

    public function sortArrow(string $column): string
    {
        if (in_array($column, $this->excludedSortColumns(), true)) {
            return '';
        }

        if ($this->sort !== $column) {
            return '↕';
        }

        return $this->direction === 'asc' ? '▲' : '▼';
    }

    /** @return list<string> */
    abstract protected function filterProperties(): array;

    public function updated($property): void
    {
        if (in_array($property, array_merge($this->filterProperties(), ['perPage']), true)) {
            $this->page = 1;
        }
    }

    abstract protected function clearFilterValues(): void;

    public function clearFilters(): void
    {
        $this->clearFilterValues();
        $this->sort = null;
        $this->direction = 'asc';
        $this->page = 1;
    }

    protected function buildExportUrl(string $route, array $extra = []): string
    {
        return url($route.'?'.http_build_query(array_filter(
            array_merge($extra, [
                'sort' => $this->sort,
                'direction' => $this->direction,
                'per_page' => $this->perPage,
                'export' => 'csv',
            ]),
            fn ($value) => $value !== null && $value !== '',
        )));
    }
}

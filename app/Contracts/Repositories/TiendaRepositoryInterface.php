<?php

namespace App\Contracts\Repositories;

use App\Models\Region;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface TiendaRepositoryInterface
{
    public function find(int $id): ?Tienda;

    public function findActive(int $id): ?Tienda;

    /** @param array{region?: string, uo?: string} $regionFilters */
    public function getActive(array $regionFilters = [], array $columns = ['*']): Collection;

    /** @param array{region?: string, uo?: string} $regionFilters */
    public function countActive(array $regionFilters = []): int;

    /** @return array<int, string> */
    public function getCompanias(array $regionFilters = []): array;

    /** @return array<int, array{clave: string, nombre: string, total: int, almacenes: int, uos: array<int, array{clave: string, nombre: string, total: int, almacenes: int}>}> */
    public function getJerarquiaRegional(?User $user = null): array;

    public function getJerarquiaOperativa(): array;

    public function paginateFiltered(
        Builder $query,
        int $page,
        int $perPage,
        array $sort = []
    ): LengthAwarePaginator;

    public function applyRegionScope(Builder $query, array $regionFilters): Builder;

    public function yieldForExport(
        string $module,
        array $regionFilters,
        array $filters,
        array $columns
    ): \Generator;
}

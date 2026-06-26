<?php

namespace App\Repositories;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Models\Tienda;
use App\Models\User;
use App\Servicios\ServicioPeriodosImportacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TiendaPostgresqlRepository implements TiendaRepositoryInterface
{
    public function find(int $id): ?Tienda
    {
        return Tienda::query()->find($id);
    }

    public function findActive(int $id): ?Tienda
    {
        return Tienda::query()->activo()->find($id);
    }

    public function getActive(array $regionFilters = [], array $columns = ['*']): Collection
    {
        return $this->applyRegionScope(Tienda::query()->activo(), $regionFilters)
            ->get($columns);
    }

    public function countActive(array $regionFilters = []): int
    {
        return $this->applyRegionScope(Tienda::query()->activo(), $regionFilters)
            ->count();
    }

    public function getCompanias(array $regionFilters = []): array
    {
        return $this->applyRegionScope(Tienda::query()->activo(), $regionFilters)
            ->whereNotNull('Compañía')
            ->where('Compañía', '!=', '')
            ->whereRaw('UPPER(TRIM("Compañía")) NOT IN (?, ?)', ['SIN DATO', 'NINGUNO'])
            ->distinct()
            ->orderBy('Compañía')
            ->pluck('Compañía')
            ->toArray();
    }

    public function getJerarquiaRegional(?User $user = null): array
    {
        $query = Tienda::query()->activo();

        $periodo = app(ServicioPeriodosImportacion::class)
            ->obtenerActivo('regular', $user);
        if ($periodo !== null) {
            $query->where('periodo_importacion_id', $periodo->id);
        }

        $rows = $query
            ->whereNotNull('Nombre_Regional')
            ->whereRaw('TRIM("Nombre_Regional") != ?', [''])
            ->selectRaw('"Clave_Regional", "Nombre_Regional", "Clave_UniOpe", "Nombre_UniOpe", COUNT(*) as total, COUNT(DISTINCT "Nombre_Almacen") as almacenes')
            ->groupBy('Clave_Regional', 'Nombre_Regional', 'Clave_UniOpe', 'Nombre_UniOpe')
            ->orderBy('Clave_Regional')
            ->orderBy('Clave_UniOpe')
            ->get();

        $jerarquia = [];
        foreach ($rows as $row) {
            $claveReg = $row->Clave_Regional;
            if (! isset($jerarquia[$claveReg])) {
                $jerarquia[$claveReg] = [
                    'clave' => $claveReg,
                    'nombre' => $row->Nombre_Regional,
                    'total' => 0,
                    'almacenes' => 0,
                    'uos' => [],
                ];
            }
            $jerarquia[$claveReg]['total'] += (int) $row->total;
            $jerarquia[$claveReg]['almacenes'] += (int) $row->almacenes;
            $jerarquia[$claveReg]['uos'][] = [
                'clave' => $row->Clave_UniOpe,
                'nombre' => $row->Nombre_UniOpe,
                'total' => (int) $row->total,
                'almacenes' => (int) $row->almacenes,
            ];
        }

        return array_values($jerarquia);
    }

    public function getJerarquiaOperativa(): array
    {
        $rows = Tienda::query()
            ->activo()
            ->select('Clave_Regional', 'Nombre_Regional', 'Clave_UniOpe', 'Nombre_UniOpe')
            ->distinct()
            ->orderBy('Clave_Regional')
            ->orderBy('Clave_UniOpe')
            ->get();

        $regions = [];
        $regionNames = [];
        $uos = [];

        foreach ($rows as $row) {
            $clave = $row->Clave_Regional;
            $nombre = $row->Nombre_Regional;
            $uoClave = $row->Clave_UniOpe;
            $uoNombre = $row->Nombre_UniOpe;

            if (! in_array($clave, $regions, true)) {
                $regions[] = $clave;
                $regionNames[$clave] = $nombre;
            }

            if (! isset($uos[$clave])) {
                $uos[$clave] = [];
            }

            $uoKey = $uoClave;
            if (! isset($uos[$clave][$uoKey])) {
                $uos[$clave][$uoKey] = $uoNombre;
            }
        }

        return compact('regions', 'regionNames', 'uos');
    }

    public function paginateFiltered(
        Builder $query,
        int $page,
        int $perPage,
        array $sort = []
    ): LengthAwarePaginator {
        if (! empty($sort['column']) && ! empty($sort['direction'])) {
            $query->orderBy($sort['column'], $sort['direction']);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function applyRegionScope(Builder $query, array $regionFilters): Builder
    {
        return $query
            ->when(
                ! empty($regionFilters['region']),
                fn (Builder $q) => $q->regional($regionFilters['region'])
            )
            ->when(
                ! empty($regionFilters['uo']),
                fn (Builder $q) => $q->unidadOperativa($regionFilters['uo'])
            );
    }

    public function yieldForExport(
        string $module,
        array $regionFilters,
        array $filters,
        array $columns
    ): \Generator {
        $query = Tienda::query()->activo();
        $query = $this->applyRegionScope($query, $regionFilters);
        $query = $this->applyModuleFilters($query, $module, $filters);

        foreach ($query->cursor() as $tienda) {
            $row = array_intersect_key($tienda->getAttributes(), array_flip($columns));

            yield $row;
        }
    }

    private function applyModuleFilters(Builder $query, string $module, array $filters): Builder
    {
        return match ($module) {
            'conectividad' => $query
                ->tap(fn (Builder $q) => $q->conectividad($filters))
                ->tap(fn (Builder $q) => $this->applyTextFilter($q, $filters)),
            'directorio' => $query
                ->directorio($filters, $this->getDirectorioTrackedColumns()),
            'criticidad' => $query
                ->criticidad($filters)
                ->tap(fn (Builder $q) => $this->applyTextFilter($q, $filters)),
            'auditoria' => $query
                ->auditoria($filters)
                ->tap(fn (Builder $q) => $this->applyTextFilter($q, $filters)),
            'aperturas' => $query
                ->aperturas($filters)
                ->tap(fn (Builder $q) => $this->applyTextFilter($q, $filters)),
            'mapa' => $query
                ->mapa($filters)
                ->tap(fn (Builder $q) => $this->applyTextFilter($q, $filters)),
            default => $query,
        };
    }

    private function applyTextFilter(Builder $query, array $filters): Builder
    {
        if (($filters['almacen'] ?? '') !== '') {
            $query->almacen($filters['almacen']);
        }

        if (($filters['tienda_salud'] ?? '') !== '') {
            $query->tiendaSalud($filters['tienda_salud']);
        }

        return $query;
    }

    /** @return string[] */
    private function getDirectorioTrackedColumns(): array
    {
        return [
            'TELEFONIA', 'CORREO', 'Señal de celular', 'Compañía', 'INTERNET',
            'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic',
            'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia', 'Fch_Audit',
            'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes', 'Latitud', 'Longitud',
            'Direccion', 'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA',
            'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
        ];
    }
}

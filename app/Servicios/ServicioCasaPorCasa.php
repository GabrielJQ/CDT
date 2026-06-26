<?php

namespace App\Servicios;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicioCasaPorCasa
{
    private const NO_ACCESS = '__NO_ACCESS__';

    public function resolveUoFilter(?Request $request = null): array
    {
        $request ??= request();
        $filtro = app(ServicioAlcanceUsuario::class)->filtroEfectivo($request);
        $region = $filtro['region'];
        $uo = $filtro['uo'];

        if ($region === self::NO_ACCESS || $uo === self::NO_ACCESS) {
            return [self::NO_ACCESS];
        }

        if (empty($region) && empty($uo)) {
            return [];
        }

        $conn = DB::connection('pgsql_imports');

        $query = $conn->table('tiendas_casa_x_casa')
            ->join('tiendas', function ($join) {
                $join->on('tiendas_casa_x_casa.no_tienda', '=', 'tiendas.No_Tienda_Actual')
                    ->on('tiendas_casa_x_casa.almacen', '=', 'tiendas.Nombre_Almacen')
                    ->on('tiendas_casa_x_casa.estado', '=', 'tiendas.Estado')
                    ->on('tiendas_casa_x_casa.municipio', '=', 'tiendas.Municipio');
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

    public function activeCxcQuery(): Builder
    {
        return DB::connection('pgsql_imports')
            ->table('tiendas_casa_x_casa')
            ->where('es_activo', true);
    }

    public function applyUoFilter(Builder $query, array $uoFilter): void
    {
        if (! empty($uoFilter)) {
            $query->whereIn('unidad_operativa', $uoFilter);
        }
    }

    public function dashboardData(?array $uoFilter = null): array
    {
        $uoFilter ??= $this->resolveUoFilter();

        $q = $this->activeCxcQuery();
        $this->applyUoFilter($q, $uoFilter);

        $total = (clone $q)->count();

        $porEstatus = (clone $q)
            ->select('estatus', DB::raw('count(*) as total'))
            ->whereNotNull('estatus')
            ->groupBy('estatus')
            ->orderByDesc('total')
            ->get();

        $anaqueles = [
            'instalados' => (clone $q)->where('anaqueles_instalados', true)->count(),
            'pendientes' => (clone $q)->where('anaqueles_instalados', false)->count(),
        ];

        $aviso = [
            'con_aviso' => (clone $q)->where('aviso_funcionamiento', true)->count(),
            'sin_aviso' => (clone $q)->where('aviso_funcionamiento', false)->count(),
        ];

        $topUos = (clone $q)
            ->select('unidad_operativa', DB::raw('count(*) as total'))
            ->groupBy('unidad_operativa')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $porTipoAnaquel = (clone $q)
            ->select('tipo_anaquel', DB::raw('count(*) as total'))
            ->whereNotNull('tipo_anaquel')
            ->groupBy('tipo_anaquel')
            ->orderByDesc('total')
            ->get();

        $cruce = $this->calcularCruce($uoFilter);

        return compact('total', 'porEstatus', 'anaqueles', 'aviso', 'topUos', 'porTipoAnaquel', 'cruce');
    }

    public function calcularCruce(array $uoFilter = []): array
    {
        $conn = DB::connection('pgsql_imports');

        $enTiendas = $conn->table('tiendas_casa_x_casa', 'cxc')
            ->join('tiendas as t', function ($join) {
                $join->on('t.No_Tienda_Actual', '=', 'cxc.no_tienda')
                    ->on('t.Nombre_Almacen', '=', 'cxc.almacen')
                    ->on('t.Estado', '=', 'cxc.estado')
                    ->on('t.Municipio', '=', 'cxc.municipio')
                    ->where('t.es_activo', true);
            })
            ->where('cxc.es_activo', true);

        if (! empty($uoFilter)) {
            $enTiendas->whereIn('cxc.unidad_operativa', $uoFilter);
        }

        $soloCxc = $conn->table('tiendas_casa_x_casa', 'cxc')
            ->leftJoin('tiendas as t', function ($join) {
                $join->on('t.No_Tienda_Actual', '=', 'cxc.no_tienda')
                    ->on('t.Nombre_Almacen', '=', 'cxc.almacen')
                    ->on('t.Estado', '=', 'cxc.estado')
                    ->on('t.Municipio', '=', 'cxc.municipio')
                    ->where('t.es_activo', true);
            })
            ->where('cxc.es_activo', true)
            ->whereNull('t.id');

        if (! empty($uoFilter)) {
            $soloCxc->whereIn('cxc.unidad_operativa', $uoFilter);
        }

        return [
            'enTiendas' => $enTiendas->count(),
            'soloCxc' => $soloCxc->count(),
        ];
    }

    public function directorioQuery(array $uoFilter = []): Builder
    {
        $query = $this->activeCxcQuery();
        $this->applyUoFilter($query, $uoFilter);

        return $query;
    }

    public function directorioFilterOptions(array $uoFilter = []): array
    {
        $base = $this->activeCxcQuery();
        $this->applyUoFilter($base, $uoFilter);

        return [
            'estados' => (clone $base)->select('estado')->distinct()->orderBy('estado')->pluck('estado'),
            'unidadesOperativas' => (clone $base)->select('unidad_operativa')->distinct()->orderBy('unidad_operativa')->pluck('unidad_operativa'),
            'estatusList' => (clone $base)->select('estatus')->whereNotNull('estatus')->distinct()->orderBy('estatus')->pluck('estatus'),
        ];
    }

    public function mapaQuery(array $uoFilter = []): Builder
    {
        $query = DB::connection('pgsql_imports')
            ->table('tiendas_casa_x_casa')
            ->select([
                'id', 'almacen', 'no_tienda', 'municipio', 'estado', 'unidad_operativa',
                'tipo_anaquel', 'anaqueles_instalados', 'latitud', 'longitud',
            ])
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('latitud', '!=', 0)
            ->where('longitud', '!=', 0)
            ->where('es_activo', true);

        $this->applyUoFilter($query, $uoFilter);

        return $query;
    }

    public function findStore(int $id, array $uoFilter = []): ?object
    {
        $query = $this->activeCxcQuery()->where('id', $id);
        $this->applyUoFilter($query, $uoFilter);

        return $query->first();
    }

    public function cruceIndividual(object $store): ?object
    {
        $rows = DB::connection('pgsql_imports')
            ->table('tiendas')
            ->select([
                'No_Tienda_Actual', 'Nombre_Almacen', 'Estado', 'Municipio',
                'Fecha_Apertura', 'TELEFONIA', 'INTERNET', 'Señal de celular',
                'Compañía', 'Cap_Tot', 'Fch_Audit', 'Vigencia',
            ])
            ->where('No_Tienda_Actual', $store->no_tienda)
            ->where('Nombre_Almacen', $store->almacen)
            ->where('Estado', $store->estado)
            ->where('Municipio', $store->municipio)
            ->where('es_activo', true)
            ->limit(1)
            ->get();

        return $rows[0] ?? null;
    }

    public function applyNumericBounds(Builder $query, Request $request, string $latColumn, string $lonColumn): void
    {
        $values = [];
        foreach (['north', 'south', 'east', 'west'] as $key) {
            $value = $request->query($key);
            if (! is_numeric($value)) {
                return;
            }
            $values[$key] = (float) $value;
        }

        $north = min(90, $values['north']);
        $south = max(-90, $values['south']);
        $east = min(180, $values['east']);
        $west = max(-180, $values['west']);

        $query->whereBetween($latColumn, [min($south, $north), max($south, $north)]);
        if ($west <= $east) {
            $query->whereBetween($lonColumn, [$west, $east]);
        } else {
            $query->where(function ($query) use ($lonColumn, $west, $east) {
                $query->whereBetween($lonColumn, [$west, 180])->orWhereBetween($lonColumn, [-180, $east]);
            });
        }
    }
}

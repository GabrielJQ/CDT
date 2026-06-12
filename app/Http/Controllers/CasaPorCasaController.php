<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CasaPorCasaController extends Controller
{
    private function resolveUoFilter(): array
    {
        $filter = $this->applyRegionFilter();
        $region = $filter['region'] ?? '';
        $uo = $filter['uo'] ?? '';

        if (empty($region) && empty($uo)) {
            return [];
        }

        $conn = DB::connection('pgsql_imports');

        if (! empty($uo)) {
            $names = $conn->table('tiendas')
                ->where('Clave_UniOpe', $uo)
                ->distinct()
                ->pluck('Nombre_UniOpe')
                ->toArray();
        } else {
            $names = $conn->table('tiendas')
                ->where('Clave_Regional', $region)
                ->distinct()
                ->pluck('Nombre_UniOpe')
                ->toArray();
        }

        return array_filter($names);
    }

    public function dashboard()
    {
        $conn = DB::connection('pgsql_imports');
        $uoFilter = $this->resolveUoFilter();

        $query = $conn->table('tiendas_casa_x_casa');
        if (! empty($uoFilter)) {
            $query->whereIn('unidad_operativa', $uoFilter);
        }
        $total = (clone $query)->count();

        $porEstatus = (clone $query)
            ->select('estatus', DB::raw('count(*) as total'))
            ->whereNotNull('estatus')
            ->groupBy('estatus')
            ->orderByDesc('total')
            ->get();

        $anaqueles = [
            'instalados' => (clone $query)->where('anaqueles_instalados', true)->count(),
            'pendientes' => (clone $query)->where('anaqueles_instalados', false)->count(),
        ];

        $aviso = [
            'con_aviso' => (clone $query)->where('aviso_funcionamiento', true)->count(),
            'sin_aviso' => (clone $query)->where('aviso_funcionamiento', false)->count(),
        ];

        $topUos = (clone $query)
            ->select('unidad_operativa', DB::raw('count(*) as total'))
            ->groupBy('unidad_operativa')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $porTipoAnaquel = (clone $query)
            ->select('tipo_anaquel', DB::raw('count(*) as total'))
            ->whereNotNull('tipo_anaquel')
            ->groupBy('tipo_anaquel')
            ->orderByDesc('total')
            ->get();

        $cruce = $this->calcularCruce($conn, $uoFilter);

        return view('casa-x-casa.dashboard', compact(
            'total', 'porEstatus', 'anaqueles', 'aviso',
            'topUos', 'porTipoAnaquel', 'cruce',
        ));
    }

    private function whereUoFilterSql(array $uoFilter, string $alias = ''): string
    {
        if (empty($uoFilter)) {
            return '';
        }

        $escaped = array_map(fn ($v) => "'".str_replace("'", "''", $v)."'", $uoFilter);
        $prefix = $alias ? $alias.'.' : '';

        return 'AND '.$prefix.'unidad_operativa IN ('.implode(', ', $escaped).')';
    }

    public function directorio(Request $request)
    {
        $conn = DB::connection('pgsql_imports');
        $uoFilter = $this->resolveUoFilter();

        $query = $conn->table('tiendas_casa_x_casa');
        if (! empty($uoFilter)) {
            $query->whereIn('unidad_operativa', $uoFilter);
        }

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }
        if ($uo = $request->query('uo')) {
            $query->where('unidad_operativa', $uo);
        }
        if ($estatus = $request->query('estatus')) {
            $query->where('estatus', $estatus);
        }
        if ($buscar = $request->query('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('almacen', 'ILIKE', "%{$buscar}%")
                    ->orWhere('no_tienda', 'ILIKE', "%{$buscar}%")
                    ->orWhere('municipio', 'ILIKE', "%{$buscar}%")
                    ->orWhere('encargado', 'ILIKE', "%{$buscar}%");
            });
        }

        $totalCount = $query->count();
        $stores = $query->orderBy('estado')->orderBy('municipio')->paginate(50);

        $baseQuery = $conn->table('tiendas_casa_x_casa');
        if (! empty($uoFilter)) {
            $baseQuery->whereIn('unidad_operativa', $uoFilter);
        }
        $estados = (clone $baseQuery)->select('estado')
            ->distinct()->orderBy('estado')->pluck('estado');
        $unidadesOperativas = (clone $baseQuery)->select('unidad_operativa')
            ->distinct()->orderBy('unidad_operativa')->pluck('unidad_operativa');
        $estatusList = (clone $baseQuery)->select('estatus')
            ->whereNotNull('estatus')->distinct()->orderBy('estatus')->pluck('estatus');

        return view('casa-x-casa.directorio', compact(
            'stores', 'totalCount', 'estados', 'unidadesOperativas', 'estatusList',
        ));
    }

    public function mapa()
    {
        $conn = DB::connection('pgsql_imports');
        $uoFilter = $this->resolveUoFilter();

        $baseQuery = $conn->table('tiendas_casa_x_casa');
        if (! empty($uoFilter)) {
            $baseQuery->whereIn('unidad_operativa', $uoFilter);
        }

        $conCoordenadas = (clone $baseQuery)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('latitud', '!=', 0)
            ->where('longitud', '!=', 0)
            ->count();

        $totalCount = (clone $baseQuery)->count();

        return view('casa-x-casa.mapa', compact('totalCount', 'conCoordenadas'));
    }

    public function mapaData(Request $request)
    {
        $conn = DB::connection('pgsql_imports');
        $uoFilter = $this->resolveUoFilter();

        $query = $conn->table('tiendas_casa_x_casa')
            ->select([
                'id', 'almacen', 'no_tienda', 'municipio', 'estado', 'unidad_operativa',
                'tipo_anaquel', 'anaqueles_instalados', 'latitud', 'longitud',
            ])
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('latitud', '!=', 0)
            ->where('longitud', '!=', 0);

        if (! empty($uoFilter)) {
            $query->whereIn('unidad_operativa', $uoFilter);
        }

        $this->applyNumericBounds($query, $request, 'latitud', 'longitud');

        $stores = $query->orderBy('id')->limit(3000)->get();

        return response()->json(['stores' => $stores, 'limited' => $stores->count() >= 3000]);
    }

    public function show(int $id)
    {
        $conn = DB::connection('pgsql_imports');

        $store = $conn->table('tiendas_casa_x_casa')->where('id', $id)->first();
        if (! $store) {
            abort(404);
        }

        $cruce = $this->cruceIndividual($conn, $store);

        return view('casa-x-casa.show', compact('store', 'cruce'));
    }

    private function calcularCruce($conn, array $uoFilter = []): array
    {
        $enTiendas = $conn->select('
            SELECT COUNT(*) as total
            FROM tiendas_casa_x_casa cxc
            INNER JOIN tiendas t
                ON t."No_Tienda_Actual" = cxc.no_tienda
                AND t."Nombre_Almacen" = cxc.almacen
                AND t."Estado" = cxc.estado
                AND t."Municipio" = cxc.municipio
            '.$this->whereUoFilterSql($uoFilter, 'cxc').'
        ');

        $soloCxc = $conn->select('
            SELECT COUNT(*) as total
            FROM tiendas_casa_x_casa cxc
            LEFT JOIN tiendas t
                ON t."No_Tienda_Actual" = cxc.no_tienda
                AND t."Nombre_Almacen" = cxc.almacen
                AND t."Estado" = cxc.estado
                AND t."Municipio" = cxc.municipio
            WHERE t.id IS NULL
            '.$this->whereUoFilterSql($uoFilter, 'cxc').'
        ');

        return [
            'enTiendas' => (int) ($enTiendas[0]->total ?? 0),
            'soloCxc' => (int) ($soloCxc[0]->total ?? 0),
        ];
    }

    private function cruceIndividual($conn, $store): ?object
    {
        $rows = $conn->select('
            SELECT "No_Tienda_Actual", "Nombre_Almacen", "Estado", "Municipio",
                   "Fecha_Apertura", "TELEFONIA", "INTERNET", "Señal de celular",
                   "Compañía", "Cap_Tot", "Fch_Audit", "Vigencia"
            FROM tiendas
            WHERE "No_Tienda_Actual" = ?
              AND "Nombre_Almacen" = ?
              AND "Estado" = ?
              AND "Municipio" = ?
            LIMIT 1
        ', [$store->no_tienda, $store->almacen, $store->estado, $store->municipio]);

        return $rows[0] ?? null;
    }

    private function applyNumericBounds($query, Request $request, string $latColumn, string $lonColumn): void
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

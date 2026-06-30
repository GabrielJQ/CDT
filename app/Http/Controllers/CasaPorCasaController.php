<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioCasaPorCasa;
use Illuminate\Http\Request;

class CasaPorCasaController extends Controller
{
    public function __construct(
        private ServicioCasaPorCasa $cxc,
    ) {}

    public function dashboard()
    {
        $data = $this->cxc->dashboardData();

        return view('casa-x-casa.dashboard', $data);
    }

    public function directorio(Request $request)
    {
        $uoFilter = $this->cxc->resolveUoFilter();

        $result = $this->cxc->directorioPaginated(
            filters: $request->only(['estado', 'uo', 'estatus', 'buscar']),
            uoFilter: $uoFilter,
            sortColumn: $request->query('sort'),
            sortDirection: $request->query('direction', 'asc'),
        );

        $filterOptions = $this->cxc->directorioFilterOptions($uoFilter);

        return view('casa-x-casa.directorio', array_merge($result, [
            'estados' => $filterOptions['estados'],
            'unidadesOperativas' => $filterOptions['unidadesOperativas'],
            'estatusList' => $filterOptions['estatusList'],
        ]));
    }

    public function mapa()
    {
        return view('casa-x-casa.mapa');
    }

    public function mapaData(Request $request)
    {
        $uoFilter = $this->cxc->resolveUoFilter();
        $query = $this->cxc->mapaQuery($uoFilter);
        $this->cxc->applyNumericBounds($query, $request, 'latitud', 'longitud');

        if ($almacen = $request->query('almacen')) {
            $query->where('almacen', 'ILIKE', "%{$almacen}%");
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
        if ($anaquelStatus = $request->query('anaquelStatus')) {
            if ($anaquelStatus === 'instalados') {
                $query->where('anaqueles_instalados', true);
            } elseif ($anaquelStatus === 'pendientes') {
                $query->where('anaqueles_instalados', false);
            }
        }
        if ($buscar = $request->query('buscar')) {
            $term = "%{$buscar}%";
            $query->where(function ($q) use ($term) {
                $q->where('almacen', 'ILIKE', $term)
                    ->orWhere('no_tienda', 'ILIKE', $term)
                    ->orWhere('municipio', 'ILIKE', $term);
            });
        }

        $stores = $query->orderBy('id')->limit(3000)->get();

        return response()->json(['stores' => $stores, 'limited' => $stores->count() >= 3000]);
    }

    public function show(int $id)
    {
        $uoFilter = $this->cxc->resolveUoFilter();
        $store = $this->cxc->findStore($id, $uoFilter);

        if (! $store) {
            abort(404);
        }

        $cruce = $this->cxc->cruceIndividual($store);

        return view('casa-x-casa.show', compact('store', 'cruce'));
    }
}

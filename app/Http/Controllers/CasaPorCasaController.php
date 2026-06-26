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

        $query = $this->cxc->directorioQuery($uoFilter);

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

        $sortableColumns = ['no_tienda', 'almacen', 'municipio', 'estado', 'unidad_operativa', 'encargado', 'tipo_anaquel', 'estatus'];
        $sort = $this->tableSortInput($sortableColumns, ['no_tienda', 'almacen', 'municipio']);
        $totalCount = $query->count();

        if ($sort['column'] !== null) {
            $query->orderBy($sort['column'], $sort['direction'])->orderBy('id');
        } else {
            $query->orderBy('estado')->orderBy('municipio');
        }

        $stores = $query->paginate(50);

        $filterOptions = $this->cxc->directorioFilterOptions($uoFilter);
        $estados = $filterOptions['estados'];
        $unidadesOperativas = $filterOptions['unidadesOperativas'];
        $estatusList = $filterOptions['estatusList'];

        return view('casa-x-casa.directorio', compact(
            'stores', 'totalCount', 'sort', 'estados', 'unidadesOperativas', 'estatusList',
        ));
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

<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioPostgresql;

class HomeController extends Controller
{
    public function __construct(
        private ServicioPostgresql $postgres,
    ) {}

    public function index()
    {
        $regionales = $this->postgres->obtenerJerarquiaRegional();
        $regionales = app(ServicioAlcanceUsuario::class)->filtrarJerarquia(request()->user(), $regionales);

        return view('home', [
            'regionales' => $regionales,
        ]);
    }
}

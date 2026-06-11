<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioPostgresql;

class HomeController extends Controller
{
    public function __construct(
        private ServicioPostgresql $postgres,
    ) {}

    public function index()
    {
        $regionales = $this->postgres->obtenerJerarquiaRegional();

        return view('home', [
            'regionales' => $regionales,
        ]);
    }
}

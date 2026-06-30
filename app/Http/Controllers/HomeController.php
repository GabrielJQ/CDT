<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Servicios\ServicioAlcanceUsuario;

class HomeController extends Controller
{
    public function __construct(
        private TiendaRepositoryInterface $tiendaRepository,
        ServicioAlcanceUsuario $alcanceUsuario,
    ) {
        parent::__construct($alcanceUsuario);
    }

    public function index()
    {
        $user = request()->user();
        $regionales = $this->tiendaRepository->getJerarquiaRegional($user);
        $regionales = $this->alcanceUsuario->filtrarJerarquia($user, $regionales);

        return view('home', [
            'regionales' => $regionales,
        ]);
    }
}

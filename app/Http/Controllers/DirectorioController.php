<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class DirectorioController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura', 'TELEFONIA', 'Señal de celular',
        'Compañía', 'INTERNET', 'CORREO', 'Direccion', 'Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu',
        'Bon_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia',
        'Fch_Audit', 'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Nom_Pre_CRA',
        'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
        'Asam_Real_Mes',
    ];

    public function __construct(
        private TiendaRepositoryInterface $tiendaRepository,
        private ServicioPostgresql $postgres,
    ) {}

    public function index(Request $request)
    {
        return view('directorio');
    }
}

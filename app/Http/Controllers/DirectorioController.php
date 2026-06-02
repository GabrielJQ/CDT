<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioGoogleSheet;
use App\Servicios\ServicioExportacion;
use Illuminate\Http\Request;

class DirectorioController extends Controller
{
    public const TRACKED_COLUMNS = [
        'TELEFONIA', 'CORREO', 'Señal de celular', 'Compañía', 'INTERNET',
        'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic',
        'Pagare_Monto', 'Fec_CRA', 'Vigencia', 'Fch_Audit', 'Imp_Res_Audi_Mes',
        'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Direccion',
        'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA',
        'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
    ];

    private array $trackedColumns = self::TRACKED_COLUMNS;

    public function __construct(
        private ServicioGoogleSheet $sheet,
    ) {}

    public function index(Request $request)
    {
        $stores = $this->sheet->obtenerTiendas();
        if ($stores === null) {
            abort(503, $this->sheet->getUltimoError() ?? 'No se pudieron obtener los datos del Google Sheet.');
        }

        $stores = $this->applyRegionFilter($stores);
        $totalCount = count($stores);

        if ($request->query('export') === 'csv') {
            return ServicioExportacion::csvResponse($stores, [
                'Nombre_Almacen' => 'Almacén',
                'No_Tienda_Actual' => 'Tienda #',
                'Municipio' => 'Municipio',
                'Fecha_Apertura' => 'Apertura',
                'TELEFONIA' => 'Teléfono',
                'Señal de celular' => 'Señal Celular',
                'Compañía' => 'Compañía',
                'INTERNET' => 'Internet',
                'CORREO' => 'Correo',
                'Direccion' => 'Dirección',
                'Vta_Mes' => 'Vta Mes',
                'VtaNeta_Mes' => 'Vta Neta Mes',
                'Vta_Acu' => 'Vta Acumulada',
                'VtaNeta_Acu' => 'Vta Neta Acumulada',
                'Bon_Mes' => 'Bon Mes',
                'Cap_Tot' => 'Cap Total',
                'Cap_Com' => 'Cap Com',
                'Cap_Dic' => 'Cap Dic',
                'Pagare_Monto' => 'Pagare Monto',
                'Fec_CRA' => 'Fec CRA',
                'Vigencia' => 'Vigencia',
                'Fch_Audit' => 'Fch Audit',
                'Imp_Res_Audi_Mes' => 'Impuesto',
                'Audit_Realiza_Mes' => 'Auditoría Realizada',
                'Latitud' => 'Latitud',
                'Longitud' => 'Longitud',
                'Nom_Pre_CRA' => 'Presidente',
                'Nom_Pre_Sup_CRA' => 'Presidente Suplente',
                'Nom_Sec_CRA' => 'Secretario',
                'Nom_Sec_Sup_CRA' => 'Secretario Suplente',
                'Nom_Tes_CRA' => 'Tesorero',
                'Nom_Vcv_CRA' => 'Vocal',
                'Nom_Voc_Gen_CRA' => 'Vocal General',
            ], 'directorio.csv');
        }

        return view('directorio', [
            'stores' => $stores,
            'totalCount' => $totalCount,
            'globalStats' => $this->calcularStats($stores),
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }



    private function calcularStats(array $stores): array
    {
        $incompletos = 0;
        $sinCapital = 0;

        foreach ($stores as $store) {
            $hasEmpty = false;
            foreach ($this->trackedColumns as $col) {
                $val = trim($store[$col] ?? '');
                if ($val === '' || $val === '0') {
                    $hasEmpty = true;
                    break;
                }
            }
            if ($hasEmpty) $incompletos++;

            $capTot = trim($store['Cap_Tot'] ?? '');
            if ($capTot === '' || $capTot === '0' || (float) str_replace(',', '', $capTot) === 0.0) {
                $sinCapital++;
            }
        }

        return compact('incompletos', 'sinCapital');
    }
}

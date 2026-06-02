<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioGoogleSheet;

class DirectorioController extends Controller
{
    private array $trackedColumns = [
        'TELEFONIA', 'CORREO', 'Señal de celular', 'Compañía', 'INTERNET',
        'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic',
        'Pagare_Monto', 'Fec_CRA', 'Vigencia', 'Fch_Audit', 'Imp_Res_Audi_Mes',
        'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Direccion',
        'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA',
        'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
    ];

    public function __construct(
        private ServicioGoogleSheet $sheet,
    ) {}

    public function index()
    {
        $stores = $this->sheet->obtenerTiendas();
        if ($stores === null) {
            return $this->errorView();
        }

        $stores = $this->applyRegionFilter($stores);
        $totalCount = count($stores);

        return view('directorio', [
            'stores' => $stores,
            'totalCount' => $totalCount,
            'globalStats' => $this->calcularStats($stores),
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }

    private function errorView()
    {
        return view('directorio', [
            'stores' => [],
            'totalCount' => 0,
            'globalStats' => ['incompletos' => 0, 'sinCapital' => 0],
            'error' => 'No se pudieron obtener los datos del Google Sheet.',
            'updatedAt' => null,
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

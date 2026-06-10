<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioGoogleSheet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DirectorioController extends Controller
{
    public const TRACKED_COLUMNS = [
        'TELEFONIA', 'CORREO', 'Señal de celular', 'Compañía', 'INTERNET',
        'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic',
        'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia', 'Fch_Audit', 'Imp_Res_Audi_Mes',
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
        $stores = $this->applyRegionFilter($this->sheet->obtenerTiendas());
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
                'Pagare_Fecha' => 'Pagare Fecha',
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
        $comitesIncompletos = 0;
        $asambleasMes = 0;
        $tiendasFaltante = 0;
        $importeFaltante = 0.0;
        $pagaresVencidos = 0;
        $importePagaresVencidos = 0.0;

        $columnasIncompletos = array_filter($this->trackedColumns, fn ($c) => ! str_contains($c, 'Sup_CRA'));

        foreach ($stores as $store) {
            $hasEmpty = false;
            foreach ($columnasIncompletos as $col) {
                $val = trim($store[$col] ?? '');
                if ($val === '' || $val === '0') {
                    $hasEmpty = true;
                    break;
                }
            }
            if ($hasEmpty) {
                $incompletos++;
            }

            $capTotStr = trim($store['Cap_Tot'] ?? '');
            $capTot = (float) str_replace([',', '$', ' '], '', $capTotStr);
            if ($capTotStr === '' || $capTotStr === '0' || $capTot === 0.0) {
                $sinCapital++;
            }

            // Comité Incompleto (evaluamos Presidente, Secretario y Tesorero como mínimos)
            $p = trim($store['Nom_Pre_CRA'] ?? '');
            $s = trim($store['Nom_Sec_CRA'] ?? '');
            $t = trim($store['Nom_Tes_CRA'] ?? '');
            if ($p === '' || $p === '0' || $s === '' || $s === '0' || $t === '' || $t === '0') {
                $comitesIncompletos++;
            }

            // Asambleas realizadas en el mes
            $asamReal = (int) ($store['Asam_Real_Mes'] ?? 0);
            if ($asamReal > 0) {
                $asambleasMes++;
            }

            // Faltante de capital (Fórmula PROVISIONAL: Capital Dictaminado - Capital Total)
            $capDic = (float) str_replace([',', '$', ' '], '', trim($store['Cap_Dic'] ?? '0'));
            $faltante = $capDic - $capTot;
            if ($faltante > 0) {
                $tiendasFaltante++;
                $importeFaltante += $faltante;
            }

            // Pagaré vencido (1 año de vigencia desde Pagare_Fecha)
            $pagareFechaStr = trim($store['Pagare_Fecha'] ?? '');
            if ($pagareFechaStr !== '' && $pagareFechaStr !== '0') {
                $parsed = Carbon::createFromFormat('d/m/Y', $pagareFechaStr);
                if ($parsed === false) {
                    $parsed = Carbon::parse($pagareFechaStr);
                }
                if ($parsed !== false && $parsed->copy()->addYear()->isPast()) {
                    $pagaresVencidos++;
                    $pagareMonto = (float) str_replace([',', '$', ' '], '', trim($store['Pagare_Monto'] ?? '0'));
                    $importePagaresVencidos += $pagareMonto;
                }
            }
        }

        return compact('incompletos', 'sinCapital', 'comitesIncompletos', 'asambleasMes', 'tiendasFaltante', 'importeFaltante', 'pagaresVencidos', 'importePagaresVencidos');
    }
}

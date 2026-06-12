<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioGoogleSheet;
use App\Servicios\ServicioPostgresql;
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

    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura', 'TELEFONIA', 'Señal de celular',
        'Compañía', 'INTERNET', 'CORREO', 'Direccion', 'Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu',
        'Bon_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia',
        'Fch_Audit', 'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Nom_Pre_CRA',
        'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
        'Asam_Real_Mes',
    ];

    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioPostgresql $postgres,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'q' => trim($request->query('q', '')),
            'incompletos' => $request->boolean('incompletos'),
            'sinCapital' => $request->boolean('sinCapital'),
        ];

        if ($this->postgres->tieneDatos() && $request->query('export') !== 'csv') {
            $page = max(1, (int) $request->query('page', 1));
            $perPage = max(10, min(100, (int) $request->query('per_page', self::DEFAULT_PAGE_SIZE)));
            $result = $this->postgres->obtenerDirectorioPaginado(
                $this->applyRegionFilter(),
                $filters,
                $page,
                $perPage,
                self::COLUMNS,
                $this->trackedColumns,
            );

            return view('directorio', [
                'stores' => $result['rows'],
                'totalCount' => $result['total'],
                'filteredCount' => $result['filtered'],
                'serverPagination' => [
                    'page' => $page,
                    'perPage' => $perPage,
                    'total' => $result['filtered'],
                    'totalPages' => max(1, (int) ceil($result['filtered'] / $perPage)),
                ],
                'filters' => $filters,
                'globalStats' => $result['stats'],
                'updatedAt' => now()->toDateTimeString(),
            ]);
        }

        $stores = $this->sheet->obtenerTiendas($this->applyRegionFilter(), self::COLUMNS);
        $totalCount = count($stores);

        $filtered = array_values(array_filter($stores, function (array $store) use ($filters) {
            if ($filters['q'] !== '') {
                $search = mb_strtoupper($filters['q']);
                $haystack = mb_strtoupper(implode(' ', [
                    $store['Nombre_Almacen'] ?? '',
                    $store['No_Tienda_Actual'] ?? '',
                    $store['Municipio'] ?? '',
                ]));
                if (! str_contains($haystack, $search)) {
                    return false;
                }
            }

            if ($filters['incompletos'] && ! $this->tieneCamposIncompletos($store)) {
                return false;
            }

            if ($filters['sinCapital'] && ! $this->sinCapital($store)) {
                return false;
            }

            return true;
        }));

        if ($request->query('export') === 'csv') {
            return ServicioExportacion::csvResponse($filtered, [
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

        $pagination = $this->paginateArray($filtered);

        return view('directorio', [
            'stores' => $pagination['items'],
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'serverPagination' => $pagination['meta'],
            'filters' => $filters,
            'globalStats' => $this->calcularStats($stores),
            'updatedAt' => now()->toDateTimeString(),
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
            if ($this->tieneCamposIncompletos($store, $columnasIncompletos)) {
                $incompletos++;
            }

            $capTot = $this->capitalTotal($store);
            if ($this->sinCapital($store)) {
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

    private function tieneCamposIncompletos(array $store, ?array $columns = null): bool
    {
        $columns ??= array_filter($this->trackedColumns, fn ($c) => ! str_contains($c, 'Sup_CRA'));
        foreach ($columns as $col) {
            $val = trim($store[$col] ?? '');
            if ($val === '' || $val === '0') {
                return true;
            }
        }

        return false;
    }

    private function sinCapital(array $store): bool
    {
        $capTotStr = trim($store['Cap_Tot'] ?? '');

        return $capTotStr === '' || $capTotStr === '0' || $this->capitalTotal($store) === 0.0;
    }

    private function capitalTotal(array $store): float
    {
        return (float) str_replace([',', '$', ' '], '', trim($store['Cap_Tot'] ?? ''));
    }
}

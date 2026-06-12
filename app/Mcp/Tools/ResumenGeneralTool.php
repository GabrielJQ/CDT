<?php

namespace App\Mcp\Tools;

use App\Servicios\ServicioAuditoria;
use App\Servicios\ServicioConectividad;
use App\Servicios\ServicioFecha;
use App\Servicios\ServicioPostgresql;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Resumen general del dashboard: total de tiendas, conectividad, auditoría, aperturas del mes y ubicación geográfica.')]
class ResumenGeneralTool extends Tool
{
    public function __construct(
        private ServicioPostgresql $postgres,
        private ServicioConectividad $conectividad,
        private ServicioAuditoria $auditoria,
        private ServicioFecha $fecha,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'estado' => $schema->string()
                ->description('Filtrar por estado (opcional)')
                ->default(''),
        ];
    }

    public function handle(Request $request): Response
    {
        $tiendas = $this->postgres->obtenerTiendas();

        $estado = trim($request->get('estado', ''));
        if ($estado !== '') {
            $tiendas = array_values(array_filter($tiendas, fn ($t) => str_contains(mb_strtolower($t['Estado'] ?? ''), mb_strtolower($estado))
            ));
        }

        if (empty($tiendas)) {
            return Response::text('No se encontraron tiendas'.($estado ? " en el estado {$estado}" : '').'.');
        }

        $total = count($tiendas);
        $conectividad = $this->conectividad->resumenSimple($tiendas);
        $auditoria = $this->auditoria->resumenSimple($tiendas);

        $now = now();
        $aperturasMes = 0;
        $conCoordenadas = 0;
        foreach ($tiendas as $t) {
            $fecha = $this->fecha->parsear($t['Fecha_Apertura'] ?? '');
            if ($fecha && $fecha->year === $now->year && $fecha->month === $now->month) {
                $aperturasMes++;
            }
            $lat = trim($t['Latitud'] ?? '');
            $lon = trim($t['Longitud'] ?? '');
            if ($lat !== '' && $lat !== '0' && $lon !== '' && $lon !== '0') {
                $conCoordenadas++;
            }
        }

        $sinCoordenadas = $total - $conCoordenadas;

        $lineas = ["=== Resumen General ===\n"];
        $lineas[] = "Total de tiendas: {$total}";
        $lineas[] = "Aperturas este mes: {$aperturasMes}";
        $lineas[] = '';
        $lineas[] = '--- Conectividad ---';
        foreach (['TELEFONIA', 'INTERNET', 'Señal de celular'] as $col) {
            $k = $conectividad[$col] ?? null;
            if ($k) {
                $lineas[] = "{$k['label']}: {$k['pctYes']}% ({$k['yes']} de {$total})";
            }
        }
        $lineas[] = '';
        $lineas[] = '--- Auditoría ---';
        $lineas[] = "Comités de CRA vencidos: {$auditoria['comitesVencidos']} (".($total > 0 ? round($auditoria['comitesVencidos'] / $total * 100, 1) : 0).'%)';
        $lineas[] = "Auditorías mayores a $500,000: {$auditoria['auditoriaAlta']} (".($total > 0 ? round($auditoria['auditoriaAlta'] / $total * 100, 1) : 0).'%)';
        $lineas[] = "Rotación menor a 0.5: {$auditoria['rotacionBaja']} (".($total > 0 ? round($auditoria['rotacionBaja'] / $total * 100, 1) : 0).'%)';
        $lineas[] = "Auditorías pendientes (+3 meses): {$auditoria['auditoriaPendiente']} (".($total > 0 ? round($auditoria['auditoriaPendiente'] / $total * 100, 1) : 0).'%)';
        $lineas[] = '';
        $lineas[] = '--- Geo ---';
        $lineas[] = "Con coordenadas: {$conCoordenadas}";
        $lineas[] = "Sin coordenadas: {$sinCoordenadas}";

        return Response::text(implode("\n", $lineas))->withStructuredContent([
            'total' => $total,
            'aperturas_mes' => $aperturasMes,
            'conectividad' => $conectividad,
            'auditoria' => $auditoria,
            'con_coordenadas' => $conCoordenadas,
            'sin_coordenadas' => $sinCoordenadas,
        ]);
    }
}

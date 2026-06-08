<?php

namespace App\Mcp\Tools;

use App\Servicios\ServicioAuditoria;
use App\Servicios\ServicioGoogleSheet;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Obtiene KPIs de auditoría: comités vencidos, auditoría alta, rotación baja y auditorías pendientes.')]
class ResumenAuditoriaTool extends Tool
{
    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioAuditoria $auditoria,
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
        $tiendas = $this->sheet->obtenerTiendas();

        if ($tiendas === null) {
            return Response::error('No se pudieron obtener los datos del Google Sheet.');
        }

        $estado = trim($request->get('estado', ''));
        if ($estado !== '') {
            $tiendas = array_values(array_filter($tiendas, fn ($t) => str_contains(mb_strtolower($t['Estado'] ?? ''), mb_strtolower($estado))
            ));
        }

        if (empty($tiendas)) {
            return Response::text('No se encontraron tiendas'.($estado ? " en el estado {$estado}" : '').'.');
        }

        foreach ($tiendas as &$store) {
            $store['_audit'] = $this->auditoria->evaluarTienda($store);
        }

        $kpis = $this->auditoria->calcularKpis($tiendas);

        $total = count($tiendas);
        $lineas = ["Resumen de Auditoría ({$total} tiendas)\n"];
        $lineas[] = "Comités vencidos: {$kpis['comitesVencidos']}";
        $lineas[] = "Auditoría alta (> \$500k): {$kpis['auditoriaAlta']}";
        $lineas[] = "Rotación baja (< 0.5): {$kpis['rotacionBaja']}";
        $lineas[] = "Auditorías pendientes (3+ meses): {$kpis['auditoriaPendiente']}";
        $lineas[] = '';
        $lineas[] = "Rotación: cero={$kpis['rotacionCero']}, crítico={$kpis['rotacionCritico']}, amarillo={$kpis['rotacionAmarillo']}, óptimo={$kpis['rotacionOptimo']}";
        $lineas[] = "Auditorías realizadas este mes: {$kpis['auditoriasMes']}";
        $lineas[] = "Sin auditoría en 12+ meses: {$kpis['sinAuditoriaAnio']}";

        return Response::text(implode("\n", $lineas))->withStructuredContent($kpis);
    }
}

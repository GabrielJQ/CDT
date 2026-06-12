<?php

namespace App\Mcp\Tools;

use App\Servicios\ServicioAuditoria;
use App\Servicios\ServicioPostgresql;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Obtiene KPIs de auditoría: comités vencidos, auditoría alta, rotación baja y auditorías pendientes.')]
class ResumenAuditoriaTool extends Tool
{
    public function __construct(
        private ServicioPostgresql $postgres,
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
        $tiendas = $this->postgres->obtenerTiendas(columns: ['Estado', 'Vigencia', 'Imp_Res_Audi_Mes', 'Cap_Dic', 'Vta_Mes', 'Fch_Audit', 'Audit_Realiza_Mes']);

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
        $lineas[] = "Comités de CRA vencidos: {$kpis['comitesVencidos']} (".($total > 0 ? round($kpis['comitesVencidos'] / $total * 100, 1) : 0).'%)';
        $lineas[] = "Auditorías mayores a $500,000: {$kpis['auditoriaAlta']} (".($total > 0 ? round($kpis['auditoriaAlta'] / $total * 100, 1) : 0).'%)';
        $lineas[] = "Rotación menor a 0.5: {$kpis['rotacionBaja']} (".($total > 0 ? round($kpis['rotacionBaja'] / $total * 100, 1) : 0).'%)';
        $lineas[] = "Auditorías pendientes (+3 meses): {$kpis['auditoriaPendiente']} (".($total > 0 ? round($kpis['auditoriaPendiente'] / $total * 100, 1) : 0).'%)';
        $lineas[] = '';
        $lineas[] = "Rotación: cero={$kpis['rotacionCero']} (".($total > 0 ? round($kpis['rotacionCero'] / $total * 100, 1) : 0)."%), crítico={$kpis['rotacionCritico']} (".($total > 0 ? round($kpis['rotacionCritico'] / $total * 100, 1) : 0)."%), amarillo={$kpis['rotacionAmarillo']} (".($total > 0 ? round($kpis['rotacionAmarillo'] / $total * 100, 1) : 0)."%), óptimo={$kpis['rotacionOptimo']} (".($total > 0 ? round($kpis['rotacionOptimo'] / $total * 100, 1) : 0).'%)';
        $lineas[] = "Auditorías realizadas este mes: {$kpis['auditoriasMes']} (".($total > 0 ? round($kpis['auditoriasMes'] / $total * 100, 1) : 0).'%)';
        $lineas[] = "Sin auditoría en 12+ meses: {$kpis['sinAuditoriaAnio']} (".($total > 0 ? round($kpis['sinAuditoriaAnio'] / $total * 100, 1) : 0).'%)';

        return Response::text(implode("\n", $lineas))->withStructuredContent($kpis);
    }
}

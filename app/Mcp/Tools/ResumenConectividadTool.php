<?php

namespace App\Mcp\Tools;

use App\Servicios\ServicioConectividad;
use App\Servicios\ServicioGoogleSheet;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Obtiene KPIs de conectividad de las tiendas: telefonía, internet, señal celular y distribución por compañía.')]
class ResumenConectividadTool extends Tool
{
    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioConectividad $conectividad,
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

        $kpis = $this->conectividad->calcularKpis($tiendas);
        $total = $kpis['_total'];
        $compania = $kpis['_compania'] ?? [];

        $campos = ['TELEFONIA', 'INTERNET', 'Señal de celular'];
        $lineas = ["Resumen de Conectividad ({$total} tiendas)\n"];
        foreach ($campos as $col) {
            $k = $kpis[$col] ?? null;
            if ($k) {
                $lineas[] = "{$k['label']}: {$k['pctYes']}% sí ({$k['yes']} de {$total})";
            }
        }

        $lineas[] = "\nDistribución por compañía (señal celular):";
        foreach ($compania as $comp => $data) {
            $lineas[] = "- {$comp}: {$data['count']} ({$data['pct']}%)";
        }

        return Response::text(implode("\n", $lineas))->withStructuredContent([
            'total' => $total,
            'telefonia' => $kpis['TELEFONIA'] ?? null,
            'internet' => $kpis['INTERNET'] ?? null,
            'senial_celular' => $kpis['Señal de celular'] ?? null,
            'compania' => $compania,
        ]);
    }
}

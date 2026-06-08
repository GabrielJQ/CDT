<?php

namespace App\Mcp\Tools;

use App\Servicios\ServicioGoogleSheet;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Busca tiendas por nombre, estado o municipio. Devuelve datos básicos de cada tienda encontrada.')]
class BuscarTiendaTool extends Tool
{
    public function __construct(
        private ServicioGoogleSheet $sheet,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Texto a buscar (nombre de tienda, estado o municipio)')
                ->required(),
            'limite' => $schema->integer()
                ->description('Máximo de resultados a devolver (default: 10)')
                ->default(10),
        ];
    }

    public function handle(Request $request): Response
    {
        $query = mb_strtolower(trim($request->get('query', '')));
        $limite = (int) $request->get('limite', 10);

        if ($query === '') {
            return Response::error('Debes proporcionar un texto de búsqueda.');
        }

        $tiendas = $this->sheet->obtenerTiendas();

        if ($tiendas === null) {
            return Response::error('No se pudieron obtener los datos del Google Sheet.');
        }

        $resultados = array_values(array_filter($tiendas, fn ($t) => str_contains(mb_strtolower($t['Nombre_Almacen'] ?? ''), $query)
            || str_contains(mb_strtolower($t['Estado'] ?? ''), $query)
            || str_contains(mb_strtolower($t['Municipio'] ?? ''), $query)
        ));

        $total = count($resultados);
        $resultados = array_slice($resultados, 0, $limite);

        $items = array_map(fn ($t) => [
            'clave' => $t['Clave_Sucursal'] ?? '',
            'nombre' => $t['Nombre_Almacen'] ?? '',
            'estado' => $t['Estado'] ?? '',
            'municipio' => $t['Municipio'] ?? '',
            'direccion' => trim(($t['Domicilio'] ?? '').', '.($t['Colonia'] ?? '')),
        ], $resultados);

        $texto = "Tiendas encontradas: {$total}\n\n";
        foreach ($items as $item) {
            $texto .= "- {$item['clave']}: {$item['nombre']} ({$item['estado']}, {$item['municipio']})\n";
        }
        $ocultos = $total - $limite;
        if ($ocultos > 0) {
            $texto .= "\n(y {$ocultos} más)";
        }

        return Response::text($texto)->withStructuredContent([
            'total' => $total,
            'mostrados' => count($items),
            'items' => $items,
        ]);
    }
}

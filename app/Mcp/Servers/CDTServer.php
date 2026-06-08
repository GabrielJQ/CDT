<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\BuscarTiendaTool;
use App\Mcp\Tools\ResumenAuditoriaTool;
use App\Mcp\Tools\ResumenConectividadTool;
use App\Mcp\Tools\ResumenGeneralTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('CDT Server')]
#[Version('1.0.0')]
#[Instructions('Servidor MCP para consultar datos del dashboard de Control de Tiendas. Permite buscar tiendas, obtener resúmenes de conectividad, auditoría y estadísticas generales.')]
#[Description('Datos de tiendas, conectividad, auditoría y métricas del dashboard CDT')]
class CDTServer extends Server
{
    protected array $tools = [
        BuscarTiendaTool::class,
        ResumenConectividadTool::class,
        ResumenAuditoriaTool::class,
        ResumenGeneralTool::class,
    ];
}

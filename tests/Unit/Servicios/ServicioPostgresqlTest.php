<?php

namespace Tests\Unit\Servicios;

use App\Servicios\ServicioPostgresql;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ServicioPostgresqlTest extends TestCase
{
    public function test_row_to_audit_store_calculates_kpis_when_derived_columns_are_null(): void
    {
        $store = $this->invokePrivate('rowToAuditStore', [
            (object) [
                'Estado' => 'Oaxaca',
                'Nombre_Almacen' => 'Almacen Central',
                'No_Tienda_Actual' => '123',
                'Municipio' => 'Oaxaca',
                'Cap_Dic' => 100000,
                'Vta_Mes' => 20000,
                'Fch_Audit' => now()->subMonths(6)->toDateString(),
                'Audit_Realiza_Mes' => 0,
                'Asam_Real_Mes' => 0,
                'Vigencia' => now()->subMonth()->toDateString(),
                'Imp_Res_Audi_Mes' => 600000,
                'nivel_critico' => null,
                'estado_comite' => null,
                'rango_rotacion' => null,
                'auditoria_pendiente' => null,
            ],
            ['Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Cap_Dic', 'Vta_Mes', 'Fch_Audit', 'Audit_Realiza_Mes', 'Asam_Real_Mes', 'Vigencia', 'Imp_Res_Audi_Mes'],
        ]);

        $this->assertSame('vencido', $store['_audit']['estadoComite']);
        $this->assertSame('critico', $store['_audit']['rangoRotacion']);
        $this->assertTrue($store['_audit']['auditoriaPendiente']);
        $this->assertSame('rojo', $store['_audit']['level']);
        $this->assertContains('comite_vencido', $store['_audit']['conditions']);
        $this->assertContains('auditoria_alta', $store['_audit']['conditions']);
        $this->assertContains('rotacion_baja', $store['_audit']['conditions']);
        $this->assertContains('auditoria_pendiente', $store['_audit']['conditions']);
    }

    public function test_row_to_critical_store_calculates_level_when_derived_columns_are_null(): void
    {
        $store = $this->invokePrivate('rowToCriticalStore', [
            (object) [
                'Estado' => 'Oaxaca',
                'Nombre_Almacen' => 'Almacen Central',
                'No_Tienda_Actual' => '123',
                'Municipio' => 'Oaxaca',
                'Cap_Tot' => 10000,
                'Cap_Dic' => 10000,
                'Vigencia' => now()->subMonth()->toDateString(),
                'Imp_Res_Audi_Mes' => 600000,
                'Pagare_Fecha' => now()->subYears(2)->toDateString(),
                'Vta_Mes' => 1000,
                'Asam_Prog_Mes' => 1,
                'Asam_Real_Mes' => 0,
                'nivel_critico' => null,
                'factores_criticos_count' => null,
            ],
            ['Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Cap_Tot', 'Cap_Dic', 'Vigencia', 'Imp_Res_Audi_Mes', 'Pagare_Fecha', 'Vta_Mes', 'Asam_Prog_Mes', 'Asam_Real_Mes'],
        ]);

        $this->assertSame(7, $store['_critico']['count']);
        $this->assertSame('rojo', $store['_critico']['level']);
        $this->assertTrue($store['_critico']['conditions']['comite_vencido']);
        $this->assertTrue($store['_critico']['conditions']['rotacion_baja']);
        $this->assertTrue($store['_critico']['conditions']['asamblea_pendiente']);
    }

    public function test_filtrar_geo_calculado_incidencias_includes_sin_coordenadas_and_fuera_mexico(): void
    {
        $rows = $this->invokePrivate('filtrarGeoCalculado', [[
            ['_geo' => ['status' => 'OK']],
            ['_geo' => ['status' => 'SIN_COORDENADAS']],
            ['_geo' => ['status' => 'FUERA_MEXICO']],
            ['_geo' => ['status' => 'FUERA_ESTADO']],
        ], 'INCIDENCIAS']);

        $this->assertCount(2, $rows);
        $this->assertSame('SIN_COORDENADAS', $rows[0]['_geo']['status']);
        $this->assertSame('FUERA_MEXICO', $rows[1]['_geo']['status']);
    }

    private function invokePrivate(string $method, array $arguments): mixed
    {
        $service = new ServicioPostgresql;
        $reflection = new ReflectionClass($service);
        $reflectionMethod = $reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($service, $arguments);
    }
}

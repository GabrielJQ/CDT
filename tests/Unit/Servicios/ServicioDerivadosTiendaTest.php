<?php

namespace Tests\Unit\Servicios;

use App\Servicios\ServicioAuditoria;
use App\Servicios\ServicioDerivadosTienda;
use App\Servicios\ServicioFecha;
use App\Servicios\ServicioGeo;
use App\Servicios\ServicioTiendaCritica;
use PHPUnit\Framework\TestCase;

class ServicioDerivadosTiendaTest extends TestCase
{
    private ServicioDerivadosTienda $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $fecha = new ServicioFecha;
        $this->servicio = new ServicioDerivadosTienda(
            new ServicioAuditoria($fecha),
            new ServicioTiendaCritica($fecha),
            new ServicioGeo,
        );
    }

    public function test_calcula_derivados_existentes(): void
    {
        $derivados = $this->servicio->calcular([
            'Estado' => 'OAXACA',
            'Latitud' => '17.0600',
            'Longitud' => '-96.7250',
            'Cap_Tot' => '10000',
            'Cap_Dic' => '10000',
            'Vta_Mes' => '2000',
            'Vigencia' => now()->subMonth()->format('Y-m-d'),
            'Imp_Res_Audi_Mes' => '600000',
            'Pagare_Fecha' => now()->subYears(2)->format('Y-m-d'),
            'Asam_Prog_Mes' => '1',
            'Asam_Real_Mes' => '0',
            'Fch_Audit' => now()->subMonths(6)->format('Y-m-d'),
        ]);

        $this->assertSame('rojo', $derivados['nivel_critico']);
        $this->assertSame(7, $derivados['factores_criticos_count']);
        $this->assertSame('OK', $derivados['estado_geo']);
        $this->assertSame('vencido', $derivados['estado_comite']);
        $this->assertSame('critico', $derivados['rango_rotacion']);
        $this->assertTrue($derivados['auditoria_pendiente']);
    }

    public function test_only_auditoria_limita_columnas_calculadas(): void
    {
        $derivados = $this->servicio->calcular([
            'Cap_Dic' => '0',
            'Vta_Mes' => '0',
            'Vigencia' => '',
            'Fch_Audit' => '',
        ], 'auditoria');

        $this->assertSame([
            'estado_comite' => 'sin_fecha',
            'rango_rotacion' => 'cero',
            'auditoria_pendiente' => true,
        ], $derivados);
    }
}

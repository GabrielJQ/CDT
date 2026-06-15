<?php

namespace Tests\Unit\Servicios;

use App\Servicios\ServicioPeriodosImportacion;
use PHPUnit\Framework\TestCase;

class ServicioPeriodosImportacionTest extends TestCase
{
    private ServicioPeriodosImportacion $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new ServicioPeriodosImportacion;
    }

    public function test_rango_de_fechas_por_trimestre(): void
    {
        $this->assertSame(['2026-01-01', '2026-03-31'], $this->servicio->rangoFechas(2026, 'T1'));
        $this->assertSame(['2026-04-01', '2026-06-30'], $this->servicio->rangoFechas(2026, 'T2'));
        $this->assertSame(['2026-07-01', '2026-09-30'], $this->servicio->rangoFechas(2026, 'T3'));
        $this->assertSame(['2026-10-01', '2026-12-31'], $this->servicio->rangoFechas(2026, 'T4'));
    }

    public function test_llave_regular_usa_campos_confirmados(): void
    {
        $llave = $this->servicio->llaveRegular([
            'Clave_Regional' => '47',
            'Clave_UniOpe' => '1',
            'ClaveSIAC_Almacen' => ' oax-01 ',
            'No_Tienda_Actual' => ' 1 ',
        ]);

        $this->assertSame('47|1|OAX-01|1', $llave);
    }

    public function test_llave_casa_por_casa_usa_campos_confirmados(): void
    {
        $llave = $this->servicio->llaveCasaPorCasa([
            'unidad_operativa' => 'Oaxaca',
            'almacen' => 'Central',
            'no_tienda' => '1',
            'estado' => 'Oaxaca',
            'municipio' => 'Oaxaca de Juarez',
        ]);

        $this->assertSame('OAXACA|CENTRAL|1|OAXACA|OAXACA DE JUAREZ', $llave);
    }
}

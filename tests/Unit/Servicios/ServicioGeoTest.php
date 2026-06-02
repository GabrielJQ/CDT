<?php

namespace Tests\Unit\Servicios;

use App\Servicios\ServicioGeo;
use PHPUnit\Framework\TestCase;

class ServicioGeoTest extends TestCase
{
    private ServicioGeo $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioGeo;
    }

    public function test_coordenada_vacia_retorna_null(): void
    {
        $this->assertNull($this->servicio->parsearCoordenada(''));
        $this->assertNull($this->servicio->parsearCoordenada('0'));
    }

    public function test_decimal_positivo(): void
    {
        $result = $this->servicio->parsearCoordenada('17.5');
        $this->assertNotNull($result);
        $this->assertSame(17.5, $result);
    }

    public function test_cardinal_sur_negativo(): void
    {
        $result = $this->servicio->parsearCoordenada('17.5 S');
        $this->assertSame(-17.5, $result);
    }

    public function test_cardinal_oeste_negativo(): void
    {
        $result = $this->servicio->parsearCoordenada('97.5 W');
        $this->assertSame(-97.5, $result);
    }

    public function test_dms_grados_minutos(): void
    {
        $result = $this->servicio->parsearCoordenada("17° 30'");
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(17.5, $result, 0.001);
    }

    public function test_dms_completo(): void
    {
        $result = $this->servicio->parsearCoordenada("17° 30' 0\"");
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(17.5, $result, 0.001);
    }

    public function test_dms_con_cardinal(): void
    {
        $result = $this->servicio->parsearCoordenada("17° 30' 0\" N");
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(17.5, $result, 0.001);
    }

    public function test_dms_sur(): void
    {
        $result = $this->servicio->parsearCoordenada("17° 30' 0\" S");
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(-17.5, $result, 0.001);
    }

    public function test_coma_decimal_espanol(): void
    {
        $result = $this->servicio->parsearCoordenada('17,5');
        $this->assertSame(17.5, $result);
    }

    public function test_evaluar_geo_ok(): void
    {
        $store = [
            'Latitud' => '17.5',
            'Longitud' => '-96.5',
            'Estado' => 'OAXACA',
        ];

        $result = $this->servicio->evaluarGeo($store);

        $this->assertSame('OK', $result['status']);
        $this->assertSame(17.5, $result['lat']);
        $this->assertSame(-96.5, $result['lon']);
    }

    public function test_evaluar_geo_sin_coordenadas(): void
    {
        $store = [
            'Latitud' => '',
            'Longitud' => '',
            'Estado' => 'OAXACA',
        ];

        $result = $this->servicio->evaluarGeo($store);

        $this->assertSame('SIN_COORDENADAS', $result['status']);
    }

    public function test_evaluar_geo_fuera_mexico(): void
    {
        $store = [
            'Latitud' => '50.0',
            'Longitud' => '-120.0',
            'Estado' => 'OTRO',
        ];

        $result = $this->servicio->evaluarGeo($store);

        $this->assertSame('FUERA_MEXICO', $result['status']);
    }

    public function test_evaluar_geo_fuera_estado(): void
    {
        $store = [
            'Latitud' => '25.0',
            'Longitud' => '-97.0',
            'Estado' => 'OAXACA',
        ];

        $result = $this->servicio->evaluarGeo($store);

        $this->assertSame('FUERA_ESTADO', $result['status']);
    }

    public function test_evaluar_geo_fuera_estado_solo_cuando_oaxaca(): void
    {
        $store = [
            'Latitud' => '25.0',
            'Longitud' => '-97.0',
            'Estado' => 'TAMAULIPAS',
        ];

        $result = $this->servicio->evaluarGeo($store);

        $this->assertSame('OK', $result['status']);
    }

    public function test_calcular_stats(): void
    {
        $stores = [
            ['_geo' => ['status' => 'OK']],
            ['_geo' => ['status' => 'OK']],
            ['_geo' => ['status' => 'SIN_COORDENADAS']],
        ];

        $stats = $this->servicio->calcularStats($stores);

        $this->assertSame(2, $stats['OK']);
        $this->assertSame(1, $stats['SIN_COORDENADAS']);
        $this->assertSame(0, $stats['FUERA_MEXICO']);
    }
}

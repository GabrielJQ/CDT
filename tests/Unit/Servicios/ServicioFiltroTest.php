<?php

namespace Tests\Unit\Servicios;

use App\Servicios\ServicioFiltro;
use Tests\TestCase;

class ServicioFiltroTest extends TestCase
{
    private ServicioFiltro $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioFiltro;
    }

    public function test_por_almacen_vacio_retorna_todos(): void
    {
        $stores = [
            ['Nombre_Almacen' => 'OAXACA CENTRO'],
            ['Nombre_Almacen' => 'ISTMO SUR'],
        ];

        $result = $this->servicio->porAlmacen($stores, '');

        $this->assertCount(2, $result);
    }

    public function test_por_almacen_match_exacto(): void
    {
        $stores = [
            ['Nombre_Almacen' => 'OAXACA CENTRO'],
            ['Nombre_Almacen' => 'ISTMO SUR'],
        ];

        $result = $this->servicio->porAlmacen($stores, 'OAXACA');

        $this->assertCount(1, $result);
        $this->assertSame('OAXACA CENTRO', $result[0]['Nombre_Almacen']);
    }

    public function test_por_almacen_case_insensitive(): void
    {
        $stores = [
            ['Nombre_Almacen' => 'Oaxaca Centro'],
            ['Nombre_Almacen' => 'Istmo Sur'],
        ];

        $result = $this->servicio->porAlmacen($stores, 'oaxaca');

        $this->assertCount(1, $result);
    }

    public function test_por_almacen_parcial(): void
    {
        $stores = [
            ['Nombre_Almacen' => 'OAXACA CENTRO'],
            ['Nombre_Almacen' => 'OAXACA SUR'],
            ['Nombre_Almacen' => 'ISTMO NORTE'],
        ];

        $result = $this->servicio->porAlmacen($stores, 'SUR');

        $this->assertCount(1, $result);
    }

    public function test_por_almacen_sin_match(): void
    {
        $stores = [
            ['Nombre_Almacen' => 'OAXACA CENTRO'],
        ];

        $result = $this->servicio->porAlmacen($stores, 'MIXTECA');

        $this->assertCount(0, $result);
    }

    public function test_opciones_almacen(): void
    {
        $stores = [
            ['Nombre_Almacen' => 'OAXACA CENTRO'],
            ['Nombre_Almacen' => 'ISTMO SUR'],
            ['Nombre_Almacen' => 'OAXACA CENTRO'],
        ];

        $result = $this->servicio->opcionesAlmacen($stores);

        $this->assertCount(2, $result);
        $this->assertSame('ISTMO SUR', $result[0]);
        $this->assertSame('OAXACA CENTRO', $result[1]);
    }

    public function test_opciones_compania(): void
    {
        $stores = [
            ['Compañía' => 'Telcel'],
            ['Compañía' => 'Movistar'],
            ['Compañía' => 'Telcel'],
        ];

        $result = $this->servicio->opcionesCompania($stores);

        $this->assertCount(2, $result);
    }

    public function test_opciones_compania_vacia_se_agrupa(): void
    {
        $stores = [
            ['Compañía' => 'Telcel'],
            ['Compañía' => ''],
        ];

        $result = $this->servicio->opcionesCompania($stores);

        $this->assertContains('Sin dato', $result);
    }
}

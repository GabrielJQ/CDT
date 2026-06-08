<?php

namespace Tests\Unit\Servicios;

use App\Servicios\ServicioFecha;
use App\Servicios\ServicioTiendaCritica;
use PHPUnit\Framework\TestCase;

class ServicioTiendaCriticaTest extends TestCase
{
    private ServicioTiendaCritica $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioTiendaCritica(new ServicioFecha);
    }

    public function test_ningun_factor_verde(): void
    {
        $store = [
            'Cap_Tot' => '200000',
            'Cap_Dic' => '200000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertSame('verde', $result['level']);
        $this->assertSame(0, $result['count']);
    }

    public function test_capital_bajo(): void
    {
        $store = [
            'Cap_Tot' => '20000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertTrue($result['conditions']['capital_bajo']);
    }

    public function test_capital_normal_no_activa(): void
    {
        $store = [
            'Cap_Tot' => '200000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertFalse($result['conditions']['capital_bajo']);
    }

    public function test_capital_cero_no_activa(): void
    {
        $store = [
            'Cap_Tot' => '0',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '0',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '0',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertFalse($result['conditions']['capital_bajo']);
    }

    public function test_comite_vencido(): void
    {
        $store = [
            'Cap_Tot' => '200000',
            'Vigencia' => '2020-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertTrue($result['conditions']['comite_vencido']);
    }

    public function test_auditoria_elevada(): void
    {
        $store = [
            'Cap_Tot' => '200000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '600000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertTrue($result['conditions']['auditoria_elevada']);
    }

    public function test_pagare_vencido(): void
    {
        $store = [
            'Cap_Tot' => '200000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => now()->subYears(2)->format('Y-m-d'),
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertTrue($result['conditions']['pagare_vencido']);
    }

    public function test_pagare_no_vencido(): void
    {
        $store = [
            'Cap_Tot' => '200000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertFalse($result['conditions']['pagare_vencido']);
    }

    public function test_rotacion_baja(): void
    {
        $store = [
            'Cap_Tot' => '100000',
            'Cap_Dic' => '100000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '40000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertTrue($result['conditions']['rotacion_baja']);
    }

    public function test_rotacion_normal(): void
    {
        $store = [
            'Cap_Tot' => '100000',
            'Cap_Dic' => '100000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '200000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertFalse($result['conditions']['rotacion_baja']);
    }

    public function test_asamblea_pendiente(): void
    {
        $store = [
            'Cap_Tot' => '200000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '2',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertTrue($result['conditions']['asamblea_pendiente']);
    }

    public function test_asamblea_realizada_no_activa(): void
    {
        $store = [
            'Cap_Tot' => '200000',
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '2',
            'Asam_Real_Mes' => '2',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertFalse($result['conditions']['asamblea_pendiente']);
    }

    public function test_nivel_rojo(): void
    {
        $store = [
            'Cap_Tot' => '50000',
            'Vigencia' => '2020-01-01',
            'Imp_Res_Audi_Mes' => '600000',
            'Pagare_Fecha' => now()->addMonth()->format('Y-m-d'),
            'Vta_Mes' => '100000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '2',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertSame('rojo', $result['level']);
        $this->assertGreaterThanOrEqual(4, $result['count']);
    }

    public function test_nivel_amarillo(): void
    {
        $store = [
            'Cap_Tot' => '200000',
            'Vigencia' => '2020-01-01',
            'Imp_Res_Audi_Mes' => '600000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertSame('amarillo', $result['level']);
    }

    public function test_labels_tienen_detalle(): void
    {
        $store = [
            'Cap_Tot' => '50000',
            'Vigencia' => '2020-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Pagare_Fecha' => '2030-01-01',
            'Vta_Mes' => '500000',
            'GDOMARG' => '',
            'Asam_Prog_Mes' => '0',
            'Asam_Real_Mes' => '0',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertArrayHasKey('label', $result['labels']['capital_bajo']);
        $this->assertArrayHasKey('detail', $result['labels']['capital_bajo']);
        $this->assertArrayHasKey('label', $result['labels']['comite_vencido']);
        $this->assertArrayHasKey('detail', $result['labels']['comite_vencido']);
    }
}

<?php

namespace Tests\Unit\Servicios;

use App\Servicios\ServicioAuditoria;
use App\Servicios\ServicioFecha;
use PHPUnit\Framework\TestCase;

class ServicioAuditoriaTest extends TestCase
{
    private ServicioAuditoria $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioAuditoria(new ServicioFecha);
    }

    public function test_ningun_factor_verde(): void
    {
        $store = [
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => now()->subMonth()->format('Y-m-d'),
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertSame('verde', $result['level']);
        $this->assertEmpty($result['conditions']);
    }

    public function test_comite_vencido(): void
    {
        $store = [
            'Vigencia' => '2020-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => now()->subMonth()->format('Y-m-d'),
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertContains('comite_vencido', $result['conditions']);
    }

    public function test_auditoria_alta(): void
    {
        $store = [
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '600000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => now()->subMonth()->format('Y-m-d'),
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertContains('auditoria_alta', $result['conditions']);
    }

    public function test_rotacion_baja(): void
    {
        $store = [
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '100000',
            'Fch_Audit' => now()->subMonth()->format('Y-m-d'),
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertContains('rotacion_baja', $result['conditions']);
    }

    public function test_auditoria_pendiente_sin_fecha(): void
    {
        $store = [
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => '',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertContains('auditoria_pendiente', $result['conditions']);
    }

    public function test_auditoria_pendiente_mas_3_meses(): void
    {
        $store = [
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => now()->subMonths(6)->format('Y-m-d'),
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertContains('auditoria_pendiente', $result['conditions']);
    }

    public function test_auditoria_reciente_no_pendiente(): void
    {
        $store = [
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => now()->subMonth()->format('Y-m-d'),
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertNotContains('auditoria_pendiente', $result['conditions']);
    }

    public function test_estado_comite_vigente(): void
    {
        $store = [
            'Vigencia' => '2030-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => '',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertSame('vigente', $result['estadoComite']);
    }

    public function test_estado_comite_proximo_a_vencer(): void
    {
        $store = [
            'Vigencia' => now()->addDays(15)->format('Y-m-d'),
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => '',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertSame('proximo_a_vencer', $result['estadoComite']);
    }

    public function test_estado_comite_sin_fecha(): void
    {
        $store = [
            'Vigencia' => '',
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => '',
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertSame('sin_fecha', $result['estadoComite']);
    }

    public function test_nivel_rojo(): void
    {
        $store = [
            'Vigencia' => '2020-01-01',
            'Imp_Res_Audi_Mes' => '600000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '100000',
            'Fch_Audit' => now()->subYears(2)->format('Y-m-d'),
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertSame('rojo', $result['level']);
    }

    public function test_nivel_amarillo(): void
    {
        $store = [
            'Vigencia' => '2020-01-01',
            'Imp_Res_Audi_Mes' => '100000',
            'Cap_Tot' => '100000',
            'Vta_Mes' => '200000',
            'Fch_Audit' => now()->subMonth()->format('Y-m-d'),
        ];

        $result = $this->servicio->evaluarTienda($store);

        $this->assertSame('amarillo', $result['level']);
    }

    public function test_calcular_kpis(): void
    {
        $stores = [
            ['_audit' => ['conditions' => ['comite_vencido', 'auditoria_alta']]],
            ['_audit' => ['conditions' => ['rotacion_baja']]],
            ['_audit' => ['conditions' => []]],
        ];

        $kpis = $this->servicio->calcularKpis($stores);

        $this->assertSame(1, $kpis['comitesVencidos']);
        $this->assertSame(1, $kpis['auditoriaAlta']);
        $this->assertSame(1, $kpis['rotacionBaja']);
        $this->assertSame(0, $kpis['auditoriaPendiente']);
    }
}

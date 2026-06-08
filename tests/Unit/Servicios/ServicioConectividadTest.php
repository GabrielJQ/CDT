<?php

namespace Tests\Unit\Servicios;

use App\Servicios\ServicioConectividad;
use PHPUnit\Framework\TestCase;

class ServicioConectividadTest extends TestCase
{
    private ServicioConectividad $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioConectividad;
    }

    public function test_calcular_kpis_vacio(): void
    {
        $kpis = $this->servicio->calcularKpis([]);

        $this->assertSame(0, $kpis['_total']);
        $this->assertSame(0, $kpis['TELEFONIA']['yes']);
        $this->assertSame(0, $kpis['TELEFONIA']['no']);
        $this->assertSame(0, $kpis['TELEFONIA']['undef']);
    }

    public function test_calcular_kpis_todo_s(): void
    {
        $stores = [[
            'TELEFONIA' => 'S',
            'INTERNET' => 'S',
            'Señal de celular' => 'S',
            'Compañía' => 'Telcel',
        ]];

        $kpis = $this->servicio->calcularKpis($stores);

        $this->assertSame(1, $kpis['_total']);
        foreach (['TELEFONIA', 'INTERNET', 'Señal de celular'] as $key) {
            $this->assertSame(1, $kpis[$key]['yes']);
            $this->assertSame(0, $kpis[$key]['no']);
            $this->assertSame(0, $kpis[$key]['undef']);
        }
    }

    public function test_calcular_kpis_mixto(): void
    {
        $stores = [
            ['TELEFONIA' => 'S', 'INTERNET' => 'N', 'Señal de celular' => 'S', 'Compañía' => ''],
            ['TELEFONIA' => 'N', 'INTERNET' => 'S', 'Señal de celular' => 'N', 'Compañía' => ''],
        ];

        $kpis = $this->servicio->calcularKpis($stores);

        $this->assertSame(2, $kpis['_total']);
        $this->assertSame(1, $kpis['TELEFONIA']['yes']);
        $this->assertSame(1, $kpis['TELEFONIA']['no']);
        $this->assertSame(0, $kpis['TELEFONIA']['undef']);
    }

    public function test_kpis_porcentajes(): void
    {
        $stores = [
            ['TELEFONIA' => 'S', 'INTERNET' => 'S', 'Señal de celular' => 'S', 'Compañía' => ''],
            ['TELEFONIA' => 'N', 'INTERNET' => 'N', 'Señal de celular' => 'N', 'Compañía' => ''],
            ['TELEFONIA' => '', 'INTERNET' => '', 'Señal de celular' => '', 'Compañía' => ''],
        ];

        $kpis = $this->servicio->calcularKpis($stores);

        $this->assertSame(3, $kpis['_total']);
        $this->assertSame(33.0, $kpis['TELEFONIA']['pctYes']);
    }

    public function test_valores_indefinidos(): void
    {
        $stores = [
            ['TELEFONIA' => 'S', 'INTERNET' => '', 'Señal de celular' => '', 'Compañía' => ''],
            ['TELEFONIA' => '', 'INTERNET' => '', 'Señal de celular' => '', 'Compañía' => ''],
        ];

        $kpis = $this->servicio->calcularKpis($stores);

        $this->assertSame(1, $kpis['TELEFONIA']['undef']);
        $this->assertSame(2, $kpis['INTERNET']['undef']);
    }

    public function test_distribucion_compania(): void
    {
        $stores = [
            ['TELEFONIA' => 'S', 'INTERNET' => 'S', 'Señal de celular' => 'S', 'Compañía' => 'Telcel'],
            ['TELEFONIA' => 'S', 'INTERNET' => 'S', 'Señal de celular' => 'S', 'Compañía' => 'Movistar'],
            ['TELEFONIA' => 'S', 'INTERNET' => 'S', 'Señal de celular' => 'S', 'Compañía' => 'Telcel'],
        ];

        $kpis = $this->servicio->calcularKpis($stores);

        $this->assertSame(2, $kpis['_compania']['Telcel']['count']);
        $this->assertSame(1, $kpis['_compania']['Movistar']['count']);
    }

    public function test_distribucion_solo_con_senial(): void
    {
        $stores = [
            ['TELEFONIA' => 'S', 'INTERNET' => 'S', 'Señal de celular' => 'N', 'Compañía' => 'Telcel'],
            ['TELEFONIA' => 'S', 'INTERNET' => 'S', 'Señal de celular' => 'S', 'Compañía' => 'AT&T'],
        ];

        $kpis = $this->servicio->calcularKpis($stores);

        $this->assertArrayNotHasKey('Telcel', $kpis['_compania']);
        $this->assertSame(1, $kpis['_compania']['AT&T']['count']);
    }

    public function test_contar_sin_conectividad(): void
    {
        $stores = [
            ['TELEFONIA' => 'N', 'INTERNET' => 'N', 'Señal de celular' => 'N'],
            ['TELEFONIA' => 'S', 'INTERNET' => 'S', 'Señal de celular' => 'S'],
        ];

        $count = $this->servicio->contarSinConectividad($stores);

        $this->assertSame(1, $count);
    }

    public function test_resumen_simple(): void
    {
        $stores = [
            ['TELEFONIA' => 'S', 'INTERNET' => 'S', 'Señal de celular' => 'S'],
            ['TELEFONIA' => 'N', 'INTERNET' => 'S', 'Señal de celular' => 'S'],
        ];

        $resumen = $this->servicio->resumenSimple($stores);

        $this->assertSame(1, $resumen['TELEFONIA']['yes']);
        $this->assertSame(50.0, $resumen['TELEFONIA']['pctYes']);
        $this->assertArrayNotHasKey('no', $resumen['TELEFONIA']);
        $this->assertArrayNotHasKey('undef', $resumen['TELEFONIA']);
    }
}

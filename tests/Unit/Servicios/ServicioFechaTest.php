<?php

namespace Tests\Unit\Servicios;

use App\Servicios\ServicioFecha;
use PHPUnit\Framework\TestCase;

class ServicioFechaTest extends TestCase
{
    private ServicioFecha $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioFecha;
    }

    public function test_null_retorna_null(): void
    {
        $this->assertNull($this->servicio->parsear(null));
    }

    public function test_vacio_retorna_null(): void
    {
        $this->assertNull($this->servicio->parsear(''));
    }

    public function test_cero_retorna_null(): void
    {
        $this->assertNull($this->servicio->parsear('0'));
    }

    public function test_formato_Y_m_d(): void
    {
        $fecha = $this->servicio->parsear('2026-01-15');
        $this->assertNotNull($fecha);
        $this->assertSame(2026, $fecha->year);
        $this->assertSame(1, $fecha->month);
        $this->assertSame(15, $fecha->day);
    }

    public function test_formato_d_m_Y(): void
    {
        $fecha = $this->servicio->parsear('15/01/2026');
        $this->assertNotNull($fecha);
        $this->assertSame(2026, $fecha->year);
        $this->assertSame(1, $fecha->month);
        $this->assertSame(15, $fecha->day);
    }

    public function test_formato_Y_m_d_barra(): void
    {
        $fecha = $this->servicio->parsear('2026/01/15');
        $this->assertNotNull($fecha);
        $this->assertSame(2026, $fecha->year);
        $this->assertSame(1, $fecha->month);
        $this->assertSame(15, $fecha->day);
    }

    public function test_formato_d_m_Y_guion(): void
    {
        $fecha = $this->servicio->parsear('15-01-2026');
        $this->assertNotNull($fecha);
        $this->assertSame(2026, $fecha->year);
        $this->assertSame(1, $fecha->month);
        $this->assertSame(15, $fecha->day);
    }

    public function test_fallback_carbon_parse(): void
    {
        $fecha = $this->servicio->parsear('January 15, 2026');
        $this->assertNotNull($fecha);
        $this->assertSame(2026, $fecha->year);
        $this->assertSame(1, $fecha->month);
        $this->assertSame(15, $fecha->day);
    }

    public function test_fecha_invalida_retorna_null(): void
    {
        $this->assertNull($this->servicio->parsear('no-es-fecha'));
    }

    public function test_formato_explicito_permite_anio_menor_2000(): void
    {
        $fecha = $this->servicio->parsear('1999-01-01');
        $this->assertNotNull($fecha);
        $this->assertSame(1999, $fecha->year);
    }

    public function test_espacios_trim(): void
    {
        $fecha = $this->servicio->parsear('  2026-01-15  ');
        $this->assertNotNull($fecha);
        $this->assertSame(15, $fecha->day);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuditoriaControllerTest extends TestCase
{
    private function fakeOk(): void
    {
        // PostgreSQL is faked globally in TestCase.
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_index_returns_200(): void
    {
        $this->fakeOk();

        $response = $this->get('/auditoria');

        $response->assertStatus(200);
        $response->assertSee('Auditor');
    }

    public function test_filter_by_nivel(): void
    {
        $this->fakeOk();

        $response = $this->get('/auditoria?nivel=rojo');

        $response->assertStatus(200);
    }

    public function test_filter_by_estado_comite(): void
    {
        $this->fakeOk();

        $response = $this->get('/auditoria?estado_comite=vencido');

        $response->assertStatus(200);
    }

    public function test_filter_by_estado_auditoria(): void
    {
        $this->fakeOk();

        $response = $this->get('/auditoria?estado_auditoria=vencida');

        $response->assertStatus(200);
    }

    public function test_filter_by_rotacion(): void
    {
        $this->fakeOk();

        $response = $this->get('/auditoria?rango_rotacion=critico');

        $response->assertStatus(200);
    }

    public function test_filter_by_asambleas(): void
    {
        $this->fakeOk();

        $response = $this->get('/auditoria?asambleas_mes=si');

        $response->assertStatus(200);
    }

    public function test_export_csv(): void
    {
        $this->fakeOk();

        $response = $this->get('/auditoria?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=auditoria.csv');
    }
}

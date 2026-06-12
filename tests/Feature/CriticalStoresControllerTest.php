<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CriticalStoresControllerTest extends TestCase
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

        $response = $this->get('/informacion-tiendas');

        $response->assertStatus(200);
        $response->assertSee('Informaci');
    }

    public function test_filter_by_nivel(): void
    {
        $this->fakeOk();

        $response = $this->get('/informacion-tiendas?nivel=rojo');

        $response->assertStatus(200);
    }

    public function test_filter_by_indicador(): void
    {
        $this->fakeOk();

        $response = $this->get('/informacion-tiendas?indicador=comite_vencido');

        $response->assertStatus(200);
    }

    public function test_filter_by_almacen(): void
    {
        $this->fakeOk();

        $response = $this->get('/informacion-tiendas?almacen=OAXACA');

        $response->assertStatus(200);
    }

    public function test_export_csv(): void
    {
        $this->fakeOk();

        $response = $this->get('/informacion-tiendas?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=informacion-tiendas.csv');
    }

    public function test_shows_semaphore_summary(): void
    {
        $this->fakeOk();

        $response = $this->get('/informacion-tiendas');

        $response->assertStatus(200);
        $response->assertSee('rojo');
    }
}

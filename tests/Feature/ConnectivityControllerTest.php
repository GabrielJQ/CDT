<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConnectivityControllerTest extends TestCase
{
    private function fakeOk(): void
    {
        // PostgreSQL is faked globally in TestCase.
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->signIn();
    }

    public function test_index_returns_200(): void
    {
        $this->fakeOk();

        $response = $this->get('/conectividad');

        $response->assertStatus(200);
        $response->assertSee('Conectividad');
    }

    public function test_filter_by_almacen(): void
    {
        $this->fakeOk();

        $response = $this->get('/conectividad?almacen=OAXACA');

        $response->assertStatus(200);
        $response->assertSee('Conectividad');
    }

    public function test_filter_by_telefono_si(): void
    {
        $this->fakeOk();

        $response = $this->get('/conectividad?telefono=si');

        $response->assertStatus(200);
    }

    public function test_filter_by_telefono_no(): void
    {
        $this->fakeOk();

        $response = $this->get('/conectividad?telefono=no');

        $response->assertStatus(200);
    }

    public function test_filter_by_internet_si(): void
    {
        $this->fakeOk();

        $response = $this->get('/conectividad?internet=si');

        $response->assertStatus(200);
    }

    public function test_filter_by_compania(): void
    {
        $this->fakeOk();

        $response = $this->get('/conectividad?compania=Telcel');

        $response->assertStatus(200);
    }

    public function test_export_csv(): void
    {
        $this->fakeOk();

        $response = $this->get('/export/conectividad');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=conectividad.csv');
    }

    public function test_export_csv_with_filter(): void
    {
        $this->fakeOk();

        $response = $this->get('/export/conectividad?almacen=OAXACA');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=conectividad.csv');
    }

    public function test_shows_kpi_cards(): void
    {
        $this->fakeOk();

        $response = $this->get('/conectividad');

        $response->assertStatus(200);
        $response->assertSee('Teléfono');
    }
}

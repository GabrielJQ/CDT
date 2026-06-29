<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AperturaControllerTest extends TestCase
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

        $response = $this->get('/aperturas');

        $response->assertStatus(200);
        $response->assertSee('Aperturas');
    }

    public function test_filter_by_almacen(): void
    {
        $this->fakeOk();

        $response = $this->get('/aperturas?almacen=OAXACA');

        $response->assertStatus(200);
    }

    public function test_filter_by_date_range(): void
    {
        $this->fakeOk();

        $response = $this->get('/aperturas?desde=01/01/2024&hasta=31/12/2024');

        $response->assertStatus(200);
    }

    public function test_export_csv(): void
    {
        $this->fakeOk();

        $response = $this->get('/export/aperturas');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=aperturas.csv');
    }

    public function test_export_csv_with_filter(): void
    {
        $this->fakeOk();

        $response = $this->get('/export/aperturas?desde=2024-01-01');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=aperturas.csv');
    }
}

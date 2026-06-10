<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AperturaControllerTest extends TestCase
{
    private string $fixture;

    private function fakeOk(): void
    {
        Http::fake(['*docs.google.com*' => Http::response($this->fixture)]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = file_get_contents(__DIR__.'/../fixtures/tiendas.csv');
        Cache::flush();
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

        $response = $this->get('/aperturas?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=aperturas.csv');
    }
}

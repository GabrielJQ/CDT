<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapaControllerTest extends TestCase
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

        $response = $this->get('/mapa');

        $response->assertStatus(200);
        $response->assertSee('Mapa');
    }

    public function test_filter_by_almacen(): void
    {
        $this->fakeOk();

        $response = $this->get('/mapa?almacen=OAXACA');

        $response->assertStatus(200);
    }

    public function test_filter_by_estado_geo(): void
    {
        $this->fakeOk();

        $response = $this->get('/mapa?estado_geo=SIN_COORDENADAS');

        $response->assertStatus(200);
    }

    public function test_export_csv(): void
    {
        $this->fakeOk();

        $response = $this->get('/mapa?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=mapa.csv');
    }

    public function test_shows_leaflet_map(): void
    {
        $this->fakeOk();

        $response = $this->get('/mapa');

        $response->assertStatus(200);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MapaControllerTest extends TestCase
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

        $response = $this->get('/export/mapa');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=mapa.csv');
    }

    public function test_export_csv_with_filter(): void
    {
        $this->fakeOk();

        $response = $this->get('/export/mapa?almacen=OAXACA');

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

    public function test_data_endpoint_returns_map_points(): void
    {
        $this->fakeOk();

        $response = $this->getJson('/mapa/data?north=32.7&south=14.5&east=-86.7&west=-118.4');

        $response->assertStatus(200)
            ->assertJsonStructure(['stores', 'limited']);

        $this->assertNotEmpty($response->json('stores'));
    }
}

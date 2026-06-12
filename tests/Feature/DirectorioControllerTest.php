<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DirectorioControllerTest extends TestCase
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

        $response = $this->get('/directorio');

        $response->assertStatus(200);
        $response->assertSee('Directorio');
    }

    public function test_export_csv(): void
    {
        $this->fakeOk();

        $response = $this->get('/directorio?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=directorio.csv');
    }

    public function test_shows_store_table(): void
    {
        $this->fakeOk();

        $response = $this->get('/directorio');

        $response->assertStatus(200);
        $response->assertSee('OAXACA CENTRO');
    }

    public function test_shows_stats(): void
    {
        $this->fakeOk();

        $response = $this->get('/directorio');

        $response->assertStatus(200);
        $response->assertSee('Incompletos');
    }
}

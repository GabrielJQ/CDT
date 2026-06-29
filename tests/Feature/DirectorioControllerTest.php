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
        $this->signIn();
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

        $response = $this->get('/export/directorio');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename=directorio.xlsx');
    }

    public function test_export_csv_with_filter(): void
    {
        $this->fakeOk();

        $response = $this->get('/export/directorio?q=OAXACA');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename=directorio.xlsx');
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

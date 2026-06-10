<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

    public function test_dashboard_returns_200(): void
    {
        $this->fakeOk();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_dashboard_shows_total_stores(): void
    {
        $this->fakeOk();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('6');
    }

    public function test_dashboard_shows_connectivity_chart(): void
    {
        $this->fakeOk();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('chart-connectivity');
    }

    public function test_dashboard_shows_critical_stores_chart(): void
    {
        $this->fakeOk();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('chart-critical');
    }

    public function test_dashboard_shows_chartjs_script(): void
    {
        $this->fakeOk();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('chartjs-ready');
    }

    public function test_refresh_updates_data(): void
    {
        $this->fakeOk();

        $response = $this->post('/refresh');

        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    public function test_refresh_with_failure_shows_error(): void
    {
        Http::fake(['*docs.google.com*' => Http::response('Server Error', 500)]);

        $response = $this->post('/refresh');

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    public function test_dashboard_with_region_filter(): void
    {
        $this->fakeOk();

        $response = $this->withCookie('region_filter', 'U.O. OAXACA')->get('/');

        $response->assertStatus(200);
    }

    public function test_dashboard_graceful_degradation(): void
    {
        Http::fake(['*docs.google.com*' => Http::response('', 500)]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }
}

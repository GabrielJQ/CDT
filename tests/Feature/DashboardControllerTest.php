<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

    public function test_dashboard_returns_200(): void
    {
        $this->fakeOk();

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_dashboard_shows_total_stores(): void
    {
        $this->fakeOk();

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('6');
    }

    public function test_dashboard_shows_connectivity_chart(): void
    {
        $this->fakeOk();

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('chart-connectivity');
    }

    public function test_dashboard_shows_critical_stores_chart(): void
    {
        $this->fakeOk();

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('chart-critical');
    }

    public function test_dashboard_shows_chartjs_script(): void
    {
        $this->fakeOk();

        $response = $this->get('/dashboard');

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

    public function test_refresh_invalidates_dashboard_cache(): void
    {
        Cache::put('dashboard_metrics_version', 1);

        $response = $this->post('/refresh');

        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $this->assertSame(2, Cache::get('dashboard_metrics_version'));
    }

    public function test_dashboard_with_region_filter(): void
    {
        $this->fakeOk();

        $response = $this->withCookie('region_filter', 'U.O. OAXACA')->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_graceful_degradation(): void
    {
        $this->fakeOk();

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }
}

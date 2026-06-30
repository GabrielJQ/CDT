<?php

namespace Tests\Feature\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ConnectivityTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->signIn();
    }

    public function test_component_renders_connectivity_data(): void
    {
        Livewire::test('connectivity-table')
            ->assertStatus(200)
            ->assertSee('Módulo operativo')
            ->assertSee('Exportar reporte')
            ->assertSee('Tiendas mostradas')
            ->assertSee('Teléfono fijo')
            ->assertSee('Internet');
    }

    public function test_component_filters_without_full_page_request(): void
    {
        Livewire::test('connectivity-table')
            ->set('telefono', 'no')
            ->assertSet('page', 1)
            ->assertSee('Tiendas mostradas')
            ->assertSee('No');
    }

    public function test_component_sorts_allowed_columns(): void
    {
        Livewire::test('connectivity-table')
            ->call('sortBy', 'INTERNET')
            ->assertSet('sort', 'INTERNET')
            ->assertSet('direction', 'asc')
            ->call('sortBy', 'INTERNET')
            ->assertSet('direction', 'desc');
    }
}

<?php

namespace Tests\Feature\Livewire;

use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AperturasTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->signIn();
    }

    public function test_component_renders_aperturas_data(): void
    {
        Livewire::test('aperturas-table')
            ->assertStatus(200)
            ->assertSee('Módulo operativo')
            ->assertSee('Apertura de Tiendas')
            ->assertSee('Exportar CSV')
            ->assertSee('Tiendas mostradas')
            ->assertSee('Abiertas este año');
    }

    public function test_component_filters_without_full_page_request(): void
    {
        Livewire::test('aperturas-table')
            ->set('almacen', 'OAXACA')
            ->assertSet('page', 1)
            ->assertSee('Tiendas mostradas');
    }

    public function test_component_sorts_allowed_columns(): void
    {
        Livewire::test('aperturas-table')
            ->call('sortBy', '_fecha_apertura')
            ->assertSet('sort', '_fecha_apertura')
            ->assertSet('direction', 'asc')
            ->call('sortBy', '_fecha_apertura')
            ->assertSet('direction', 'desc');
    }
}

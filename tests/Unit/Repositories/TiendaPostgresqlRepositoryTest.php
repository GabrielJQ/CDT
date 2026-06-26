<?php

namespace Tests\Unit\Repositories;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Models\Tienda;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TiendaPostgresqlRepositoryTest extends TestCase
{
    private TiendaRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTiendasTable();
        $this->seedTiendas();

        $this->repository = app(TiendaRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        Schema::connection('pgsql_imports')->dropIfExists('tiendas');
        parent::tearDown();
    }

    private function createTiendasTable(): void
    {
        Schema::connection('pgsql_imports')->create('tiendas', function ($table) {
            $table->id();
            $table->string('Clave_Sucursal')->nullable();
            $table->string('Nombre_Almacen')->nullable();
            $table->string('No_Tienda_Actual')->nullable();
            $table->string('Clave_Regional')->nullable();
            $table->string('Nombre_Regional')->nullable();
            $table->string('Clave_UniOpe')->nullable();
            $table->string('Nombre_UniOpe')->nullable();
            $table->string('Municipio')->nullable();
            $table->string('Estado')->nullable();
            $table->string('TELEFONIA')->nullable();
            $table->string('Señal de celular')->nullable();
            $table->string('Compañía')->nullable();
            $table->string('INTERNET')->nullable();
            $table->string('CORREO')->nullable();
            $table->string('Direccion')->nullable();
            $table->string('Domicilio')->nullable();
            $table->string('Colonia')->nullable();
            $table->decimal('Vta_Mes', 12)->nullable();
            $table->decimal('VtaNeta_Mes', 12)->nullable();
            $table->decimal('Cap_Tot', 12)->nullable();
            $table->decimal('Cap_Com', 12)->nullable();
            $table->decimal('Cap_Dic', 12)->nullable();
            $table->decimal('Pagare_Monto', 12)->nullable();
            $table->date('Pagare_Fecha')->nullable();
            $table->date('Fec_CRA')->nullable();
            $table->date('Vigencia')->nullable();
            $table->date('Fch_Audit')->nullable();
            $table->decimal('Imp_Res_Audi_Mes', 12)->nullable();
            $table->integer('Audit_Realiza_Mes')->nullable();
            $table->integer('Asam_Real_Mes')->nullable();
            $table->integer('Asam_Prog_Mes')->nullable();
            $table->decimal('Latitud', 10, 7)->nullable();
            $table->decimal('Longitud', 10, 7)->nullable();
            $table->string('nivel_critico', 20)->nullable();
            $table->integer('factores_criticos_count')->nullable();
            $table->string('estado_comite', 30)->nullable();
            $table->string('rango_rotacion', 20)->nullable();
            $table->boolean('auditoria_pendiente')->nullable();
            $table->string('estado_geo', 30)->nullable();
            $table->boolean('es_activo')->default(true);
            $table->boolean('es_tienda_salud')->nullable();
            $table->date('Fecha_Apertura')->nullable();
            $table->timestamps();
        });
    }

    private function seedTiendas(): void
    {
        $now = now();

        $columns = $this->getTiendaColumns();

        Tienda::query()->insert(
            array_map(fn (array $row) => array_merge(array_fill_keys($columns, null), $row), [
                [
                    'Clave_Sucursal' => 'SUC_001',
                    'Nombre_Almacen' => 'OAXACA CENTRO',
                    'No_Tienda_Actual' => '001',
                    'Clave_Regional' => 'REG_01',
                    'Nombre_Regional' => 'OAXACA',
                    'Clave_UniOpe' => 'UO_01',
                    'Nombre_UniOpe' => 'CENTRO',
                    'Municipio' => 'Oaxaca de Juárez',
                    'Estado' => 'Oaxaca',
                    'TELEFONIA' => 'S',
                    'Señal de celular' => 'S',
                    'Compañía' => 'Telcel',
                    'INTERNET' => 'S',
                    'es_activo' => true,
                    'nivel_critico' => 'verde',
                    'es_tienda_salud' => false,
                    'Fecha_Apertura' => '2024-01-15',
                    'Vta_Mes' => 100000,
                    'Cap_Tot' => 50000,
                    'Cap_Dic' => 40000,
                    'Vigencia' => $now->addMonths(6)->toDateString(),
                    'Fch_Audit' => $now->subMonth()->toDateString(),
                    'Imp_Res_Audi_Mes' => 10000,
                    'Audit_Realiza_Mes' => 1,
                    'Asam_Real_Mes' => 2,
                    'Asam_Prog_Mes' => 3,
                    'Latitud' => 17.0654,
                    'Longitud' => -96.7236,
                    'estado_geo' => 'OK',
                    'factores_criticos_count' => 0,
                    'estado_comite' => 'vigente',
                    'rango_rotacion' => 'optimo',
                    'auditoria_pendiente' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'Clave_Sucursal' => 'SUC_002',
                    'Nombre_Almacen' => 'ISTMO SUR',
                    'No_Tienda_Actual' => '002',
                    'Clave_Regional' => 'REG_01',
                    'Nombre_Regional' => 'OAXACA',
                    'Clave_UniOpe' => 'UO_02',
                    'Nombre_UniOpe' => 'ISTMO',
                    'Municipio' => 'Salina Cruz',
                    'Estado' => 'Oaxaca',
                    'TELEFONIA' => 'N',
                    'Señal de celular' => 'N',
                    'Compañía' => 'Movistar',
                    'INTERNET' => 'N',
                    'es_activo' => true,
                    'nivel_critico' => 'rojo',
                    'es_tienda_salud' => false,
                    'Fecha_Apertura' => '2023-06-01',
                    'Vta_Mes' => 5000,
                    'Cap_Tot' => 10000,
                    'Cap_Dic' => 5000,
                    'Vigencia' => $now->subMonth()->toDateString(),
                    'Fch_Audit' => $now->subMonths(6)->toDateString(),
                    'Imp_Res_Audi_Mes' => 600000,
                    'Audit_Realiza_Mes' => 0,
                    'Asam_Real_Mes' => 0,
                    'Asam_Prog_Mes' => 1,
                    'Latitud' => 16.1750,
                    'Longitud' => -95.1950,
                    'estado_geo' => 'OK',
                    'factores_criticos_count' => 5,
                    'estado_comite' => 'vencido',
                    'rango_rotacion' => 'critico',
                    'auditoria_pendiente' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'Clave_Sucursal' => 'SUC_003',
                    'Nombre_Almacen' => 'MÉRIDA CENTRO',
                    'No_Tienda_Actual' => '003',
                    'Clave_Regional' => 'REG_09',
                    'Nombre_Regional' => 'PENINSULAR',
                    'Clave_UniOpe' => 'UO_26',
                    'Nombre_UniOpe' => 'YUCATAN',
                    'Municipio' => 'Mérida',
                    'Estado' => 'Yucatán',
                    'TELEFONIA' => 'S',
                    'Señal de celular' => 'S',
                    'Compañía' => 'Telcel',
                    'INTERNET' => 'S',
                    'es_activo' => true,
                    'nivel_critico' => 'amarillo',
                    'es_tienda_salud' => true,
                    'Fecha_Apertura' => '2025-03-10',
                    'Vta_Mes' => 200000,
                    'Cap_Tot' => 150000,
                    'Cap_Dic' => 120000,
                    'Vigencia' => $now->addYear()->toDateString(),
                    'Fch_Audit' => $now->subDays(15)->toDateString(),
                    'Imp_Res_Audi_Mes' => 200000,
                    'Audit_Realiza_Mes' => 1,
                    'Asam_Real_Mes' => 1,
                    'Asam_Prog_Mes' => 1,
                    'Latitud' => 20.9675,
                    'Longitud' => -89.6236,
                    'estado_geo' => 'OK',
                    'factores_criticos_count' => 2,
                    'estado_comite' => 'vigente',
                    'rango_rotacion' => 'optimo',
                    'auditoria_pendiente' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'Clave_Sucursal' => 'SUC_004',
                    'Nombre_Almacen' => 'INACTIVA STORE',
                    'No_Tienda_Actual' => '004',
                    'Clave_Regional' => 'REG_01',
                    'Nombre_Regional' => 'OAXACA',
                    'Clave_UniOpe' => 'UO_01',
                    'Nombre_UniOpe' => 'CENTRO',
                    'es_activo' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])
        );
    }

    private function getTiendaColumns(): array
    {
        return [
            'Clave_Sucursal', 'Nombre_Almacen', 'No_Tienda_Actual', 'Clave_Regional',
            'Nombre_Regional', 'Clave_UniOpe', 'Nombre_UniOpe', 'Municipio', 'Estado',
            'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET', 'CORREO',
            'Direccion', 'Domicilio', 'Colonia', 'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot',
            'Cap_Com', 'Cap_Dic', 'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia',
            'Fch_Audit', 'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes', 'Asam_Real_Mes',
            'Asam_Prog_Mes', 'Latitud', 'Longitud', 'nivel_critico', 'factores_criticos_count',
            'estado_comite', 'rango_rotacion', 'auditoria_pendiente', 'estado_geo',
            'es_activo', 'es_tienda_salud', 'Fecha_Apertura', 'created_at', 'updated_at',
        ];
    }

    public function test_find_returns_tienda(): void
    {
        $tienda = $this->repository->find(1);

        $this->assertNotNull($tienda);
        $this->assertEquals('OAXACA CENTRO', $tienda->Nombre_Almacen);
    }

    public function test_find_returns_null_for_missing(): void
    {
        $this->assertNull($this->repository->find(999));
    }

    public function test_find_active_returns_tienda_only_if_active(): void
    {
        $this->assertNotNull($this->repository->findActive(1));
        $this->assertNull($this->repository->findActive(4));
    }

    public function test_get_active_returns_all_active_stores(): void
    {
        $stores = $this->repository->getActive();

        $this->assertCount(3, $stores);
    }

    public function test_get_active_filters_by_region(): void
    {
        $stores = $this->repository->getActive(['region' => 'REG_09']);

        $this->assertCount(1, $stores);
        $this->assertEquals('MÉRIDA CENTRO', $stores->first()->Nombre_Almacen);
    }

    public function test_get_active_filters_by_uo(): void
    {
        $stores = $this->repository->getActive(['uo' => 'UO_02']);

        $this->assertCount(1, $stores);
        $this->assertEquals('ISTMO SUR', $stores->first()->Nombre_Almacen);
    }

    public function test_get_active_filters_by_region_and_uo(): void
    {
        $stores = $this->repository->getActive(['region' => 'REG_01', 'uo' => 'UO_01']);

        $this->assertCount(1, $stores);
        $this->assertEquals('OAXACA CENTRO', $stores->first()->Nombre_Almacen);
    }

    public function test_get_active_returns_empty_for_nonexistent_region(): void
    {
        $stores = $this->repository->getActive(['region' => 'REG_99']);

        $this->assertCount(0, $stores);
    }

    public function test_get_active_selects_specific_columns(): void
    {
        $stores = $this->repository->getActive([], ['Nombre_Almacen']);

        $this->assertCount(3, $stores);
        $this->assertNotNull($stores->first()->Nombre_Almacen);
    }

    public function test_count_active_returns_total_count(): void
    {
        $this->assertEquals(3, $this->repository->countActive());
    }

    public function test_count_active_filters_by_region(): void
    {
        $this->assertEquals(1, $this->repository->countActive(['region' => 'REG_09']));
    }

    public function test_get_companias_returns_distinct_sorted(): void
    {
        $companias = $this->repository->getCompanias();

        $this->assertEquals(['Movistar', 'Telcel'], $companias);
    }

    public function test_get_companias_filters_by_region(): void
    {
        $companias = $this->repository->getCompanias(['region' => 'REG_09']);

        $this->assertEquals(['Telcel'], $companias);
    }

    public function test_get_jerarquia_regional_returns_grouped_hierarchy(): void
    {
        $rows = $this->repository->getJerarquiaRegional();

        $this->assertCount(2, $rows);
        $this->assertSame('REG_01', $rows[0]['clave']);
        $this->assertSame('OAXACA', $rows[0]['nombre']);
        $this->assertSame(2, $rows[0]['total']);
        $this->assertSame(2, $rows[0]['almacenes']);
        $this->assertCount(2, $rows[0]['uos']);
        $this->assertSame('UO_01', $rows[0]['uos'][0]['clave']);
        $this->assertSame('UO_02', $rows[0]['uos'][1]['clave']);
        $this->assertSame('REG_09', $rows[1]['clave']);
        $this->assertSame('PENINSULAR', $rows[1]['nombre']);
        $this->assertSame(1, $rows[1]['total']);
        $this->assertSame(1, $rows[1]['almacenes']);
        $this->assertCount(1, $rows[1]['uos']);
        $this->assertSame('UO_26', $rows[1]['uos'][0]['clave']);
    }

    public function test_get_jerarquia_operativa_returns_regions_and_uos(): void
    {
        $result = $this->repository->getJerarquiaOperativa();

        $this->assertArrayHasKey('regions', $result);
        $this->assertArrayHasKey('regionNames', $result);
        $this->assertArrayHasKey('uos', $result);
        $this->assertContains('REG_01', $result['regions']);
        $this->assertContains('REG_09', $result['regions']);
        $this->assertEquals('PENINSULAR', $result['regionNames']['REG_09']);
        $this->assertArrayHasKey('UO_26', $result['uos']['REG_09']);
    }

    public function test_apply_region_scope_filters_builder(): void
    {
        $query = Tienda::query()->activo();
        $query = $this->repository->applyRegionScope($query, ['region' => 'REG_01', 'uo' => 'UO_02']);

        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals('ISTMO SUR', $results->first()->Nombre_Almacen);
    }

    public function test_apply_region_scope_with_empty_filters_does_nothing(): void
    {
        $query = Tienda::query()->activo();
        $query = $this->repository->applyRegionScope($query, []);

        $this->assertCount(3, $query->get());
    }

    public function test_paginate_filtered_returns_paginator(): void
    {
        $query = Tienda::query()->activo();
        $paginator = $this->repository->paginateFiltered($query, 1, 2);

        $this->assertCount(2, $paginator->items());
        $this->assertEquals(3, $paginator->total());
        $this->assertEquals(1, $paginator->currentPage());
        $this->assertEquals(2, $paginator->perPage());
    }

    public function test_paginate_filtered_with_sort(): void
    {
        $query = Tienda::query()->activo();
        $paginator = $this->repository->paginateFiltered($query, 1, 10, [
            'column' => 'Nombre_Almacen',
            'direction' => 'asc',
        ]);

        $names = collect($paginator->items())->pluck('Nombre_Almacen')->toArray();
        $this->assertEquals(['ISTMO SUR', 'MÉRIDA CENTRO', 'OAXACA CENTRO'], $names);
    }

    public function test_paginate_filtered_second_page(): void
    {
        $query = Tienda::query()->activo();
        $paginator = $this->repository->paginateFiltered($query, 2, 2);

        $this->assertCount(1, $paginator->items());
        $this->assertEquals(2, $paginator->currentPage());
    }

    public function test_yield_for_export_conectividad(): void
    {
        $rows = iterator_to_array(
            $this->repository->yieldForExport('conectividad', ['region' => 'REG_01'], ['telefono' => 'si'], ['Nombre_Almacen', 'TELEFONIA'])
        );

        $this->assertCount(1, $rows);
        $this->assertEquals('OAXACA CENTRO', $rows[0]['Nombre_Almacen']);
    }

    public function test_yield_for_export_auditoria(): void
    {
        $rows = iterator_to_array(
            $this->repository->yieldForExport('auditoria', ['region' => 'REG_01'], [], ['Nombre_Almacen', 'estado_comite'])
        );

        $this->assertCount(2, $rows);
    }

    public function test_yield_for_export_aperturas(): void
    {
        $rows = iterator_to_array(
            $this->repository->yieldForExport('aperturas', [], ['desde' => '2025-01-01'], ['Nombre_Almacen', 'Fecha_Apertura'])
        );

        $this->assertCount(1, $rows);
        $this->assertEquals('MÉRIDA CENTRO', $rows[0]['Nombre_Almacen']);
    }

    public function test_yield_for_export_mapa(): void
    {
        $rows = iterator_to_array(
            $this->repository->yieldForExport('mapa', ['region' => 'REG_09'], [], ['Nombre_Almacen', 'estado_geo'])
        );

        $this->assertCount(1, $rows);
    }

    public function test_yield_for_export_directorio(): void
    {
        $rows = iterator_to_array(
            $this->repository->yieldForExport('directorio', [], ['q' => 'OAXACA'], ['Nombre_Almacen'])
        );

        $this->assertCount(1, $rows);
        $this->assertEquals('OAXACA CENTRO', $rows[0]['Nombre_Almacen']);
    }

    public function test_yield_for_export_criticidad(): void
    {
        $rows = iterator_to_array(
            $this->repository->yieldForExport('criticidad', [], ['nivel' => 'rojo'], ['Nombre_Almacen', 'nivel_critico'])
        );

        $this->assertCount(1, $rows);
        $this->assertEquals('ISTMO SUR', $rows[0]['Nombre_Almacen']);
    }

    public function test_yield_for_export_respects_region(): void
    {
        $rows = iterator_to_array(
            $this->repository->yieldForExport('conectividad', ['region' => 'REG_09'], [], ['Nombre_Almacen'])
        );

        $this->assertCount(1, $rows);
        $this->assertEquals('MÉRIDA CENTRO', $rows[0]['Nombre_Almacen']);
    }
}

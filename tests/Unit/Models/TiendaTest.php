<?php

namespace Tests\Unit\Models;

use App\Models\Tienda;
use Tests\TestCase;

class TiendaTest extends TestCase
{
    private Tienda $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new Tienda;
    }

    public function test_connection_is_pgsql_imports(): void
    {
        $this->assertEquals('pgsql_imports', $this->model->getConnectionName());
    }

    public function test_table_is_tiendas(): void
    {
        $this->assertEquals('tiendas', $this->model->getTable());
    }

    public function test_primary_key_is_id(): void
    {
        $this->assertEquals('id', $this->model->getKeyName());
    }

    public function test_nivel_critico_returns_raw_when_set(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['nivel_critico' => 'rojo'], true);

        $this->assertEquals('rojo', $model->nivel_critico);
    }

    public function test_nivel_critico_computes_verde_when_null(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['nivel_critico' => null, 'factores_criticos_count' => 1], true);

        $this->assertEquals('verde', $model->nivel_critico);
    }

    public function test_nivel_critico_computes_amarillo_when_2_factores(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['nivel_critico' => null, 'factores_criticos_count' => 2], true);

        $this->assertEquals('amarillo', $model->nivel_critico);
    }

    public function test_nivel_critico_computes_rojo_when_4_factores(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['nivel_critico' => null, 'factores_criticos_count' => 5], true);

        $this->assertEquals('rojo', $model->nivel_critico);
    }

    public function test_nivel_critico_returns_null_when_both_null(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['nivel_critico' => null, 'factores_criticos_count' => null], true);

        $this->assertNull($model->nivel_critico);
    }

    public function test_estado_comite_returns_raw_when_set(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['estado_comite' => 'vigente'], true);

        $this->assertEquals('vigente', $model->estado_comite);
    }

    public function test_estado_comite_vencido_when_vigencia_in_past(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['estado_comite' => null, 'Vigencia' => '2024-01-01'], true);

        $this->assertEquals('vencido', $model->estado_comite);
    }

    public function test_estado_comite_proximo_when_vigencia_within_30_days(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['estado_comite' => null, 'Vigencia' => now()->addDays(15)->toDateString()], true);

        $this->assertEquals('proximo_a_vencer', $model->estado_comite);
    }

    public function test_estado_comite_vigente_when_future(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['estado_comite' => null, 'Vigencia' => now()->addYear()->toDateString()], true);

        $this->assertEquals('vigente', $model->estado_comite);
    }

    public function test_estado_comite_sin_fecha_when_vigencia_null(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['estado_comite' => null, 'Vigencia' => null], true);

        $this->assertEquals('sin_fecha', $model->estado_comite);
    }

    public function test_rango_rotacion_returns_raw_when_set(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['rango_rotacion' => 'optimo'], true);

        $this->assertEquals('optimo', $model->rango_rotacion);
    }

    public function test_rango_rotacion_cero_when_cap_dic_zero(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['rango_rotacion' => null, 'Cap_Dic' => 0, 'Vta_Mes' => 1000], true);

        $this->assertEquals('cero', $model->rango_rotacion);
    }

    public function test_rango_rotacion_cero_when_vta_mes_zero(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['rango_rotacion' => null, 'Cap_Dic' => 50000, 'Vta_Mes' => 0], true);

        $this->assertEquals('cero', $model->rango_rotacion);
    }

    public function test_rango_rotacion_critico(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['rango_rotacion' => null, 'Cap_Dic' => 50000, 'Vta_Mes' => 10000], true);

        $this->assertEquals('critico', $model->rango_rotacion);
    }

    public function test_rango_rotacion_amarillo(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['rango_rotacion' => null, 'Cap_Dic' => 50000, 'Vta_Mes' => 35000], true);

        $this->assertEquals('amarillo', $model->rango_rotacion);
    }

    public function test_rango_rotacion_optimo(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['rango_rotacion' => null, 'Cap_Dic' => 50000, 'Vta_Mes' => 60000], true);

        $this->assertEquals('optimo', $model->rango_rotacion);
    }

    public function test_auditoria_pendiente_returns_raw_when_set(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['auditoria_pendiente' => false], true);

        $this->assertFalse($model->auditoria_pendiente);
    }

    public function test_auditoria_pendiente_true_when_fch_audit_null(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['auditoria_pendiente' => null, 'Fch_Audit' => null], true);

        $this->assertTrue($model->auditoria_pendiente);
    }

    public function test_auditoria_pendiente_true_when_older_than_3_months(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['auditoria_pendiente' => null, 'Fch_Audit' => now()->subMonths(4)->toDateString()], true);

        $this->assertTrue($model->auditoria_pendiente);
    }

    public function test_auditoria_pendiente_false_when_recent(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['auditoria_pendiente' => null, 'Fch_Audit' => now()->subMonth()->toDateString()], true);

        $this->assertFalse($model->auditoria_pendiente);
    }

    public function test_factores_criticos_returns_raw_when_set(): void
    {
        $model = new Tienda;
        $model->setRawAttributes(['factores_criticos_count' => 3], true);

        $this->assertSame(3, $model->factores_criticos_count);
    }

    public function test_factores_criticos_counts_capital_bajo(): void
    {
        $model = new Tienda;
        $model->setRawAttributes([
            'factores_criticos_count' => null,
            'Cap_Tot' => 10000,
            'Cap_Dic' => 0,
            'estado_comite' => null,
            'Imp_Res_Audi_Mes' => 0,
            'Pagare_Fecha' => null,
            'rango_rotacion' => null,
            'Asam_Prog_Mes' => 0,
            'Asam_Real_Mes' => 0,
            'Vigencia' => null,
            'Fch_Audit' => null,
            'Vta_Mes' => 0,
        ], true);

        $this->assertSame(1, $model->factores_criticos_count);
    }

    public function test_factores_criticos_counts_multiple_factors(): void
    {
        $model = new Tienda;
        $model->setRawAttributes([
            'factores_criticos_count' => null,
            'Cap_Tot' => 10000,
            'Cap_Dic' => 15000,
            'estado_comite' => 'vencido',
            'Imp_Res_Audi_Mes' => 600000,
            'Pagare_Fecha' => '2022-01-01',
            'rango_rotacion' => 'critico',
            'Asam_Prog_Mes' => 3,
            'Asam_Real_Mes' => 0,
            'Vigencia' => null,
            'Fch_Audit' => null,
            'Vta_Mes' => 0,
        ], true);

        $this->assertSame(7, $model->factores_criticos_count);
    }

    public function test_scope_activo(): void
    {
        $query = Tienda::query()->activo();
        $sql = $query->toSql();

        $this->assertStringContainsString('"es_activo"', $sql);
        $this->assertStringContainsString('?', $sql);
        $this->assertEquals([true], $query->getBindings());
    }

    public function test_scope_regional(): void
    {
        $sql = Tienda::query()->regional('REG_09')->toSql();
        $this->assertStringContainsString('"Clave_Regional"', $sql);
        $this->assertStringContainsString('?', $sql);
    }

    public function test_scope_regional_ignores_empty(): void
    {
        $sql = Tienda::query()->regional('')->toSql();
        $this->assertStringNotContainsString('Clave_Regional', $sql);
    }

    public function test_scope_unidad_operativa(): void
    {
        $sql = Tienda::query()->unidadOperativa('UO_26')->toSql();
        $this->assertStringContainsString('"Clave_UniOpe"', $sql);
    }

    public function test_scope_unidad_operativa_ignores_empty(): void
    {
        $sql = Tienda::query()->unidadOperativa('')->toSql();
        $this->assertStringNotContainsString('Clave_UniOpe', $sql);
    }

    public function test_scope_almacen(): void
    {
        $sql = Tienda::query()->almacen('OAXACA')->toSql();
        $this->assertStringContainsString('LOWER("Nombre_Almacen") LIKE LOWER(?)', $sql);
    }

    public function test_scope_conectividad_si(): void
    {
        $sql = Tienda::query()->conectividad(['telefono' => 'si'])->toSql();
        $this->assertStringContainsString('"TELEFONIA"', $sql);
        $this->assertStringContainsString('?', $sql);
    }

    public function test_scope_conectividad_compania(): void
    {
        $sql = Tienda::query()->conectividad(['compania' => 'TELCEL'])->toSql();
        $this->assertStringContainsString('Compañía', $sql);
    }

    public function test_scope_conectividad_sin_dato(): void
    {
        $sql = Tienda::query()->conectividad(['compania' => 'SIN DATO'])->toSql();
        $this->assertStringContainsString('UPPER(TRIM("Compañía")) IN', $sql);
    }

    public function test_scope_criticidad_nivel(): void
    {
        $sql = Tienda::query()->criticidad(['nivel' => 'rojo'])->toSql();
        $this->assertStringContainsString('"nivel_critico"', $sql);
    }

    public function test_scope_criticidad_indicador(): void
    {
        $sql = Tienda::query()->criticidad(['indicador' => 'capital_bajo'])->toSql();
        $this->assertStringContainsString('Cap_Tot', $sql);
    }

    public function test_scope_auditoria_nivel(): void
    {
        $sql = Tienda::query()->auditoria(['nivel' => 'rojo'])->toSql();
        $this->assertStringContainsString('"nivel_critico"', $sql);
    }

    public function test_scope_auditoria_vencida(): void
    {
        $sql = Tienda::query()->auditoria(['estado_auditoria' => 'vencida'])->toSql();
        $this->assertStringContainsString('"auditoria_pendiente"', $sql);
    }

    public function test_scope_aperturas_desde(): void
    {
        $sql = Tienda::query()->aperturas(['desde' => '2025-01-01'])->toSql();
        $this->assertStringContainsString('"Fecha_Apertura"', $sql);
        $this->assertStringContainsString('>=', $sql);
    }

    public function test_scope_aperturas_hasta(): void
    {
        $sql = Tienda::query()->aperturas(['hasta' => '2025-06-01'])->toSql();
        $this->assertStringContainsString('"Fecha_Apertura"', $sql);
        $this->assertStringContainsString('<=', $sql);
    }

    public function test_scope_mapa_incidencias(): void
    {
        $sql = Tienda::query()->mapa(['estado_geo' => 'INCIDENCIAS'])->toSql();
        $this->assertStringContainsString('"estado_geo" in (?, ?)', $sql);
    }

    public function test_scope_mapa_normal(): void
    {
        $sql = Tienda::query()->mapa(['estado_geo' => 'VALIDO'])->toSql();
        $this->assertStringContainsString('"estado_geo"', $sql);
    }

    public function test_scope_bounds_valid(): void
    {
        $sql = Tienda::query()->bounds([
            'north' => 25,
            'south' => 15,
            'east' => -85,
            'west' => -100,
        ])->toSql();
        $this->assertStringContainsString('"Latitud"', $sql);
        $this->assertStringContainsString('"Longitud"', $sql);
    }

    public function test_scope_bounds_invalid_ignored(): void
    {
        $sql = Tienda::query()->bounds(['north' => 'not_a_number'])->toSql();
        $this->assertStringNotContainsString('Latitud', $sql);
    }

    public function test_scope_tienda_salud(): void
    {
        $sql = Tienda::query()->tiendaSalud('salud')->toSql();
        $this->assertStringContainsString('"es_tienda_salud"', $sql);
    }

    public function test_scope_tienda_bienestar(): void
    {
        $sql = Tienda::query()->tiendaSalud('bienestar')->toSql();
        $this->assertStringContainsString('"es_tienda_salud"', $sql);
    }

    public function test_scope_tienda_salud_ignores_null(): void
    {
        $sql = Tienda::query()->tiendaSalud(null)->toSql();
        $this->assertStringNotContainsString('es_tienda_salud', $sql);
    }

    public function test_scope_directorio_search(): void
    {
        $sql = Tienda::query()->directorio(['q' => 'OAXACA'], [])->toSql();
        $this->assertStringContainsString('LOWER("Nombre_Almacen") LIKE LOWER(?)', $sql);
    }

    public function test_scope_directorio_sin_capital(): void
    {
        $sql = Tienda::query()->directorio(['sinCapital' => true], [])->toSql();
        $this->assertStringContainsString('Cap_Tot', $sql);
    }
}

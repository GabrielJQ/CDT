<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = DB::connection('pgsql_imports');

        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_region_uo_id ON tiendas ("Clave_Regional", "Clave_UniOpe", id)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_region_criticidad ON tiendas ("Clave_Regional", "Clave_UniOpe", nivel_critico, factores_criticos_count DESC, id)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_region_auditoria ON tiendas ("Clave_Regional", "Clave_UniOpe", auditoria_pendiente, estado_comite, rango_rotacion, id)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_region_apertura ON tiendas ("Clave_Regional", "Clave_UniOpe", "Fecha_Apertura" DESC, id)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_region_geo_bounds ON tiendas ("Clave_Regional", "Clave_UniOpe", "Latitud", "Longitud", id) WHERE "Latitud" IS NOT NULL AND "Longitud" IS NOT NULL AND "Latitud" != \'0\' AND "Longitud" != \'0\'');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_cxc_uo_geo_bounds ON tiendas_casa_x_casa (unidad_operativa, latitud, longitud, id) WHERE latitud IS NOT NULL AND longitud IS NOT NULL AND latitud != 0 AND longitud != 0');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_cxc_directorio_filters ON tiendas_casa_x_casa (estado, unidad_operativa, estatus, id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::connection('pgsql_imports');

        $connection->statement('DROP INDEX IF EXISTS idx_cxc_directorio_filters');
        $connection->statement('DROP INDEX IF EXISTS idx_cxc_uo_geo_bounds');
        $connection->statement('DROP INDEX IF EXISTS idx_tiendas_region_geo_bounds');
        $connection->statement('DROP INDEX IF EXISTS idx_tiendas_region_apertura');
        $connection->statement('DROP INDEX IF EXISTS idx_tiendas_region_auditoria');
        $connection->statement('DROP INDEX IF EXISTS idx_tiendas_region_criticidad');
        $connection->statement('DROP INDEX IF EXISTS idx_tiendas_region_uo_id');
    }
};

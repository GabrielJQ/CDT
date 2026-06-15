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

        $connection->statement('ALTER TABLE tiendas_casa_x_casa ADD COLUMN IF NOT EXISTS periodo_importacion_id bigint');
        $connection->statement('ALTER TABLE tiendas_casa_x_casa ADD COLUMN IF NOT EXISTS es_activo boolean NOT NULL DEFAULT true');
        $connection->statement('ALTER TABLE tiendas_casa_x_casa ADD COLUMN IF NOT EXISTS llave_tienda_periodo varchar(255)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_cxc_periodo_activo ON tiendas_casa_x_casa (periodo_importacion_id, es_activo, id)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_cxc_activo_uo ON tiendas_casa_x_casa (es_activo, unidad_operativa, id)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_cxc_llave_periodo ON tiendas_casa_x_casa (periodo_importacion_id, llave_tienda_periodo)');
        $connection->statement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_cxc_periodo_llave ON tiendas_casa_x_casa (periodo_importacion_id, llave_tienda_periodo)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::connection('pgsql_imports');

        $connection->statement('DROP INDEX IF EXISTS idx_cxc_llave_periodo');
        $connection->statement('DROP INDEX IF EXISTS uniq_cxc_periodo_llave');
        $connection->statement('DROP INDEX IF EXISTS idx_cxc_activo_uo');
        $connection->statement('DROP INDEX IF EXISTS idx_cxc_periodo_activo');
        $connection->statement('ALTER TABLE tiendas_casa_x_casa DROP COLUMN IF EXISTS llave_tienda_periodo');
        $connection->statement('ALTER TABLE tiendas_casa_x_casa DROP COLUMN IF EXISTS es_activo');
        $connection->statement('ALTER TABLE tiendas_casa_x_casa DROP COLUMN IF EXISTS periodo_importacion_id');
    }
};

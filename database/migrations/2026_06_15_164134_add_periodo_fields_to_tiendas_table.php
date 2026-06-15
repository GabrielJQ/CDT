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

        $connection->statement('ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS periodo_importacion_id bigint');
        $connection->statement('ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS es_activo boolean NOT NULL DEFAULT true');
        $connection->statement('ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS llave_tienda_periodo varchar(255)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_periodo_activo ON tiendas (periodo_importacion_id, es_activo, id)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_activo_region ON tiendas (es_activo, "Clave_Regional", "Clave_UniOpe", id)');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_llave_periodo ON tiendas (periodo_importacion_id, llave_tienda_periodo)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::connection('pgsql_imports');

        $connection->statement('DROP INDEX IF EXISTS idx_tiendas_llave_periodo');
        $connection->statement('DROP INDEX IF EXISTS idx_tiendas_activo_region');
        $connection->statement('DROP INDEX IF EXISTS idx_tiendas_periodo_activo');
        $connection->statement('ALTER TABLE tiendas DROP COLUMN IF EXISTS llave_tienda_periodo');
        $connection->statement('ALTER TABLE tiendas DROP COLUMN IF EXISTS es_activo');
        $connection->statement('ALTER TABLE tiendas DROP COLUMN IF EXISTS periodo_importacion_id');
    }
};

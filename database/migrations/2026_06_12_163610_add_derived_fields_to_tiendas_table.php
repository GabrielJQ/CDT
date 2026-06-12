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

        $connection->statement('ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS "nivel_critico" varchar(20)');
        $connection->statement('ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS "factores_criticos_count" integer');
        $connection->statement('ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS "estado_geo" varchar(30)');
        $connection->statement('ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS "estado_comite" varchar(30)');
        $connection->statement('ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS "rango_rotacion" varchar(30)');
        $connection->statement('ALTER TABLE tiendas ADD COLUMN IF NOT EXISTS "auditoria_pendiente" boolean');

        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_derivados_riesgo ON tiendas ("nivel_critico", "estado_geo", "estado_comite", "rango_rotacion")');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::connection('pgsql_imports');

        $connection->statement('DROP INDEX IF EXISTS idx_tiendas_derivados_riesgo');
        $connection->statement('ALTER TABLE tiendas DROP COLUMN IF EXISTS "auditoria_pendiente"');
        $connection->statement('ALTER TABLE tiendas DROP COLUMN IF EXISTS "rango_rotacion"');
        $connection->statement('ALTER TABLE tiendas DROP COLUMN IF EXISTS "estado_comite"');
        $connection->statement('ALTER TABLE tiendas DROP COLUMN IF EXISTS "estado_geo"');
        $connection->statement('ALTER TABLE tiendas DROP COLUMN IF EXISTS "factores_criticos_count"');
        $connection->statement('ALTER TABLE tiendas DROP COLUMN IF EXISTS "nivel_critico"');
    }
};

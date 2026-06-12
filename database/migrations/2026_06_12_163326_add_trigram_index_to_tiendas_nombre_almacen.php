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
        $connection->statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        $connection->statement('CREATE INDEX IF NOT EXISTS idx_tiendas_nombre_almacen_trgm ON tiendas USING gin ("Nombre_Almacen" gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('pgsql_imports')->statement('DROP INDEX IF EXISTS idx_tiendas_nombre_almacen_trgm');
    }
};

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

        foreach ($this->indexes() as $name => $columns) {
            $quotedColumns = implode(', ', array_map(fn (string $column) => '"'.$column.'"', $columns));
            $connection->statement("CREATE INDEX IF NOT EXISTS {$name} ON tiendas ({$quotedColumns})");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::connection('pgsql_imports');

        foreach (array_keys($this->indexes()) as $name) {
            $connection->statement("DROP INDEX IF EXISTS {$name}");
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function indexes(): array
    {
        return [
            'idx_tiendas_region_uo' => ['Clave_Regional', 'Clave_UniOpe'],
            'idx_tiendas_nombre_almacen' => ['Nombre_Almacen'],
            'idx_tiendas_estado_municipio' => ['Estado', 'Municipio'],
            'idx_tiendas_conectividad' => ['TELEFONIA', 'INTERNET', 'Señal de celular'],
            'idx_tiendas_fecha_apertura' => ['Fecha_Apertura'],
        ];
    }
};

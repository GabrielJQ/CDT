<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('pgsql_imports')->table('unidades_operativas', function (Blueprint $table) {
            $table->dropUnique('unidades_operativas_clave_unique');
            $table->unique(['region_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_imports')->table('unidades_operativas', function (Blueprint $table) {
            $table->dropUnique(['region_id', 'clave']);
            $table->string('clave')->unique()->change();
        });
    }
};

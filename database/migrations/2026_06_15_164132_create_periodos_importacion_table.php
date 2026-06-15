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
        Schema::connection('pgsql_imports')->create('periodos_importacion', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 30);
            $table->unsignedSmallInteger('anio');
            $table->string('trimestre', 2);
            $table->string('nombre', 120);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_corte')->nullable();
            $table->string('archivo_original', 255)->nullable();
            $table->string('estado', 30)->default('pendiente');
            $table->boolean('es_activo')->default(false);
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('total_errores')->default(0);
            $table->timestamps();

            $table->unique(['tipo', 'anio', 'trimestre']);
            $table->index(['tipo', 'es_activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_imports')->dropIfExists('periodos_importacion');
    }
};

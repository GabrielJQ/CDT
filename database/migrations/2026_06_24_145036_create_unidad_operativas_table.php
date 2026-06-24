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
        Schema::connection('pgsql_imports')->create('unidades_operativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regiones')->cascadeOnDelete();
            $table->string('clave')->unique();
            $table->string('nombre');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['region_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_operativas');
    }
};

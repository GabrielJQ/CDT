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
        Schema::connection('pgsql_imports')->table('periodos_importacion', function (Blueprint $table) {
            $table->dropUnique('periodos_importacion_tipo_anio_trimestre_unique');
            $table->string('scope_type', 20)->default('global')->after('estado')->index();
            $table->unsignedBigInteger('region_id')->nullable()->after('scope_type')->index();
            $table->unsignedBigInteger('unidad_operativa_id')->nullable()->after('region_id')->index();
            $table->unsignedBigInteger('uploaded_by')->nullable()->after('unidad_operativa_id')->index();

            $table->unique(['tipo', 'anio', 'trimestre', 'scope_type', 'region_id', 'unidad_operativa_id'], 'periodos_importacion_scope_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_imports')->table('periodos_importacion', function (Blueprint $table) {
            $table->dropUnique('periodos_importacion_scope_unique');
            $table->dropColumn(['scope_type', 'region_id', 'unidad_operativa_id', 'uploaded_by']);
            $table->unique(['tipo', 'anio', 'trimestre']);
        });
    }
};

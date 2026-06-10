<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_imports')->table('tiendas', function ($table) {
            $table->string('B_C_R_P', 200)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_imports')->table('tiendas', function ($table) {
            $table->string('B_C_R_P', 20)->nullable()->change();
        });
    }
};

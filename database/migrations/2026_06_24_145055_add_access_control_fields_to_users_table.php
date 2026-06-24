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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('nacional')->after('password')->index();
            $table->foreignId('region_id')->nullable()->after('role')->constrained('regiones')->nullOnDelete();
            $table->foreignId('unidad_operativa_id')->nullable()->after('region_id')->constrained('unidades_operativas')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('unidad_operativa_id')->index();
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unidad_operativa_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });
    }
};

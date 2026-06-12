<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql_imports')->create('tiendas_casa_x_casa', function (Blueprint $table) {
            $table->id();

            $table->integer('edo')->nullable();
            $table->string('estado', 100);
            $table->integer('mpio')->nullable();
            $table->string('municipio', 150);
            $table->integer('loc')->nullable();
            $table->string('localidad', 200);
            $table->string('unidad_operativa', 150);
            $table->string('almacen', 200);
            $table->string('no_tienda', 50);
            $table->text('direccion')->nullable();
            $table->string('encargado', 200)->nullable();
            $table->decimal('latitud', 11, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->string('tipo_anaquel', 10)->nullable();
            $table->string('estatus', 100)->nullable();
            $table->boolean('anaqueles_instalados')->nullable();
            $table->boolean('aviso_funcionamiento')->nullable();
            $table->text('comentarios')->nullable();

            $table->index(['no_tienda', 'almacen', 'estado', 'municipio']);
            $table->index('unidad_operativa');
            $table->index('estatus');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql_imports')->dropIfExists('tiendas_casa_x_casa');
    }
};

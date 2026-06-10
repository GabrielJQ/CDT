<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tiendas', function (Blueprint $table) {
            $table->id();

            // Identidad / Regional
            $table->string('Clave_Regional', 50)->nullable();
            $table->string('Nombre_Regional', 200)->nullable();
            $table->string('Clave_UniOpe', 50)->nullable();
            $table->string('Nombre_UniOpe', 200)->nullable();
            $table->string('ClaveSIAC_Almacen', 50)->nullable();
            $table->string('Nombre_Almacen', 200)->nullable();
            $table->string('ClaveContable_Almacen', 50)->nullable();
            $table->string('Edo', 10)->nullable();
            $table->string('Estado', 100)->nullable();
            $table->string('Mpio', 10)->nullable();
            $table->string('Municipio', 100)->nullable();
            $table->string('Loc', 10)->nullable();
            $table->string('Localidad', 200)->nullable();
            $table->string('B_C_R_P', 20)->nullable();
            $table->string('Canal', 100)->nullable();
            $table->string('No_Tienda_Actual', 50)->nullable();
            $table->string('No_Tienda_Ori', 50)->nullable();

            // Apertura / Autorización
            $table->date('Fecha_Apertura')->nullable();
            $table->date('Fecha_Autoriza')->nullable();
            $table->string('Oficio_Autoriza', 100)->nullable();
            $table->date('Fecha_Autoriza_Consejo')->nullable();
            $table->string('Oficio_Autoriza_Consejo', 100)->nullable();

            // Dirección / Encargado
            $table->text('Direccion')->nullable();
            $table->string('Encargado', 200)->nullable();
            $table->string('Enc_Sexo', 10)->nullable();
            $table->date('Enc_FecNac')->nullable();

            // Financiero / Capital
            $table->decimal('Pagare_Monto', 18, 2)->nullable();
            $table->decimal('Impuestos', 18, 2)->nullable();
            $table->date('Pagare_Fecha')->nullable();
            $table->decimal('Cap_Com', 18, 2)->nullable();
            $table->decimal('Cap_Dic', 18, 2)->nullable();
            $table->decimal('Cap_Tot', 18, 2)->nullable();
            $table->date('Fecha_Pos')->nullable();
            $table->string('PedRIT', 50)->nullable();
            $table->decimal('Rt_Sup', 18, 2)->nullable();
            $table->string('Nom_Sup', 200)->nullable();
            $table->string('Tipo_Loc', 100)->nullable();
            $table->string('Energia', 100)->nullable();
            $table->string('Opc_Unica', 100)->nullable();
            $table->string('Otrs_Tdas', 100)->nullable();
            $table->string('Nva_Imag', 100)->nullable();
            $table->decimal('M2AVenta', 12, 2)->nullable();
            $table->decimal('M2Bodega', 12, 2)->nullable();
            $table->decimal('M2Fachada', 12, 2)->nullable();
            $table->string('Movimiento', 100)->nullable();
            $table->date('Fech_Mov_Reap')->nullable();
            $table->string('Mot_Reaper', 255)->nullable();

            // Auditoría
            $table->date('Fch_Audit')->nullable();
            $table->decimal('Imp_Res_Audi_Mes', 18, 2)->nullable();
            $table->integer('Audit_Realiza_Mes')->nullable();
            $table->integer('Audit_Acum_Tot')->nullable();
            $table->integer('Audit_Progra_Mes')->nullable();
            $table->text('Audit_Obs')->nullable();

            // Asambleas
            $table->date('Asam_Fch_')->nullable();
            $table->integer('Asam_Real_Mes')->nullable();
            $table->integer('Asam_Acum_Tot')->nullable();
            $table->integer('Asam_Prog_Mes')->nullable();
            $table->string('Asam_Tipo', 100)->nullable();
            $table->integer('Asam_AsisT')->nullable();
            $table->text('Asam_Observa')->nullable();

            // Conectividad
            $table->string('TELEFONIA', 50)->nullable();
            $table->string('CORREO', 200)->nullable();
            $table->string('INTERNET', 50)->nullable();
            $table->string('Señal de celular', 50)->nullable();
            $table->string('Compañía', 200)->nullable();

            // Servicios / Infraestructura (valores libres)
            $table->text('ANUNCIOS POR ALTAVOZ')->nullable();
            $table->text('CARNICERIA')->nullable();
            $table->text('VENTA DE FRUTAS, VERDURAS Y PR')->nullable();
            $table->text('RADIO CIVIL')->nullable();
            $table->text('PAGO DE SERVICIOS')->nullable();
            $table->text('ENTREGA DE APOYOS DE PROGRAMAS')->nullable();
            $table->text('ACOPIO Y TRUEQUE DE PRODUCCION')->nullable();
            $table->text('Otro')->nullable();
            $table->text('Acti_realizados')->nullable();
            $table->text('Acti_observa')->nullable();

            // CRA (Comités)
            $table->string('Nom_Pre_CRA', 200)->nullable();
            $table->string('Nom_Pre_Sup_CRA', 200)->nullable();
            $table->string('Nom_Sec_CRA', 200)->nullable();
            $table->string('Nom_Sec_Sup_CRA', 200)->nullable();
            $table->string('Nom_Tes_CRA', 200)->nullable();
            $table->string('Nom_Vcv_CRA', 200)->nullable();
            $table->string('Nom_Voc_Gen_CRA', 200)->nullable();
            $table->date('Fec_CRA')->nullable();
            $table->date('Vigencia')->nullable();
            $table->string('Nom_Rep_Con', 200)->nullable();
            $table->string('Cargo_Rep_Con', 200)->nullable();
            $table->string('Rep_Estatal', 200)->nullable();
            $table->string('Rep_Nacional', 200)->nullable();

            // Ventas
            $table->decimal('Vta_Mes', 18, 2)->nullable();
            $table->decimal('Bon_Mes', 18, 2)->nullable();
            $table->decimal('IVA_Mes', 18, 2)->nullable();
            $table->decimal('VtaNeta_Mes', 18, 2)->nullable();
            $table->decimal('Vta_Acu', 18, 2)->nullable();
            $table->decimal('Bon_Acu', 18, 2)->nullable();
            $table->decimal('IVA_Acu', 18, 2)->nullable();
            $table->decimal('VtaNeta_Acu', 18, 2)->nullable();
            $table->decimal('Vta_Mes_Maiz', 18, 2)->nullable();
            $table->decimal('Bon_Mes_Maiz', 18, 2)->nullable();
            $table->decimal('IVA_Mes_Maiz', 18, 2)->nullable();
            $table->decimal('VtaNeta_Mes_Maiz', 18, 2)->nullable();
            $table->decimal('Vta_Acu_Maiz', 18, 2)->nullable();
            $table->decimal('Bon_Acu_Maiz', 18, 2)->nullable();
            $table->decimal('IVA_Acu_Maiz', 18, 2)->nullable();
            $table->decimal('VtaNeta_Acu_Maiz', 18, 2)->nullable();
            $table->decimal('Vta_Mes_MaizK', 18, 2)->nullable();
            $table->decimal('Vta_Acu_MaizK', 18, 2)->nullable();

            // Flags / Indicadores
            $table->string('U_SERV', 50)->nullable();
            $table->string('OBJ', 50)->nullable();
            $table->string('IND', 50)->nullable();
            $table->string('MIDH', 50)->nullable();
            $table->string('100X100', 50)->nullable();
            $table->string('GDOMARG', 50)->nullable();
            $table->string('POBLACION', 50)->nullable();

            // Infraestructura física
            $table->text('Fachada')->nullable();
            $table->text('Placa identificadora')->nullable();
            $table->text('Placa vivir mejor')->nullable();
            $table->text('Imagen institucional')->nullable();
            $table->text('Buena administración')->nullable();
            $table->text('Programas SEDESOL')->nullable();
            $table->text('Corresponsal bancario')->nullable();
            $table->text('Vende tiempo aire')->nullable();
            $table->text('Mostrador')->nullable();
            $table->text('Estantería')->nullable();
            $table->text('Cuenta con bascula')->nullable();
            $table->text('Cuenta con rebanadora')->nullable();
            $table->text('Cuenta con refrigerador')->nullable();
            $table->text('Piso firme')->nullable();
            $table->text('Pared solida')->nullable();
            $table->text('Techo seguro')->nullable();
            $table->text('Cancelería')->nullable();
            $table->text('Iluminación')->nullable();
            $table->text('Venta huevo')->nullable();
            $table->text('Sistema_int_de_admon_de_Inv')->nullable();

            // Geo
            $table->decimal('Latitud', 10, 7)->nullable();
            $table->decimal('Longitud', 10, 7)->nullable();

            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index('Nombre_Almacen');
            $table->index('Estado');
            $table->index('Municipio');
            $table->index('No_Tienda_Actual');
            $table->index('Nombre_UniOpe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS tiendas CASCADE');
    }
};

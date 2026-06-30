<?php

namespace App\Console\Commands;

use App\Servicios\ServicioPeriodosImportacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InicializarPeriodosImportacion extends Command
{
    protected $signature = 'periodos:inicializar
        {--anio=2026 : Año inicial}
        {--trimestre=T1 : Trimestre inicial}
        {--fecha-corte=2026-04-01 : Fecha de corte inicial}
        {--force : Ejecuta sin pedir confirmación}';

    protected $description = 'Crea periodos iniciales y asigna los datos actuales a 2026 T1';

    public function handle(ServicioPeriodosImportacion $periodos): int
    {
        $anio = (int) $this->option('anio');
        $trimestre = $periodos->normalizarTrimestre($this->option('trimestre'));
        $fechaCorte = (string) $this->option('fecha-corte');

        if (! $this->option('force') && ! $this->confirm("Asignar datos actuales a {$anio} {$trimestre}?")) {
            $this->warn('Operación cancelada.');

            return self::SUCCESS;
        }

        $conn = DB::connection(config('database.imports'));

        $regular = $periodos->buscar(ServicioPeriodosImportacion::TIPO_REGULAR, $anio, $trimestre)
            ?? $periodos->preparar(
                ServicioPeriodosImportacion::TIPO_REGULAR,
                $anio,
                $trimestre,
                $fechaCorte,
                'Datos existentes',
                false,
            );
        $regularCount = $conn->table('tiendas')->whereNull('periodo_importacion_id')->count();
        $conn->table('tiendas')->whereNull('periodo_importacion_id')->update([
            'periodo_importacion_id' => $regular->id,
            'es_activo' => true,
            'llave_tienda_periodo' => DB::raw("CONCAT_WS('|', UPPER(TRIM(COALESCE(\"Clave_Regional\"::text, ''))), UPPER(TRIM(COALESCE(\"Clave_UniOpe\"::text, ''))), UPPER(TRIM(COALESCE(\"ClaveSIAC_Almacen\"::text, ''))), UPPER(TRIM(COALESCE(\"No_Tienda_Actual\"::text, ''))))"),
        ]);
        $regularTotal = $conn->table('tiendas')->where('periodo_importacion_id', $regular->id)->count();
        $periodos->activar(ServicioPeriodosImportacion::TIPO_REGULAR, (int) $regular->id, $regularTotal);

        $cxc = $periodos->buscar(ServicioPeriodosImportacion::TIPO_CASA_X_CASA, $anio, $trimestre)
            ?? $periodos->preparar(
                ServicioPeriodosImportacion::TIPO_CASA_X_CASA,
                $anio,
                $trimestre,
                $fechaCorte,
                'Datos existentes',
                false,
            );
        $cxcCount = $conn->table('tiendas_casa_x_casa')->whereNull('periodo_importacion_id')->count();
        $conn->table('tiendas_casa_x_casa')->whereNull('periodo_importacion_id')->update([
            'periodo_importacion_id' => $cxc->id,
            'es_activo' => true,
            'llave_tienda_periodo' => DB::raw("CONCAT_WS('|', UPPER(TRIM(COALESCE(unidad_operativa::text, ''))), UPPER(TRIM(COALESCE(almacen::text, ''))), UPPER(TRIM(COALESCE(no_tienda::text, ''))), UPPER(TRIM(COALESCE(estado::text, ''))), UPPER(TRIM(COALESCE(municipio::text, ''))))"),
        ]);
        $cxcTotal = $conn->table('tiendas_casa_x_casa')->where('periodo_importacion_id', $cxc->id)->count();
        $periodos->activar(ServicioPeriodosImportacion::TIPO_CASA_X_CASA, (int) $cxc->id, $cxcTotal);

        Cache::flush();

        $this->info("Regular {$anio} {$trimestre}: {$regularCount} filas asignadas.");
        $this->info("CxC {$anio} {$trimestre}: {$cxcCount} filas asignadas.");

        return self::SUCCESS;
    }
}

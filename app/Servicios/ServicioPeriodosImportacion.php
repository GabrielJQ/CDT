<?php

namespace App\Servicios;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class ServicioPeriodosImportacion
{
    public const TIPO_REGULAR = 'regular';

    public const TIPO_CASA_X_CASA = 'casa_x_casa';

    /**
     * @return array<int, string>
     */
    public function trimestres(): array
    {
        return [
            1 => 'T1',
            2 => 'T2',
            3 => 'T3',
            4 => 'T4',
        ];
    }

    public function normalizarTrimestre(int|string $trimestre): string
    {
        $value = strtoupper(trim((string) $trimestre));
        if (preg_match('/^[1-4]$/', $value)) {
            return 'T'.$value;
        }

        if (! in_array($value, $this->trimestres(), true)) {
            throw new \InvalidArgumentException('Trimestre inválido. Usa T1, T2, T3 o T4.');
        }

        return $value;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function rangoFechas(int $anio, string $trimestre): array
    {
        return match ($this->normalizarTrimestre($trimestre)) {
            'T1' => ["{$anio}-01-01", "{$anio}-03-31"],
            'T2' => ["{$anio}-04-01", "{$anio}-06-30"],
            'T3' => ["{$anio}-07-01", "{$anio}-09-30"],
            'T4' => ["{$anio}-10-01", "{$anio}-12-31"],
        };
    }

    public function obtenerActivo(string $tipo): ?object
    {
        return $this->conn()->table('periodos_importacion')
            ->where('tipo', $tipo)
            ->where('es_activo', true)
            ->first();
    }

    /**
     * @return array{regular: object|null, casa_x_casa: object|null}
     */
    public function activos(): array
    {
        return [
            self::TIPO_REGULAR => $this->obtenerActivo(self::TIPO_REGULAR),
            self::TIPO_CASA_X_CASA => $this->obtenerActivo(self::TIPO_CASA_X_CASA),
        ];
    }

    public function existe(string $tipo, int $anio, int|string $trimestre): bool
    {
        return $this->buscar($tipo, $anio, $trimestre) !== null;
    }

    public function buscar(string $tipo, int $anio, int|string $trimestre): ?object
    {
        return $this->conn()->table('periodos_importacion')
            ->where('tipo', $tipo)
            ->where('anio', $anio)
            ->where('trimestre', $this->normalizarTrimestre($trimestre))
            ->first();
    }

    public function preparar(
        string $tipo,
        int $anio,
        int|string $trimestre,
        ?string $fechaCorte,
        ?string $archivoOriginal,
        bool $reemplazar = false,
    ): object {
        $trimestre = $this->normalizarTrimestre($trimestre);
        $existente = $this->buscar($tipo, $anio, $trimestre);

        if ($existente !== null && ! $reemplazar) {
            throw new \RuntimeException($this->mensajeReemplazo($tipo, $anio, $trimestre));
        }

        return $this->conn()->transaction(function () use ($tipo, $anio, $trimestre, $fechaCorte, $archivoOriginal, $existente) {
            if ($existente !== null) {
                $this->borrarDatosPeriodo($tipo, (int) $existente->id);
                $this->conn()->table('periodos_importacion')->where('id', $existente->id)->update([
                    'fecha_corte' => $fechaCorte ?: null,
                    'archivo_original' => $archivoOriginal,
                    'estado' => 'procesando',
                    'es_activo' => false,
                    'total_filas' => 0,
                    'total_errores' => 0,
                    'updated_at' => now(),
                ]);

                return $this->conn()->table('periodos_importacion')->where('id', $existente->id)->first();
            }

            [$inicio, $fin] = $this->rangoFechas($anio, $trimestre);
            $id = $this->conn()->table('periodos_importacion')->insertGetId([
                'tipo' => $tipo,
                'anio' => $anio,
                'trimestre' => $trimestre,
                'nombre' => $this->nombre($tipo, $anio, $trimestre),
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'fecha_corte' => $fechaCorte ?: null,
                'archivo_original' => $archivoOriginal,
                'estado' => 'procesando',
                'es_activo' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->conn()->table('periodos_importacion')->where('id', $id)->first();
        });
    }

    public function activar(string $tipo, int $periodoId, int $totalFilas = 0, int $totalErrores = 0): void
    {
        $this->conn()->transaction(function () use ($tipo, $periodoId, $totalFilas, $totalErrores) {
            $this->conn()->table('periodos_importacion')
                ->where('tipo', $tipo)
                ->update(['es_activo' => false, 'updated_at' => now()]);

            $this->conn()->table('periodos_importacion')
                ->where('id', $periodoId)
                ->update([
                    'estado' => $totalErrores > 0 ? 'con_errores' : 'activo',
                    'es_activo' => true,
                    'total_filas' => max(0, $totalFilas),
                    'total_errores' => max(0, $totalErrores),
                    'updated_at' => now(),
                ]);

            $table = $tipo === self::TIPO_CASA_X_CASA ? 'tiendas_casa_x_casa' : 'tiendas';
            $this->conn()->table($table)->update(['es_activo' => false]);
            $this->conn()->table($table)->where('periodo_importacion_id', $periodoId)->update(['es_activo' => true]);
        });
    }

    public function mensajeReemplazo(string $tipo, int $anio, int|string $trimestre): string
    {
        $label = $tipo === self::TIPO_CASA_X_CASA ? 'Tiendas de Salud CxC' : 'Tiendas Regulares';
        $trimestre = $this->normalizarTrimestre($trimestre);

        return "Ya existen datos para {$label} {$anio} {$trimestre}. Marca la confirmación de reemplazo para eliminar los registros actuales de ese periodo e importar el nuevo archivo. Los demás periodos no se afectarán.";
    }

    public function llaveRegular(array|object $row): string
    {
        $row = (array) $row;

        return $this->llave([
            $row['Clave_Regional'] ?? '',
            $row['Clave_UniOpe'] ?? '',
            $row['ClaveSIAC_Almacen'] ?? '',
            $row['No_Tienda_Actual'] ?? '',
        ]);
    }

    public function llaveCasaPorCasa(array|object $row): string
    {
        $row = (array) $row;

        return $this->llave([
            $row['unidad_operativa'] ?? '',
            $row['almacen'] ?? '',
            $row['no_tienda'] ?? '',
            $row['estado'] ?? '',
            $row['municipio'] ?? '',
        ]);
    }

    public function nombre(string $tipo, int $anio, int|string $trimestre): string
    {
        $label = $tipo === self::TIPO_CASA_X_CASA ? 'Tiendas de Salud CxC' : 'Tiendas Regulares';

        return $label.' '.$anio.' '.$this->normalizarTrimestre($trimestre);
    }

    private function borrarDatosPeriodo(string $tipo, int $periodoId): void
    {
        $table = $tipo === self::TIPO_CASA_X_CASA ? 'tiendas_casa_x_casa' : 'tiendas';
        $this->conn()->table($table)->where('periodo_importacion_id', $periodoId)->delete();
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    private function llave(array $parts): string
    {
        return collect($parts)
            ->map(fn (mixed $part) => mb_strtoupper(trim((string) $part)))
            ->implode('|');
    }

    private function conn(): Connection
    {
        return DB::connection('pgsql_imports');
    }
}

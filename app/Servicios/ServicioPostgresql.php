<?php

namespace App\Servicios;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServicioPostgresql
{
    private ?string $ultimoError = null;

    public function getUltimoError(): ?string
    {
        return $this->ultimoError;
    }

    public function obtenerTiendas(array $filters = []): array
    {
        $this->ultimoError = null;

        try {
            return $this->fetchDesdePostgres($filters);
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] '.$e->getMessage());

            return [];
        }
    }

    public function fetchDesdePostgres(array $filters = []): array
    {
        $conn = $this->conexion();
        $count = $conn->table('tiendas')->count();
        if ($count === 0) {
            throw new \RuntimeException('La tabla tiendas está vacía en PostgreSQL');
        }

        $reverseMap = $this->reverseMap();
        $columns = array_keys($reverseMap);

        $query = $conn->table('tiendas');

        if (! empty($filters['region'])) {
            $query->where('Clave_Regional', $filters['region']);
        }
        if (! empty($filters['uo'])) {
            $query->where('Clave_UniOpe', $filters['uo']);
        }

        $stores = [];
        $query->orderBy('id')->chunk(1000, function ($rows) use (&$stores, $reverseMap, $columns) {
            foreach ($rows as $row) {
                $store = [];
                foreach ($columns as $csvColumn) {
                    $dbColumn = $reverseMap[$csvColumn];
                    $value = $row->{$dbColumn} ?? null;
                    $store[$csvColumn] = $this->valorAString($value);
                }
                $stores[] = $store;
            }
        });

        return $stores;
    }

    public function obtenerJerarquiaRegional(): array
    {
        try {
            $conn = $this->conexion();
            $rows = $conn->select("
                SELECT
                    \"Clave_Regional\", \"Nombre_Regional\",
                    \"Clave_UniOpe\", \"Nombre_UniOpe\",
                    COUNT(*) as total
                FROM tiendas
                WHERE \"Nombre_Regional\" IS NOT NULL AND TRIM(\"Nombre_Regional\") != ''
                GROUP BY \"Clave_Regional\", \"Nombre_Regional\", \"Clave_UniOpe\", \"Nombre_UniOpe\"
                ORDER BY \"Clave_Regional\", \"Clave_UniOpe\"
            ");

            $jerarquia = [];
            foreach ($rows as $row) {
                $claveReg = $row->{'Clave_Regional'};
                if (! isset($jerarquia[$claveReg])) {
                    $jerarquia[$claveReg] = [
                        'clave' => $claveReg,
                        'nombre' => $row->{'Nombre_Regional'},
                        'total' => 0,
                        'uos' => [],
                    ];
                }
                $jerarquia[$claveReg]['total'] += (int) $row->total;
                $jerarquia[$claveReg]['uos'][] = [
                    'clave' => $row->{'Clave_UniOpe'},
                    'nombre' => $row->{'Nombre_UniOpe'},
                    'total' => (int) $row->total,
                ];
            }

            return array_values($jerarquia);
        } catch (\Throwable $e) {
            Log::error('[Postgresql] obtenerJerarquiaRegional: '.$e->getMessage());

            return [];
        }
    }

    public function tieneDatos(): bool
    {
        try {
            return $this->conexion()->table('tiendas')->count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function conexion(): Connection
    {
        return DB::connection('pgsql_imports');
    }

    private function reverseMap(): array
    {
        $map = [];
        foreach (config('importacion.column_mapping', []) as $dbCol => $csvCol) {
            $map[$csvCol] = $dbCol;
        }

        return $map;
    }

    private function valorAString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        if (is_float($value) || is_int($value)) {
            if ($value == (int) $value) {
                return number_format((int) $value, 0, '.', '');
            }

            return rtrim(rtrim(number_format($value, 7, '.', ''), '0'), '.');
        }

        return (string) $value;
    }
}

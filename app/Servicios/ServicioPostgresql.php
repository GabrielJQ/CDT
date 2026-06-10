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

    public function obtenerTiendas(): array
    {
        $this->ultimoError = null;

        $cached = cache()->get('dashboard_data');

        try {
            $stores = $this->fetchDesdePostgres();
            $this->guardarEnCache($stores);

            return $stores;
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] '.$e->getMessage());

            if ($cached) {
                return $cached;
            }

            return [];
        }
    }

    public function fetchDesdePostgres(): array
    {
        $conn = $this->conexion();

        $count = $conn->table('tiendas')->count();
        if ($count === 0) {
            throw new \RuntimeException('La tabla tiendas está vacía en PostgreSQL');
        }

        $rows = $conn->table('tiendas')->get();

        $reverseMap = $this->reverseMap();
        $columns = array_keys($reverseMap);

        $stores = [];
        foreach ($rows as $row) {
            $store = [];
            foreach ($columns as $csvColumn) {
                $dbColumn = $reverseMap[$csvColumn];
                $value = $row->{$dbColumn} ?? null;
                $store[$csvColumn] = $this->valorAString($value);
            }
            $stores[] = $store;
        }

        return $stores;
    }

    public function guardarEnCache(array $stores): void
    {
        cache()->put('dashboard_data', $stores, now()->addHours(1));
        cache()->put('dashboard_updated_at', now()->toDateTimeString(), now()->addHours(1));
    }

    public function refrescar(): array
    {
        $this->ultimoError = null;

        try {
            $stores = $this->fetchDesdePostgres();
            $this->guardarEnCache($stores);

            return $stores;
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] Refrescar: '.$e->getMessage());

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

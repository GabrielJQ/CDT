<?php

namespace App\Servicios;

class ServicioMapeoColumnas
{
    public function __construct(
        private array $mapping = [],
    ) {}

    public static function make(): self
    {
        return new self(config('importacion.column_mapping', []));
    }

    /**
     * Convierte una fila de staging (stdClass / array) en un array
     * asociativo con las columnas de la tabla destino.
     */
    public function mapear(object $stagingRow): array
    {
        $destino = [];

        foreach ($this->mapping as $colDestino => $colOrigen) {
            $valor = $stagingRow->{$colOrigen} ?? null;
            $destino[$colDestino] = ($valor !== '' && $valor !== null) ? $valor : null;
        }

        return $destino;
    }

    /**
     * Valida que todas las columnas origen existan en el header del CSV cargado.
     * Devuelve array con advertencias, vacío si todo está bien.
     */
    public function validarColumnas(array $headerCsv): array
    {
        $advertencias = [];

        foreach ($this->mapping as $colDestino => $colOrigen) {
            if (! in_array($colOrigen, $headerCsv, true)) {
                $advertencias[$colOrigen] = "La columna CSV '{$colOrigen}' (para '{$colDestino}') no existe en el archivo";
            }
        }

        return $advertencias;
    }

    /**
     * Devuelve los nombres de las columnas destino.
     */
    public function columnasDestino(): array
    {
        return array_keys($this->mapping);
    }

    /**
     * Devuelve los nombres de las columnas origen esperadas.
     */
    public function columnasOrigen(): array
    {
        return array_values($this->mapping);
    }
}

<?php

namespace App\Servicios;

use App\Servicios\Modulos\ServicioConsultasTiendas;

class ServicioJerarquiaRegional
{
    public function __construct(
        private ServicioConsultasTiendas $consultas,
    ) {}

    public function obtenerJerarquiaRegional(): array
    {
        $conn = $this->consultas->conexion();
        $rows = $conn->select("
            SELECT
                \"Clave_Regional\", \"Nombre_Regional\",
                \"Clave_UniOpe\", \"Nombre_UniOpe\",
                COUNT(*) as total,
                COUNT(DISTINCT \"Nombre_Almacen\") as almacenes
            FROM tiendas
            WHERE es_activo = true AND \"Nombre_Regional\" IS NOT NULL AND TRIM(\"Nombre_Regional\") != ''
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
                    'almacenes' => 0,
                    'uos' => [],
                ];
            }
            $jerarquia[$claveReg]['total'] += (int) $row->total;
            $jerarquia[$claveReg]['almacenes'] += (int) $row->almacenes;
            $jerarquia[$claveReg]['uos'][] = [
                'clave' => $row->{'Clave_UniOpe'},
                'nombre' => $row->{'Nombre_UniOpe'},
                'total' => (int) $row->total,
                'almacenes' => (int) $row->almacenes,
            ];
        }

        return array_values($jerarquia);
    }
}

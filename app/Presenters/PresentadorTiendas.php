<?php

namespace App\Presenters;

use App\Servicios\ServicioGeo;
use App\Servicios\ServicioIndicadorCriticidad;
use Carbon\Carbon;

class PresentadorTiendas
{
    public function __construct(
        private ServicioIndicadorCriticidad $indicadores,
        private ServicioGeo $geo,
    ) {}

    public function rowToStore(object $row, array $columns): array
    {
        $store = [];
        foreach ($columns as $column) {
            $store[$column] = $this->valorAString($row->{$column} ?? null);
        }
        $store['es_tienda_salud_bienestar'] = ! empty($row->es_tienda_salud_bienestar ?? false);

        return $store;
    }

    public function rowToCriticalStore(object $row, array $columns): array
    {
        $store = $this->rowToStore($row, $columns);
        $conditions = [];
        foreach (array_keys($this->indicadores->indicadorLabels()) as $key) {
            $conditions[$key] = $this->indicadores->rowMatchesIndicador($row, $key);
        }

        $store['_critico'] = [
            'conditions' => $conditions,
            'labels' => collect($this->indicadores->indicadorLabels())->map(fn (string $label) => ['label' => $label, 'detail' => ''])->all(),
            'count' => count(array_filter($conditions)),
            'level' => $this->indicadores->levelFromCriticalCount(count(array_filter($conditions))),
        ];

        return $store;
    }

    public function rowToAuditStore(object $row, array $columns): array
    {
        $store = $this->rowToStore($row, $columns);
        $fchAudit = $row->Fch_Audit ?? null;
        $mesesSinAuditoria = $fchAudit ? Carbon::parse($fchAudit)->diffInMonths(now()) : null;
        $impuesto = (float) ($row->Imp_Res_Audi_Mes ?? 0);
        $capDic = (float) ($row->Cap_Dic ?? 0);
        $vtaMes = (float) ($row->Vta_Mes ?? 0);
        $rotacion = $capDic > 0 ? $vtaMes / $capDic : 0;
        $estadoComite = $this->indicadores->estadoComiteFromDate($row->Vigencia ?? null);
        $rangoRotacion = $this->indicadores->rangoRotacionFromValues($capDic, $vtaMes);
        $auditoriaPendiente = $fchAudit === null || Carbon::parse($fchAudit)->lte(now()->subMonths(3));
        $conditions = [];
        if ($estadoComite === 'vencido') {
            $conditions[] = 'comite_vencido';
        }
        if ($impuesto > ServicioIndicadorCriticidad::AUDITORIA_ELEVADA_MIN) {
            $conditions[] = 'auditoria_alta';
        }
        if (in_array($rangoRotacion, ['cero', 'critico'], true)) {
            $conditions[] = 'rotacion_baja';
        }
        if ($auditoriaPendiente) {
            $conditions[] = 'auditoria_pendiente';
        }

        $store['_audit'] = [
            'level' => $this->indicadores->levelFromAuditCount(count($conditions)),
            'conditions' => $conditions,
            'estadoComite' => $estadoComite,
            'vigencia' => $this->valorAString($row->Vigencia ?? null),
            'impuesto' => $impuesto,
            'rotacion' => $rotacion,
            'fchAudit' => $this->valorAString($fchAudit),
            'mesesSinAuditoria' => $mesesSinAuditoria,
            'rangoRotacion' => $rangoRotacion,
            'auditRealizada' => (int) ($row->Audit_Realiza_Mes ?? 0),
            'sinAuditoriaAnio' => $fchAudit === null || Carbon::parse($fchAudit)->lte(now()->subYear()),
            'auditoriaPendiente' => $auditoriaPendiente,
        ];

        return $store;
    }

    public function rowToAperturaStore(object $row, array $columns): array
    {
        $store = $this->rowToStore($row, $columns);
        $fecha = $row->Fecha_Apertura ?? null;
        $store['_fecha_apertura'] = $fecha ? Carbon::parse($fecha)->toDateString() : null;
        $store['_antiguedad'] = $fecha ? ((int) Carbon::parse($fecha)->diffInMonths(now())).' meses' : '—';

        return $store;
    }

    public function rowToGeoStore(object $row, array $columns): array
    {
        $store = $this->rowToStore($row, $columns);

        $dbStatus = strtoupper(trim((string) ($row->estado_geo ?? '')));
        $validStatuses = ['OK', 'SIN_COORDENADAS', 'FUERA_MEXICO', 'FUERA_ESTADO'];

        if ($dbStatus !== '' && in_array($dbStatus, $validStatuses, true)) {
            $lat = $this->parseRawLatLon($store['Latitud'] ?? '');
            $lon = $this->parseRawLatLon($store['Longitud'] ?? '');

            $messages = [
                'OK' => 'Coordenadas válidas.',
                'SIN_COORDENADAS' => 'La tienda no tiene coordenadas registradas.',
                'FUERA_MEXICO' => 'Coordenadas ('.($store['Latitud'] ?? '').' / '.($store['Longitud'] ?? '').') están fuera del territorio mexicano.',
                'FUERA_ESTADO' => 'Coordenadas ('.($store['Latitud'] ?? '').' / '.($store['Longitud'] ?? '').') no corresponden al estado registrado.',
            ];

            $store['_geo'] = [
                'status' => $dbStatus,
                'lat' => $lat,
                'lon' => $lon,
                'mensaje' => $messages[$dbStatus],
            ];
        } else {
            $store['_geo'] = $this->geo->evaluarGeo($store);
        }

        $store['_cxc'] = [
            'esTiendaBienestar' => (bool) ($row->es_tienda_salud_bienestar ?? false),
            'esTiendaSaludBienestar' => (bool) ($row->es_tienda_salud_bienestar ?? false),
        ];

        return $store;
    }

    public function filtrarGeoCalculado(array $rows, string $estadoGeo): array
    {
        if ($estadoGeo === '') {
            return $rows;
        }

        if ($estadoGeo === 'INCIDENCIAS') {
            return array_values(array_filter($rows, fn (array $row) => in_array($row['_geo']['status'] ?? '', ['SIN_COORDENADAS', 'FUERA_MEXICO'], true)));
        }

        return array_values(array_filter($rows, fn (array $row) => ($row['_geo']['status'] ?? '') === $estadoGeo));
    }

    public function valorAString(mixed $value): string
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

    public function parseRawLatLon(string $value): ?float
    {
        $value = trim($value);

        if ($value === '' || $value === '0') {
            return null;
        }

        return (float) $value;
    }
}

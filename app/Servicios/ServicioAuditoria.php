<?php

namespace App\Servicios;

class ServicioAuditoria
{
    public function __construct(
        private ServicioFecha $fecha,
    ) {}

    public function evaluarTienda(array $store): array
    {
        $vigencia = $this->fecha->parsear($store['Vigencia'] ?? '');
        $impuesto = $this->limpiarMonto($store['Imp_Res_Audi_Mes'] ?? '0');
        $capTot = $this->limpiarMonto($store['Cap_Tot'] ?? '0');
        $vtaMes = $this->limpiarMonto($store['Vta_Mes'] ?? '0');
        $rotacion = $capTot > 0 ? $vtaMes / $capTot : 0;
        $fchAuditRaw = trim($store['Fch_Audit'] ?? '');
        $fchAudit = $this->fecha->parsear($fchAuditRaw);
        $mesesSinAuditoria = $fchAudit ? $fchAudit->diffInMonths(now()) : null;

        $estadoComite = 'sin_fecha';
        if ($vigencia !== null) {
            if ($vigencia->isPast()) {
                $estadoComite = 'vencido';
            } elseif ($vigencia->diffInDays(now()) <= 30) {
                $estadoComite = 'proximo_a_vencer';
            } else {
                $estadoComite = 'vigente';
            }
        }

        $conditions = [];
        if ($vigencia !== null && $vigencia->isPast()) $conditions[] = 'comite_vencido';
        if ($impuesto > 500000) $conditions[] = 'auditoria_alta';
        if ($rotacion < 1.5) $conditions[] = 'rotacion_baja';
        $auditoriaPendiente = ($fchAudit === null) || ($mesesSinAuditoria !== null && $mesesSinAuditoria >= 3);
        if ($auditoriaPendiente) $conditions[] = 'auditoria_pendiente';

        $count = count($conditions);
        if ($count >= 2) $level = 'rojo';
        elseif ($count >= 1) $level = 'amarillo';
        else $level = 'verde';

        return compact('level', 'conditions', 'estadoComite', 'vigencia', 'impuesto', 'rotacion', 'fchAudit', 'mesesSinAuditoria');
    }

    public function calcularKpis(array $stores): array
    {
        $comitesVencidos = 0;
        $auditoriaAlta = 0;
        $rotacionBaja = 0;
        $auditoriaPendiente = 0;

        foreach ($stores as $store) {
            $a = $store['_audit'] ?? [];
            $conds = $a['conditions'] ?? [];
            if (in_array('comite_vencido', $conds)) $comitesVencidos++;
            if (in_array('auditoria_alta', $conds)) $auditoriaAlta++;
            if (in_array('rotacion_baja', $conds)) $rotacionBaja++;
            if (in_array('auditoria_pendiente', $conds)) $auditoriaPendiente++;
        }

        return compact('comitesVencidos', 'auditoriaAlta', 'rotacionBaja', 'auditoriaPendiente');
    }

    public function resumenSimple(array $stores): array
    {
        $comitesVencidos = 0;
        $auditoriaAlta = 0;
        $rotacionBaja = 0;
        $auditoriaPendiente = 0;

        foreach ($stores as $store) {
            $vigencia = $this->fecha->parsear($store['Vigencia'] ?? '');
            if ($vigencia !== null && $vigencia->isPast()) $comitesVencidos++;

            $impuesto = $this->limpiarMonto($store['Imp_Res_Audi_Mes'] ?? '0');
            if ($impuesto > 500000) $auditoriaAlta++;

            $capTot = $this->limpiarMonto($store['Cap_Tot'] ?? '0');
            $vtaMes = $this->limpiarMonto($store['Vta_Mes'] ?? '0');
            $rotacion = $capTot > 0 ? $vtaMes / $capTot : 0;
            if ($rotacion < 1.5) $rotacionBaja++;

            $fchAudit = $this->fecha->parsear($store['Fch_Audit'] ?? '');
            $mesesSinAuditoria = $fchAudit ? $fchAudit->diffInMonths(now()) : null;
            if ($fchAudit === null || ($mesesSinAuditoria !== null && $mesesSinAuditoria >= 3)) $auditoriaPendiente++;
        }

        return compact('comitesVencidos', 'auditoriaAlta', 'rotacionBaja', 'auditoriaPendiente');
    }

    private function limpiarMonto(string $value): float
    {
        return (float) str_replace([',', '$', ' '], '', $value);
    }
}

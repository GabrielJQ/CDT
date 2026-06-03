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
        $mesesSinAuditoria = $fchAudit ? abs($fchAudit->diffInMonths(now())) : null;

        $estadoComite = 'sin_fecha';
        if ($vigencia !== null) {
            if ($vigencia->isPast()) {
                $estadoComite = 'vencido';
            } elseif (abs($vigencia->diffInDays(now())) <= 30) {
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

        // Determinar rango de rotacion
        $rangoRotacion = '';
        if ($rotacion == 0) $rangoRotacion = 'cero';
        elseif ($rotacion >= 0.01 && $rotacion < 1) $rangoRotacion = 'bajo_1';
        elseif ($rotacion >= 1 && $rotacion < 1.5) $rangoRotacion = 'bajo_1_5';
        elseif ($rotacion >= 1.5 && $rotacion < 2) $rangoRotacion = 'mayor_1_5';
        elseif ($rotacion >= 2) $rangoRotacion = 'mayor_2';

        $auditRealizada = (int) ($store['Audit_Realiza_Mes'] ?? 0);
        $sinAuditoriaAnio = ($fchAudit === null) || ($mesesSinAuditoria !== null && $mesesSinAuditoria >= 12);

        return compact('level', 'conditions', 'estadoComite', 'vigencia', 'impuesto', 'rotacion', 'fchAudit', 'mesesSinAuditoria', 'rangoRotacion', 'auditRealizada', 'sinAuditoriaAnio', 'auditoriaPendiente');
    }

    public function calcularKpis(array $stores): array
    {
        $comitesVencidos = 0;
        $auditoriaAlta = 0;
        $rotacionBaja = 0;
        $auditoriaPendiente = 0;

        $rotacionCero = 0;
        $rotacionMenor1 = 0;
        $rotacionMenor15 = 0;
        $rotacionMayor15 = 0;
        $rotacionMayor2 = 0;

        $auditoriasMes = 0;
        $sinAuditoriaTrimestre = 0;
        $sinAuditoriaAnio = 0;

        foreach ($stores as $store) {
            $a = $store['_audit'] ?? [];
            $conds = $a['conditions'] ?? [];
            if (in_array('comite_vencido', $conds)) $comitesVencidos++;
            if (in_array('auditoria_alta', $conds)) $auditoriaAlta++;
            if (in_array('rotacion_baja', $conds)) $rotacionBaja++;
            if (in_array('auditoria_pendiente', $conds)) {
                $auditoriaPendiente++;
                $sinAuditoriaTrimestre++;
            }

            if (($a['rangoRotacion'] ?? '') === 'cero') $rotacionCero++;
            elseif (($a['rangoRotacion'] ?? '') === 'bajo_1') $rotacionMenor1++;
            elseif (($a['rangoRotacion'] ?? '') === 'bajo_1_5') $rotacionMenor15++;
            elseif (($a['rangoRotacion'] ?? '') === 'mayor_1_5') $rotacionMayor15++;
            elseif (($a['rangoRotacion'] ?? '') === 'mayor_2') $rotacionMayor2++;

            if (($a['auditRealizada'] ?? 0) > 0) $auditoriasMes++;
            if ($a['sinAuditoriaAnio'] ?? false) $sinAuditoriaAnio++;
        }

        return compact(
            'comitesVencidos', 'auditoriaAlta', 'rotacionBaja', 'auditoriaPendiente',
            'rotacionCero', 'rotacionMenor1', 'rotacionMenor15', 'rotacionMayor15', 'rotacionMayor2',
            'auditoriasMes', 'sinAuditoriaTrimestre', 'sinAuditoriaAnio'
        );
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
            $mesesSinAuditoria = $fchAudit ? abs($fchAudit->diffInMonths(now())) : null;
            if ($fchAudit === null || ($mesesSinAuditoria !== null && $mesesSinAuditoria >= 3)) $auditoriaPendiente++;
        }

        return compact('comitesVencidos', 'auditoriaAlta', 'rotacionBaja', 'auditoriaPendiente');
    }

    private function limpiarMonto(string $value): float
    {
        return (float) str_replace([',', '$', ' '], '', $value);
    }
}

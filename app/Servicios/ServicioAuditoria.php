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
        $capDic = $this->limpiarMonto($store['Cap_Dic'] ?? '0');
        $vtaMes = $this->limpiarMonto($store['Vta_Mes'] ?? '0');
        $rotacion = $capDic > 0 ? $vtaMes / $capDic : 0;
        $fchAuditRaw = trim($store['Fch_Audit'] ?? '');
        $fchAudit = $this->fecha->parsear($fchAuditRaw);
        $mesesSinAuditoria = $fchAudit ? abs($fchAudit->diffInMonths(now())) : null;

        $estadoComite = 'sin_fecha';
        if ($vigencia !== null) {
            if ($vigencia->isPast()) {
                $estadoComite = 'vencido';
            } elseif ($vigencia->lte(now()->addDays(30))) {
                $estadoComite = 'proximo_a_vencer';
            } else {
                $estadoComite = 'vigente';
            }
        }

        $conditions = [];
        if ($vigencia !== null && $vigencia->isPast()) {
            $conditions[] = 'comite_vencido';
        }
        if ($impuesto > 500000) {
            $conditions[] = 'auditoria_alta';
        }
        if ($rotacion < 0.5) {
            $conditions[] = 'rotacion_baja';
        }
        $auditoriaPendiente = ($fchAudit === null) || ($mesesSinAuditoria !== null && $mesesSinAuditoria >= 3);
        if ($auditoriaPendiente) {
            $conditions[] = 'auditoria_pendiente';
        }

        $count = count($conditions);
        if ($count >= 2) {
            $level = 'rojo';
        } elseif ($count >= 1) {
            $level = 'amarillo';
        } else {
            $level = 'verde';
        }

        // Determinar rango de rotacion
        $rangoRotacion = '';
        if ($capDic <= 0) {
            $rangoRotacion = 'cero';
        } elseif ($vtaMes / $capDic < 0.5) {
            $rangoRotacion = 'critico';
        } elseif ($vtaMes / $capDic < 1) {
            $rangoRotacion = 'amarillo';
        } else {
            $rangoRotacion = 'optimo';
        }

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
        $rotacionCritico = 0;
        $rotacionAmarillo = 0;
        $rotacionOptimo = 0;

        $auditoriasMes = 0;
        $sinAuditoriaTrimestre = 0;
        $sinAuditoriaAnio = 0;

        foreach ($stores as $store) {
            $a = $store['_audit'] ?? [];
            $conds = $a['conditions'] ?? [];
            if (in_array('comite_vencido', $conds)) {
                $comitesVencidos++;
            }
            if (in_array('auditoria_alta', $conds)) {
                $auditoriaAlta++;
            }
            if (in_array('rotacion_baja', $conds)) {
                $rotacionBaja++;
            }
            if (in_array('auditoria_pendiente', $conds)) {
                $auditoriaPendiente++;
                $sinAuditoriaTrimestre++;
            }

            if (($a['rangoRotacion'] ?? '') === 'cero') {
                $rotacionCero++;
            } elseif (($a['rangoRotacion'] ?? '') === 'critico') {
                $rotacionCritico++;
            } elseif (($a['rangoRotacion'] ?? '') === 'amarillo') {
                $rotacionAmarillo++;
            } elseif (($a['rangoRotacion'] ?? '') === 'optimo') {
                $rotacionOptimo++;
            }

            if (($a['auditRealizada'] ?? 0) > 0) {
                $auditoriasMes++;
            }
            if ($a['sinAuditoriaAnio'] ?? false) {
                $sinAuditoriaAnio++;
            }
        }

        return compact(
            'comitesVencidos', 'auditoriaAlta', 'rotacionBaja', 'auditoriaPendiente',
            'rotacionCero', 'rotacionCritico', 'rotacionAmarillo', 'rotacionOptimo',
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
            if ($vigencia !== null && $vigencia->isPast()) {
                $comitesVencidos++;
            }

            $impuesto = $this->limpiarMonto($store['Imp_Res_Audi_Mes'] ?? '0');
            if ($impuesto > 500000) {
                $auditoriaAlta++;
            }

            $capDic = $this->limpiarMonto($store['Cap_Dic'] ?? '0');
            $vtaMes = $this->limpiarMonto($store['Vta_Mes'] ?? '0');
            $rotacion = $capDic > 0 ? $vtaMes / $capDic : 0;
            if ($rotacion < 0.5) {
                $rotacionBaja++;
            }

            $fchAudit = $this->fecha->parsear($store['Fch_Audit'] ?? '');
            $mesesSinAuditoria = $fchAudit ? abs($fchAudit->diffInMonths(now())) : null;
            if ($fchAudit === null || ($mesesSinAuditoria !== null && $mesesSinAuditoria >= 3)) {
                $auditoriaPendiente++;
            }
        }

        return compact('comitesVencidos', 'auditoriaAlta', 'rotacionBaja', 'auditoriaPendiente');
    }

    private function limpiarMonto(string $value): float
    {
        return (float) str_replace([',', '$', ' '], '', $value);
    }
}

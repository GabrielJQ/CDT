<?php

namespace App\Servicios;

class ServicioTiendaCritica
{
    public function __construct(
        private ServicioFecha $fecha,
    ) {}

    public function evaluarTienda(array $store): array
    {
        $conditions = [];
        $labels = [];

        $capTot = $this->limpiarMonto($store['Cap_Tot'] ?? '0');
        $conditions['capital_bajo'] = $capTot > 0 && $capTot <= 20000;
        $labels['capital_bajo'] = [
            'label' => 'Capital total bajo',
            'detail' => '$'.number_format($capTot, 2),
            'icon' => '💰',
        ];

        $capDic = $this->limpiarMonto($store['Cap_Dic'] ?? '0');
        $conditions['capital_dictaminado_bajo'] = $capDic > 0 && $capDic <= 20000;
        $labels['capital_dictaminado_bajo'] = [
            'label' => 'Capital Bienestar bajo',
            'detail' => '$'.number_format($capDic, 2),
            'icon' => '🏛️',
        ];

        $vigencia = $store['Vigencia'] ?? '';
        $vigenciaDate = $this->fecha->parsear($vigencia);
        $conditions['comite_vencido'] = $vigenciaDate !== null && $vigenciaDate->isPast();
        $labels['comite_vencido'] = [
            'label' => 'Comité vencido',
            'detail' => $vigenciaDate ? $vigenciaDate->format('d/m/Y') : 'Sin fecha',
            'icon' => '📅',
        ];

        $impuesto = $this->limpiarMonto($store['Imp_Res_Audi_Mes'] ?? '0');
        $conditions['auditoria_elevada'] = $impuesto > 500000;
        $labels['auditoria_elevada'] = [
            'label' => 'Auditoría > $500k',
            'detail' => '$'.number_format($impuesto, 2),
            'icon' => '🔍',
        ];

        $pagareFecha = $store['Pagare_Fecha'] ?? '';
        $pagareDate = $this->fecha->parsear($pagareFecha);
        $conditions['pagare_vencido'] = false;
        if ($pagareDate !== null) {
            $conditions['pagare_vencido'] = $pagareDate->copy()->addYear()->isPast();
        }
        $labels['pagare_vencido'] = [
            'label' => 'Pagare vencido',
            'detail' => $pagareDate ? $pagareDate->format('d/m/Y') : 'Sin fecha',
            'icon' => '📄',
        ];

        $vtaMes = $this->limpiarMonto($store['Vta_Mes'] ?? '0');
        $rotacion = $capDic > 0 ? $vtaMes / $capDic : 0;
        $conditions['rotacion_baja'] = $rotacion < 0.5;
        $labels['rotacion_baja'] = [
            'label' => 'Rotación baja',
            'detail' => number_format($rotacion, 2),
            'icon' => '📉',
        ];

        $asamProg = (int) ($store['Asam_Prog_Mes'] ?? 0);
        $asamReal = (int) ($store['Asam_Real_Mes'] ?? 0);
        $conditions['asamblea_pendiente'] = $asamProg > 0 && $asamReal === 0;
        $labels['asamblea_pendiente'] = [
            'label' => 'Asamblea pendiente',
            'detail' => 'Programadas: '.$asamProg.', Realizadas: '.$asamReal,
            'icon' => '🗳️',
        ];

        $activeCount = count(array_filter($conditions));

        if ($activeCount >= 4) {
            $level = 'rojo';
        } elseif ($activeCount >= 2) {
            $level = 'amarillo';
        } else {
            $level = 'verde';
        }

        return [
            'conditions' => $conditions,
            'labels' => $labels,
            'count' => $activeCount,
            'level' => $level,
        ];
    }

    public function calcularResumen(array $stores): array
    {
        $rojo = 0;
        $amarillo = 0;
        $verde = 0;
        $desglose = [];

        foreach ($stores as $store) {
            $level = $store['_critico']['level'];
            $$level++;

            foreach ($store['_critico']['conditions'] as $key => $active) {
                if ($active) {
                    $desglose[$key] = ($desglose[$key] ?? 0) + 1;
                }
            }
        }

        arsort($desglose);

        $condLabels = [
            'capital_bajo' => '💰 Capital total bajo',
            'capital_dictaminado_bajo' => '🏛️ Capital Bienestar bajo',
            'comite_vencido' => '📅 Comité vencido',
            'auditoria_elevada' => '🔍 Auditoría > $500k',
            'pagare_vencido' => '📄 Pagaré vencido',
            'rotacion_baja' => '📉 Rotación baja',
            'asamblea_pendiente' => '🗳️ Asamblea pendiente',
        ];

        $desgloseLabels = [];
        foreach ($desglose as $key => $count) {
            $desgloseLabels[] = [
                'key' => $key,
                'label' => $condLabels[$key] ?? $key,
                'count' => $count,
            ];
        }

        return compact('rojo', 'amarillo', 'verde', 'desgloseLabels');
    }

    public function resumenSimple(array $stores): array
    {
        $rojo = 0;
        $amarillo = 0;
        $verde = 0;

        foreach ($stores as $store) {
            $count = 0;

            $capTot = $this->limpiarMonto($store['Cap_Tot'] ?? '0');
            if ($capTot > 0 && $capTot <= 20000) {
                $count++;
            }

            $capDic = $this->limpiarMonto($store['Cap_Dic'] ?? '0');
            if ($capDic > 0 && $capDic <= 20000) {
                $count++;
            }

            $vigencia = $this->fecha->parsear($store['Vigencia'] ?? '');
            if ($vigencia !== null && $vigencia->isPast()) {
                $count++;
            }

            $impuesto = $this->limpiarMonto($store['Imp_Res_Audi_Mes'] ?? '0');
            if ($impuesto > 500000) {
                $count++;
            }

            $pagareDate = $this->fecha->parsear($store['Pagare_Fecha'] ?? '');
            if ($pagareDate !== null && $pagareDate->copy()->addYear()->isPast()) {
                $count++;
            }

            $asamProg = (int) ($store['Asam_Prog_Mes'] ?? 0);
            $asamReal = (int) ($store['Asam_Real_Mes'] ?? 0);
            if ($asamProg > 0 && $asamReal === 0) {
                $count++;
            }

            if ($count >= 4) {
                $rojo++;
            } elseif ($count >= 2) {
                $amarillo++;
            } else {
                $verde++;
            }
        }

        return compact('rojo', 'amarillo', 'verde');
    }

    public function limpiarMonto(string $value): float
    {
        return (float) str_replace([',', '$', ' '], '', $value);
    }
}

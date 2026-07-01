<?php

namespace App\Servicios;

use App\Presenters\IndicadorPresenter;
use Carbon\Carbon;

class ServicioIndicadorCriticidad
{
    const CAPITAL_BAJO_MAX = 20000;

    const CAPITAL_DICTAMINADO_BAJO_MAX = 20000;

    const AUDITORIA_ELEVADA_MIN = 500000;

    const ROTACION_CRITICO_MAX = 0.5;

    const ROTACION_AMARILLO_MAX = 1.0;

    const FACTORES_ROJO_MIN = 4;

    const FACTORES_AMARILLO_MIN = 2;

    const COMITE_VENCIDO_DAYS = 30;

    const AUDITORIA_PENDIENTE_MESES = 3;

    const PAGARE_VENCIDO_MESES = 12;

    public function indicadorCriticoSql(string $indicador, bool $usarDerivados = false): ?string
    {
        if ($usarDerivados) {
            return match ($indicador) {
                'capital_bajo' => 'COALESCE("Cap_Tot", 0) > 0 AND COALESCE("Cap_Tot", 0) <= '.self::CAPITAL_BAJO_MAX,
                'capital_dictaminado_bajo' => 'COALESCE("Cap_Dic", 0) > 0 AND COALESCE("Cap_Dic", 0) <= '.self::CAPITAL_DICTAMINADO_BAJO_MAX,
                'comite_vencido' => "estado_comite = 'vencido'",
                'auditoria_elevada' => 'COALESCE("Imp_Res_Audi_Mes", 0) > '.self::AUDITORIA_ELEVADA_MIN,
                'pagare_vencido' => '"Pagare_Fecha" IS NOT NULL AND "Pagare_Fecha" <= CURRENT_DATE - INTERVAL \''.self::PAGARE_VENCIDO_MESES.' months\'',
                'rotacion_baja' => "rango_rotacion IN ('cero', 'critico')",
                'asamblea_pendiente' => 'COALESCE("Asam_Prog_Mes", 0) > 0 AND COALESCE("Asam_Real_Mes", 0) = 0',
                default => null,
            };
        }

        return match ($indicador) {
            'capital_bajo' => 'COALESCE("Cap_Tot", 0) > 0 AND COALESCE("Cap_Tot", 0) <= '.self::CAPITAL_BAJO_MAX,
            'capital_dictaminado_bajo' => 'COALESCE("Cap_Dic", 0) > 0 AND COALESCE("Cap_Dic", 0) <= '.self::CAPITAL_DICTAMINADO_BAJO_MAX,
            'comite_vencido' => $this->estadoComiteSql().' = \'vencido\'',
            'auditoria_elevada' => 'COALESCE("Imp_Res_Audi_Mes", 0) > '.self::AUDITORIA_ELEVADA_MIN,
            'pagare_vencido' => '"Pagare_Fecha" IS NOT NULL AND "Pagare_Fecha" <= CURRENT_DATE - INTERVAL \''.self::PAGARE_VENCIDO_MESES.' months\'',
            'rotacion_baja' => $this->rangoRotacionSql().' IN (\'cero\', \'critico\')',
            'asamblea_pendiente' => 'COALESCE("Asam_Prog_Mes", 0) > 0 AND COALESCE("Asam_Real_Mes", 0) = 0',
            default => null,
        };
    }

    public function rowMatchesIndicador(object $row, string $key): bool
    {
        $capTot = (float) ($row->Cap_Tot ?? 0);
        $capDic = (float) ($row->Cap_Dic ?? 0);
        $vtaMes = (float) ($row->Vta_Mes ?? 0);

        return match ($key) {
            'capital_bajo' => $capTot > 0 && $capTot <= self::CAPITAL_BAJO_MAX,
            'capital_dictaminado_bajo' => $capDic > 0 && $capDic <= self::CAPITAL_DICTAMINADO_BAJO_MAX,
            'comite_vencido' => ! empty($row->Vigencia) && Carbon::parse($row->Vigencia)->isPast(),
            'auditoria_elevada' => (float) ($row->Imp_Res_Audi_Mes ?? 0) > self::AUDITORIA_ELEVADA_MIN,
            'pagare_vencido' => ! empty($row->Pagare_Fecha) && Carbon::parse($row->Pagare_Fecha)->addYear()->isPast(),
            'rotacion_baja' => $capDic <= 0 || ($vtaMes / $capDic) < self::ROTACION_CRITICO_MAX,
            'asamblea_pendiente' => (int) ($row->Asam_Prog_Mes ?? 0) > 0 && (int) ($row->Asam_Real_Mes ?? 0) === 0,
            default => false,
        };
    }

    public function estadoComiteSql(): string
    {
        return "CASE
            WHEN \"Vigencia\" IS NULL THEN 'sin_fecha'
            WHEN \"Vigencia\" <= CURRENT_DATE THEN 'vencido'
            WHEN \"Vigencia\" <= CURRENT_DATE + INTERVAL '".self::COMITE_VENCIDO_DAYS." days' THEN 'proximo_a_vencer'
            ELSE 'vigente'
        END";
    }

    public function estadoComiteFromDate(mixed $vigencia): string
    {
        if (empty($vigencia)) {
            return 'sin_fecha';
        }

        $date = Carbon::parse($vigencia);
        if ($date->isPast()) {
            return 'vencido';
        }

        if ($date->lte(now()->addDays(self::COMITE_VENCIDO_DAYS))) {
            return 'proximo_a_vencer';
        }

        return 'vigente';
    }

    public function rangoRotacionSql(): string
    {
        return "CASE
            WHEN COALESCE(\"Cap_Dic\", 0) <= 0 OR COALESCE(\"Vta_Mes\", 0) = 0 THEN 'cero'
            WHEN COALESCE(\"Vta_Mes\", 0) / NULLIF(\"Cap_Dic\", 0) < ".self::ROTACION_CRITICO_MAX." THEN 'critico'
            WHEN COALESCE(\"Vta_Mes\", 0) / NULLIF(\"Cap_Dic\", 0) < ".self::ROTACION_AMARILLO_MAX." THEN 'amarillo'
            ELSE 'optimo'
        END";
    }

    public function rangoRotacionFromValues(float $capDic, float $vtaMes): string
    {
        if ($capDic <= 0) {
            return 'cero';
        }

        $rotacion = $vtaMes / $capDic;
        if ($rotacion < self::ROTACION_CRITICO_MAX) {
            return 'critico';
        }

        if ($rotacion < self::ROTACION_AMARILLO_MAX) {
            return 'amarillo';
        }

        return 'optimo';
    }

    public function auditoriaPendienteSql(): string
    {
        return '"Fch_Audit" IS NULL OR "Fch_Audit" <= CURRENT_DATE - INTERVAL \''.self::AUDITORIA_PENDIENTE_MESES.' months\'';
    }

    public function sinAuditoriaAnioSql(): string
    {
        return '"Fch_Audit" IS NULL OR "Fch_Audit" <= CURRENT_DATE - INTERVAL \'1 year\'';
    }

    public function nivelCriticoSql(): string
    {
        $countSql = $this->factoresCriticosCountSql();

        return "CASE
            WHEN {$countSql} >= ".self::FACTORES_ROJO_MIN." THEN 'rojo'
            WHEN {$countSql} >= ".self::FACTORES_AMARILLO_MIN." THEN 'amarillo'
            ELSE 'verde'
        END";
    }

    public function nivelAuditoriaSql(): string
    {
        $countSql = $this->factoresAuditoriaCountSql();

        return "CASE
            WHEN {$countSql} >= 2 THEN 'rojo'
            WHEN {$countSql} >= 1 THEN 'amarillo'
            ELSE 'verde'
        END";
    }

    public function factoresAuditoriaCountSql(): string
    {
        return '('.implode(' + ', [
            'CASE WHEN '.$this->estadoComiteSql()." = 'vencido' THEN 1 ELSE 0 END",
            'CASE WHEN COALESCE("Imp_Res_Audi_Mes", 0) > '.self::AUDITORIA_ELEVADA_MIN.' THEN 1 ELSE 0 END',
            'CASE WHEN '.$this->rangoRotacionSql()." IN ('cero', 'critico') THEN 1 ELSE 0 END",
            'CASE WHEN '.$this->auditoriaPendienteSql().' THEN 1 ELSE 0 END',
        ]).')';
    }

    public function levelFromCriticalCount(int $count): string
    {
        if ($count >= self::FACTORES_ROJO_MIN) {
            return 'rojo';
        }

        if ($count >= self::FACTORES_AMARILLO_MIN) {
            return 'amarillo';
        }

        return 'verde';
    }

    public function levelFromAuditCount(int $count): string
    {
        if ($count >= 2) {
            return 'rojo';
        }

        if ($count >= 1) {
            return 'amarillo';
        }

        return 'verde';
    }

    public function factoresCriticosCountSql(): string
    {
        return implode(' + ', array_map(
            fn (string $indicador) => 'CASE WHEN '.$this->indicadorCriticoSql($indicador).' THEN 1 ELSE 0 END',
            array_keys($this->indicadorLabels())
        ));
    }

    public function indicadorLabels(): array
    {
        return IndicadorPresenter::factorLabels();
    }

    public function sinCapitalSql(): string
    {
        return '"Cap_Tot" IS NULL OR COALESCE("Cap_Tot", 0) = 0';
    }

    public function camposIncompletosSql(array $columns): string
    {
        return collect($columns)
            ->reject(fn (string $column) => str_contains($column, 'Sup_CRA'))
            ->map(fn (string $column) => 'NULLIF(TRIM(COALESCE("'.$column.'"::text, \'\')), \'\') IS NULL OR TRIM(COALESCE("'.$column.'"::text, \'\')) = \'0\'')
            ->implode(' OR ');
    }
}

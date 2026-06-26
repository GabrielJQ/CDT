<?php

namespace App\Servicios;

use Illuminate\Database\Query\Builder;

class ServicioKpiTiendas
{
    public function __construct(
        private ServicioIndicadorCriticidad $indicadores,
    ) {}

    public function kpisConectividad(Builder $query): array
    {
        $total = (clone $query)->count();
        $kpis = [];

        foreach ([
            'TELEFONIA' => ['label' => 'Teléfono', 'icon' => '📞'],
            'INTERNET' => ['label' => 'Internet', 'icon' => '🌐'],
            'Señal de celular' => ['label' => 'Señal Celular', 'icon' => '📱'],
        ] as $column => $info) {
            $yes = (clone $query)->where($column, 'S')->count();
            $no = (clone $query)->where($column, 'N')->count();
            $undef = $total - $yes - $no;
            $pctYes = $total > 0 ? round($yes / $total * 100) : 0;
            $kpis[$column] = [
                'label' => $info['label'],
                'icon' => $info['icon'],
                'yes' => $yes,
                'no' => $no,
                'undef' => $undef,
                'pctYes' => $pctYes,
                'pctNo' => 100 - $pctYes,
            ];
        }

        $companies = (clone $query)
            ->selectRaw('COALESCE(NULLIF(TRIM("Compañía"), \'\'), \'Sin dato\') as compania, COUNT(*) as total')
            ->where('Señal de celular', 'S')
            ->groupBy('compania')
            ->orderByDesc('total')
            ->get();
        $totalCompanies = (int) $companies->sum('total');
        $kpis['_compania'] = $companies->mapWithKeys(fn ($row) => [
            $row->compania => [
                'count' => (int) $row->total,
                'pct' => $totalCompanies > 0 ? round(((int) $row->total) / $totalCompanies * 100) : 0,
            ],
        ])->all();
        $kpis['_total'] = $total;

        return $kpis;
    }

    public function companiasConectividad(Builder $query): array
    {
        return (clone $query)
            ->selectRaw('DISTINCT COALESCE(NULLIF(TRIM("Compañía"), \'\'), \'Sin dato\') as compania')
            ->orderBy('compania')
            ->pluck('compania')
            ->filter()
            ->values()
            ->all();
    }

    public function kpisAuditoria(Builder $query, bool $usarDerivados = false): array
    {
        $estadoComiteSql = $usarDerivados ? 'estado_comite' : $this->indicadores->estadoComiteSql();
        $rangoRotacionSql = $usarDerivados ? 'rango_rotacion' : $this->indicadores->rangoRotacionSql();
        $auditoriaPendienteSql = $usarDerivados ? 'auditoria_pendiente = true' : $this->indicadores->auditoriaPendienteSql();
        $sinAuditoriaAnioSql = $this->indicadores->sinAuditoriaAnioSql();

        $row = (clone $query)->selectRaw("
            SUM(CASE WHEN {$estadoComiteSql} = 'vencido' THEN 1 ELSE 0 END) as comites_vencidos,
            SUM(CASE WHEN COALESCE(\"Imp_Res_Audi_Mes\", 0) > ".ServicioIndicadorCriticidad::AUDITORIA_ELEVADA_MIN." THEN 1 ELSE 0 END) as auditoria_alta,
            SUM(CASE WHEN {$rangoRotacionSql} IN ('critico', 'cero') THEN 1 ELSE 0 END) as rotacion_baja,
            SUM(CASE WHEN {$auditoriaPendienteSql} THEN 1 ELSE 0 END) as auditoria_pendiente,
            SUM(CASE WHEN {$rangoRotacionSql} = 'cero' THEN 1 ELSE 0 END) as rotacion_cero,
            SUM(CASE WHEN {$rangoRotacionSql} = 'critico' THEN 1 ELSE 0 END) as rotacion_critico,
            SUM(CASE WHEN {$rangoRotacionSql} = 'amarillo' THEN 1 ELSE 0 END) as rotacion_amarillo,
            SUM(CASE WHEN {$rangoRotacionSql} = 'optimo' THEN 1 ELSE 0 END) as rotacion_optimo,
            SUM(CASE WHEN COALESCE(\"Audit_Realiza_Mes\", 0) > 0 THEN 1 ELSE 0 END) as auditorias_mes,
            SUM(CASE WHEN {$auditoriaPendienteSql} THEN 1 ELSE 0 END) as sin_auditoria_trimestre,
            SUM(CASE WHEN {$sinAuditoriaAnioSql} THEN 1 ELSE 0 END) as sin_auditoria_anio
        ")->first();

        return [
            'comitesVencidos' => (int) ($row->comites_vencidos ?? 0),
            'auditoriaAlta' => (int) ($row->auditoria_alta ?? 0),
            'rotacionBaja' => (int) ($row->rotacion_baja ?? 0),
            'auditoriaPendiente' => (int) ($row->auditoria_pendiente ?? 0),
            'rotacionCero' => (int) ($row->rotacion_cero ?? 0),
            'rotacionCritico' => (int) ($row->rotacion_critico ?? 0),
            'rotacionAmarillo' => (int) ($row->rotacion_amarillo ?? 0),
            'rotacionOptimo' => (int) ($row->rotacion_optimo ?? 0),
            'auditoriasMes' => (int) ($row->auditorias_mes ?? 0),
            'sinAuditoriaTrimestre' => (int) ($row->sin_auditoria_trimestre ?? 0),
            'sinAuditoriaAnio' => (int) ($row->sin_auditoria_anio ?? 0),
        ];
    }

    public function kpisAperturas(Builder $query): array
    {
        $row = (clone $query)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN \"Fecha_Apertura\" >= DATE_TRUNC('month', CURRENT_DATE) THEN 1 ELSE 0 END) as este_mes,
            SUM(CASE WHEN \"Fecha_Apertura\" >= DATE_TRUNC('year', CURRENT_DATE) THEN 1 ELSE 0 END) as este_anio,
            SUM(CASE WHEN \"Fecha_Apertura\" IS NULL THEN 1 ELSE 0 END) as sin_fecha
        ")->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'esteMes' => (int) ($row->este_mes ?? 0),
            'esteAnio' => (int) ($row->este_anio ?? 0),
            'sinFecha' => (int) ($row->sin_fecha ?? 0),
        ];
    }

    public function sinConectividadCount(Builder $query): int
    {
        return (clone $query)
            ->where(function ($query) {
                $query->whereNull('TELEFONIA')->orWhere('TELEFONIA', '!=', 'S');
            })
            ->where(function ($query) {
                $query->whereNull('INTERNET')->orWhere('INTERNET', '!=', 'S');
            })
            ->where(function ($query) {
                $query->whereNull('Señal de celular')->orWhere('Señal de celular', '!=', 'S');
            })
            ->count();
    }

    public function aperturasEsteMesCount(Builder $query): int
    {
        return (clone $query)
            ->where('Fecha_Apertura', '>=', now()->startOfMonth()->toDateString())
            ->where('Fecha_Apertura', '<=', now()->endOfMonth()->toDateString())
            ->count();
    }

    public function geoStats(Builder $query, bool $usarDerivados = false): array
    {
        if ($usarDerivados) {
            $row = (clone $query)->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN estado_geo = 'OK' THEN 1 ELSE 0 END) as ok,
                SUM(CASE WHEN estado_geo = 'SIN_COORDENADAS' THEN 1 ELSE 0 END) as sin_coordenadas,
                SUM(CASE WHEN estado_geo = 'FUERA_MEXICO' THEN 1 ELSE 0 END) as fuera_mexico,
                SUM(CASE WHEN estado_geo = 'FUERA_ESTADO' THEN 1 ELSE 0 END) as fuera_estado
            ")->first();

            $sinCoordenadas = (int) ($row->sin_coordenadas ?? 0);
            $fueraMexico = (int) ($row->fuera_mexico ?? 0);
            $fueraEstado = (int) ($row->fuera_estado ?? 0);

            return [
                'OK' => (int) ($row->ok ?? 0),
                'SIN_COORDENADAS' => $sinCoordenadas,
                'FUERA_MEXICO' => $fueraMexico,
                'FUERA_ESTADO' => $fueraEstado,
                'conCoordenadas' => (int) ($row->total ?? 0) - $sinCoordenadas,
                'sinCoordenadas' => $sinCoordenadas,
                'incidencias' => $sinCoordenadas + $fueraMexico,
            ];
        }

        $row = (clone $query)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN \"Latitud\" IS NOT NULL AND \"Longitud\" IS NOT NULL AND \"Latitud\" != '0' AND \"Longitud\" != '0' THEN 1 ELSE 0 END) as con_coordenadas,
            SUM(CASE WHEN \"Latitud\" IS NULL OR \"Longitud\" IS NULL OR \"Latitud\" = '0' OR \"Longitud\" = '0' THEN 1 ELSE 0 END) as sin_coordenadas
        ")->first();

        $sinCoordenadas = (int) ($row->sin_coordenadas ?? 0);

        return [
            'OK' => (int) ($row->con_coordenadas ?? 0),
            'SIN_COORDENADAS' => $sinCoordenadas,
            'FUERA_MEXICO' => 0,
            'FUERA_ESTADO' => 0,
            'conCoordenadas' => (int) ($row->con_coordenadas ?? 0),
            'sinCoordenadas' => $sinCoordenadas,
            'incidencias' => $sinCoordenadas,
        ];
    }

    public function aperturasKpiDashboard(Builder $query): array
    {
        $row = (clone $query)->selectRaw("
            SUM(CASE WHEN \"Fecha_Apertura\" IS NOT NULL THEN 1 ELSE 0 END) as total,
            SUM(CASE WHEN \"Fecha_Apertura\" >= DATE_TRUNC('year', CURRENT_DATE) THEN 1 ELSE 0 END) as este_anio
        ")->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'esteAnio' => (int) ($row->este_anio ?? 0),
        ];
    }

    public function aperturasPorMes(Builder $query): array
    {
        $nombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $meses = [];
        $now = now();
        for ($i = 11; $i >= 0; $i--) {
            $date = (clone $now)->subMonths($i);
            $meses[$date->format('Y-m')] = ['label' => $nombres[(int) $date->format('n') - 1], 'count' => 0];
        }

        $rows = (clone $query)
            ->selectRaw('TO_CHAR("Fecha_Apertura", \'YYYY-MM\') as mes, COUNT(*) as total')
            ->whereNotNull('Fecha_Apertura')
            ->where('Fecha_Apertura', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->groupBy('mes')
            ->pluck('total', 'mes');

        foreach ($rows as $mes => $total) {
            if (isset($meses[$mes])) {
                $meses[$mes]['count'] = (int) $total;
            }
        }

        return array_values($meses);
    }

    public function statsDirectorio(Builder $query, array $trackedColumns): array
    {
        $incompletosSql = $this->indicadores->camposIncompletosSql($trackedColumns);
        $sinCapitalSql = $this->indicadores->sinCapitalSql();
        $comitesSql = 'NULLIF(TRIM(COALESCE("Nom_Pre_CRA"::text, \'\')), \'\') IS NULL OR NULLIF(TRIM(COALESCE("Nom_Sec_CRA"::text, \'\')), \'\') IS NULL OR NULLIF(TRIM(COALESCE("Nom_Tes_CRA"::text, \'\')), \'\') IS NULL';

        $row = (clone $query)->selectRaw("
            SUM(CASE WHEN {$incompletosSql} THEN 1 ELSE 0 END) as incompletos,
            SUM(CASE WHEN {$sinCapitalSql} THEN 1 ELSE 0 END) as sin_capital,
            SUM(CASE WHEN {$comitesSql} THEN 1 ELSE 0 END) as comites_incompletos,
            SUM(CASE WHEN COALESCE(\"Asam_Real_Mes\", 0) > 0 THEN 1 ELSE 0 END) as asambleas_mes,
            SUM(CASE WHEN COALESCE(\"Cap_Dic\", 0) - COALESCE(\"Cap_Tot\", 0) > 0 THEN 1 ELSE 0 END) as tiendas_faltante,
            SUM(GREATEST(COALESCE(\"Cap_Dic\", 0) - COALESCE(\"Cap_Tot\", 0), 0)) as importe_faltante,
            SUM(CASE WHEN \"Pagare_Fecha\" IS NOT NULL AND \"Pagare_Fecha\" <= CURRENT_DATE - INTERVAL '1 year' THEN 1 ELSE 0 END) as pagares_vencidos,
            SUM(CASE WHEN \"Pagare_Fecha\" IS NOT NULL AND \"Pagare_Fecha\" <= CURRENT_DATE - INTERVAL '1 year' THEN COALESCE(\"Pagare_Monto\", 0) ELSE 0 END) as importe_pagares_vencidos
        ")->first();

        return [
            'incompletos' => (int) ($row->incompletos ?? 0),
            'sinCapital' => (int) ($row->sin_capital ?? 0),
            'comitesIncompletos' => (int) ($row->comites_incompletos ?? 0),
            'asambleasMes' => (int) ($row->asambleas_mes ?? 0),
            'tiendasFaltante' => (int) ($row->tiendas_faltante ?? 0),
            'importeFaltante' => (float) ($row->importe_faltante ?? 0),
            'pagaresVencidos' => (int) ($row->pagares_vencidos ?? 0),
            'importePagaresVencidos' => (float) ($row->importe_pagares_vencidos ?? 0),
        ];
    }

    public function resumenCriticidad(Builder $query, bool $usarDerivados = false): array
    {
        $countSql = $this->indicadores->factoresCriticosCountSql();

        $nivelSql = $usarDerivados
            ? "
                SUM(CASE WHEN nivel_critico = 'rojo' THEN 1 ELSE 0 END) as rojo,
                SUM(CASE WHEN nivel_critico = 'amarillo' THEN 1 ELSE 0 END) as amarillo,
                SUM(CASE WHEN nivel_critico = 'verde' THEN 1 ELSE 0 END) as verde,
            "
            : "
                SUM(CASE WHEN {$countSql} >= ".ServicioIndicadorCriticidad::FACTORES_ROJO_MIN." THEN 1 ELSE 0 END) as rojo,
                SUM(CASE WHEN {$countSql} >= ".ServicioIndicadorCriticidad::FACTORES_AMARILLO_MIN." AND {$countSql} < ".ServicioIndicadorCriticidad::FACTORES_ROJO_MIN." THEN 1 ELSE 0 END) as amarillo,
                SUM(CASE WHEN {$countSql} < ".ServicioIndicadorCriticidad::FACTORES_AMARILLO_MIN.' THEN 1 ELSE 0 END) as verde,
            ';

        $row = (clone $query)->selectRaw("
            {$nivelSql}
            SUM(CASE WHEN {$this->indicadores->indicadorCriticoSql('capital_bajo', $usarDerivados)} THEN 1 ELSE 0 END) as capital_bajo,
            SUM(CASE WHEN {$this->indicadores->indicadorCriticoSql('capital_dictaminado_bajo', $usarDerivados)} THEN 1 ELSE 0 END) as capital_dictaminado_bajo,
            SUM(CASE WHEN {$this->indicadores->indicadorCriticoSql('comite_vencido', $usarDerivados)} THEN 1 ELSE 0 END) as comite_vencido,
            SUM(CASE WHEN {$this->indicadores->indicadorCriticoSql('auditoria_elevada', $usarDerivados)} THEN 1 ELSE 0 END) as auditoria_elevada,
            SUM(CASE WHEN {$this->indicadores->indicadorCriticoSql('pagare_vencido', $usarDerivados)} THEN 1 ELSE 0 END) as pagare_vencido,
            SUM(CASE WHEN {$this->indicadores->indicadorCriticoSql('rotacion_baja', $usarDerivados)} THEN 1 ELSE 0 END) as rotacion_baja,
            SUM(CASE WHEN {$this->indicadores->indicadorCriticoSql('asamblea_pendiente', $usarDerivados)} THEN 1 ELSE 0 END) as asamblea_pendiente
        ")->first();

        $labels = $this->indicadores->indicadorLabels();
        $desgloseLabels = [];
        foreach ($labels as $key => $label) {
            $count = (int) ($row->{$key} ?? 0);
            if ($count > 0) {
                $desgloseLabels[] = ['key' => $key, 'label' => $label, 'count' => $count];
            }
        }

        usort($desgloseLabels, fn (array $a, array $b) => $b['count'] <=> $a['count']);

        return [
            'rojo' => (int) ($row->rojo ?? 0),
            'amarillo' => (int) ($row->amarillo ?? 0),
            'verde' => (int) ($row->verde ?? 0),
            'desgloseLabels' => $desgloseLabels,
        ];
    }
}

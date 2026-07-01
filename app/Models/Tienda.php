<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tienda extends Model
{
    public const CAPITAL_BAJO_THRESHOLD = 20000;

    public const CAPITAL_DICTAMINADO_BAJO_THRESHOLD = 20000;

    public const AUDITORIA_ELEVADA_THRESHOLD = 500000;

    public const NIVEL_ROJO_THRESHOLD = 4;

    public const NIVEL_AMARILLO_THRESHOLD = 2;

    public const ROTACION_CRITICO_THRESHOLD = 0.5;

    public const ROTACION_AMARILLO_THRESHOLD = 1.0;

    public const COMITE_PROXIMO_VENCER_DAYS = 30;

    public const AUDITORIA_PENDIENTE_MESES = 3;

    public const PAGARE_VENCIDO_ANIOS = 1;

    public function getConnectionName(): string
    {
        return config('database.imports');
    }

    protected $table = 'tiendas';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'es_activo' => 'boolean',
            'Latitud' => 'float',
            'Longitud' => 'float',
            'Vta_Mes' => 'float',
            'Cap_Dic' => 'float',
            'Cap_Tot' => 'float',
            'Imp_Res_Audi_Mes' => 'float',
            'Pagare_Fecha' => 'date',
            'Fch_Audit' => 'date',
            'Vigencia' => 'date',
            'Fecha_Apertura' => 'date',
            'Asam_Prog_Mes' => 'integer',
            'Asam_Real_Mes' => 'integer',
        ];
    }

    public function scopeActivo($query)
    {
        $query->where('es_activo', true);
    }

    public function scopeRegional($query, ?string $region)
    {
        if ($region !== null && $region !== '') {
            $query->where('Clave_Regional', $region);
        }
    }

    public function scopeUnidadOperativa($query, ?string $uo)
    {
        if ($uo !== null && $uo !== '') {
            $query->where('Clave_UniOpe', $uo);
        }
    }

    public function scopeAlmacen($query, ?string $almacen)
    {
        if ($almacen !== null && $almacen !== '') {
            $query->whereRaw('LOWER("Nombre_Almacen") LIKE LOWER(?)', ['%'.$almacen.'%']);
        }
    }

    public function scopeConectividad($query, array $filters)
    {
        foreach ([
            'telefono' => 'TELEFONIA',
            'senial' => 'Señal de celular',
            'internet' => 'INTERNET',
        ] as $filterKey => $column) {
            if (($filters[$filterKey] ?? '') === 'si') {
                $query->where($column, 'S');
            }
            if (($filters[$filterKey] ?? '') === 'no') {
                $query->where($column, 'N');
            }
        }

        if (($filters['compania'] ?? '') !== '') {
            $company = strtoupper(trim($filters['compania']));
            if ($company === 'SIN DATO' || $company === 'SIN_DATO') {
                $query->where(function ($q) {
                    $q->whereNull('Compañía')
                        ->orWhere('Compañía', '')
                        ->orWhereRaw('UPPER(TRIM("Compañía")) IN (?, ?)', ['SIN DATO', 'NINGUNO']);
                });
            } else {
                $query->whereRaw('UPPER(TRIM("Compañía")) = ?', [$company]);
            }
        }
    }

    public function scopeDirectorio($query, array $filters, array $trackedColumns)
    {
        if (($filters['q'] ?? '') !== '') {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER("Nombre_Almacen") LIKE LOWER(?)', [$term])
                    ->orWhereRaw('LOWER(CAST("No_Tienda_Actual" AS TEXT)) LIKE LOWER(?)', [$term])
                    ->orWhereRaw('LOWER("Municipio") LIKE LOWER(?)', [$term]);
            });
        }

        if (! empty($filters['incompletos'])) {
            $cols = collect($trackedColumns)
                ->reject(fn (string $c) => str_contains($c, 'Sup_CRA'))
                ->map(fn (string $c) => 'NULLIF(TRIM(COALESCE(CAST("'.$c.'" AS TEXT), \'\')), \'\') IS NULL OR TRIM(COALESCE(CAST("'.$c.'" AS TEXT), \'\')) = \'0\'')
                ->implode(' OR ');
            $query->whereRaw($cols);
        }

        if (! empty($filters['sinCapital'])) {
            $query->whereRaw('(COALESCE("Cap_Tot", 0) > 0 AND COALESCE("Cap_Tot", 0) <= 20000)');
        }
    }

    public function scopeCriticidad($query, array $filters)
    {
        if (($filters['nivel'] ?? '') !== '') {
            $query->where('nivel_critico', $filters['nivel']);
        }

        if (($filters['indicador'] ?? '') !== '') {
            $condition = match ($filters['indicador']) {
                'capital_bajo' => 'COALESCE("Cap_Tot", 0) > 0 AND COALESCE("Cap_Tot", 0) <= 20000',
                'capital_dictaminado_bajo' => 'COALESCE("Cap_Dic", 0) > 0 AND COALESCE("Cap_Dic", 0) <= 20000',
                'comite_vencido' => "estado_comite = 'vencido'",
                'auditoria_elevada' => 'COALESCE("Imp_Res_Audi_Mes", 0) > 500000',
                'pagare_vencido' => '"Pagare_Fecha" IS NOT NULL AND "Pagare_Fecha" <= CURRENT_DATE - INTERVAL \'1 year\'',
                'rotacion_baja' => "rango_rotacion IN ('cero', 'critico')",
                'asamblea_pendiente' => 'COALESCE("Asam_Prog_Mes", 0) > 0 AND COALESCE("Asam_Real_Mes", 0) = 0',
                default => null,
            };
            if ($condition !== null) {
                $query->whereRaw($condition);
            }
        }
    }

    public function scopeAuditoria($query, array $filters)
    {
        if (($filters['nivel'] ?? '') !== '') {
            $query->where('nivel_critico', $filters['nivel']);
        }

        if (($filters['estado_comite'] ?? '') !== '') {
            $query->where('estado_comite', $filters['estado_comite']);
        }

        if (($filters['estado_auditoria'] ?? '') === 'vencida') {
            $query->where('auditoria_pendiente', true);
        } elseif (($filters['estado_auditoria'] ?? '') === 'al_dia') {
            $query->where('auditoria_pendiente', false)->whereNotNull('Fch_Audit');
        } elseif (($filters['estado_auditoria'] ?? '') === 'sin_fecha') {
            $query->whereNull('Fch_Audit');
        }

        if (($filters['filtro_500k'] ?? '') === 'si') {
            $query->where('Imp_Res_Audi_Mes', '>', 500000);
        } elseif (($filters['filtro_500k'] ?? '') === 'no') {
            $query->where(function ($q) {
                $q->whereNull('Imp_Res_Audi_Mes')->orWhere('Imp_Res_Audi_Mes', '<=', 500000);
            });
        }

        if (($filters['rango_rotacion'] ?? '') !== '') {
            $query->where('rango_rotacion', $filters['rango_rotacion']);
        }

        if (($filters['tiempo_auditoria'] ?? '') === 'mes') {
            $query->where('Audit_Realiza_Mes', '>', 0);
        } elseif (($filters['tiempo_auditoria'] ?? '') === 'trimestre') {
            $query->where('auditoria_pendiente', true);
        } elseif (($filters['tiempo_auditoria'] ?? '') === 'anio') {
            $query->where(function ($q) {
                $q->whereNull('Fch_Audit')
                    ->orWhere('Fch_Audit', '<=', now()->subYear()->toDateString());
            });
        }

        if (($filters['asambleas_mes'] ?? '') === 'si') {
            $query->where('Asam_Real_Mes', '>', 0);
        } elseif (($filters['asambleas_mes'] ?? '') === 'no') {
            $query->where(function ($q) {
                $q->whereNull('Asam_Real_Mes')->orWhere('Asam_Real_Mes', '<=', 0);
            });
        }
    }

    public function scopeAperturas($query, array $filters)
    {
        if (($filters['desde'] ?? '') !== '') {
            $query->where('Fecha_Apertura', '>=', $filters['desde']);
        }

        if (($filters['hasta'] ?? '') !== '') {
            $query->where('Fecha_Apertura', '<=', $filters['hasta']);
        }
    }

    public function scopeMapa($query, array $filters)
    {
        if (($filters['estado_geo'] ?? '') !== '') {
            if ($filters['estado_geo'] === 'INCIDENCIAS') {
                $query->whereIn('estado_geo', ['SIN_COORDENADAS', 'FUERA_MEXICO']);

                return;
            }
            $query->where('estado_geo', $filters['estado_geo']);
        }
    }

    public function scopeBounds($query, array $bounds)
    {
        foreach (['north', 'south', 'east', 'west'] as $key) {
            if (! isset($bounds[$key]) || ! is_numeric($bounds[$key])) {
                return;
            }
        }

        $north = min(90, (float) $bounds['north']);
        $south = max(-90, (float) $bounds['south']);
        $east = min(180, (float) $bounds['east']);
        $west = max(-180, (float) $bounds['west']);

        if ($south >= $north || $west >= $east) {
            return;
        }

        $query->whereBetween('Latitud', [$south, $north])
            ->whereBetween('Longitud', [$west, $east]);
    }

    public function scopeTiendaSalud($query, ?string $tipo)
    {
        if ($tipo === 'salud') {
            $query->where('es_tienda_salud', true);
        } elseif ($tipo === 'bienestar') {
            $query->where(function ($q) {
                $q->whereNull('es_tienda_salud')->orWhere('es_tienda_salud', false);
            });
        }
    }

    public function getNivelCriticoAttribute(): ?string
    {
        $value = $this->getRawOriginal('nivel_critico');
        if ($value !== null) {
            return $value;
        }

        $factores = $this->getRawOriginal('factores_criticos_count');
        if ($factores === null) {
            return null;
        }

        return match (true) {
            (int) $factores >= static::NIVEL_ROJO_THRESHOLD => 'rojo',
            (int) $factores >= static::NIVEL_AMARILLO_THRESHOLD => 'amarillo',
            default => 'verde',
        };
    }

    public function getEstadoComiteAttribute(): ?string
    {
        $value = $this->getRawOriginal('estado_comite');
        if ($value !== null) {
            return $value;
        }

        $vigencia = $this->getRawOriginal('Vigencia');
        if ($vigencia === null) {
            return 'sin_fecha';
        }

        $vigencia = now()->parse($vigencia);

        if ($vigencia->lte(now())) {
            return 'vencido';
        }

        if ($vigencia->lte(now()->addDays(static::COMITE_PROXIMO_VENCER_DAYS))) {
            return 'proximo_a_vencer';
        }

        return 'vigente';
    }

    public function getRangoRotacionAttribute(): ?string
    {
        $value = $this->getRawOriginal('rango_rotacion');
        if ($value !== null) {
            return $value;
        }

        $capDic = (float) ($this->getRawOriginal('Cap_Dic') ?? 0);
        $vtaMes = (float) ($this->getRawOriginal('Vta_Mes') ?? 0);

        if ($capDic <= 0 || $vtaMes === 0.0) {
            return 'cero';
        }

        $ratio = $vtaMes / $capDic;

        return match (true) {
            $ratio < static::ROTACION_CRITICO_THRESHOLD => 'critico',
            $ratio < static::ROTACION_AMARILLO_THRESHOLD => 'amarillo',
            default => 'optimo',
        };
    }

    public function getAuditoriaPendienteAttribute(): bool
    {
        $value = $this->getRawOriginal('auditoria_pendiente');
        if ($value !== null) {
            return (bool) $value;
        }

        $fchAudit = $this->getRawOriginal('Fch_Audit');
        if ($fchAudit === null) {
            return true;
        }

        return now()->parse($fchAudit)->lte(now()->subMonths(static::AUDITORIA_PENDIENTE_MESES));
    }

    public function getFactoresCriticosCountAttribute(): ?int
    {
        $value = $this->getRawOriginal('factores_criticos_count');
        if ($value !== null) {
            return (int) $value;
        }

        $capTot = (float) ($this->getRawOriginal('Cap_Tot') ?? 0);
        $capDic = (float) ($this->getRawOriginal('Cap_Dic') ?? 0);
        $estadoComite = $this->getRawOriginal('estado_comite');
        $impResAudi = (float) ($this->getRawOriginal('Imp_Res_Audi_Mes') ?? 0);
        $pagareFecha = $this->getRawOriginal('Pagare_Fecha');
        $rangoRotacion = $this->getRawOriginal('rango_rotacion');
        $asamProg = (int) ($this->getRawOriginal('Asam_Prog_Mes') ?? 0);
        $asamReal = (int) ($this->getRawOriginal('Asam_Real_Mes') ?? 0);

        $count = 0;
        if ($capTot > 0 && $capTot <= static::CAPITAL_BAJO_THRESHOLD) {
            $count++;
        }
        if ($capDic > 0 && $capDic <= static::CAPITAL_DICTAMINADO_BAJO_THRESHOLD) {
            $count++;
        }
        if ($estadoComite === 'vencido') {
            $count++;
        }
        if ($impResAudi > static::AUDITORIA_ELEVADA_THRESHOLD) {
            $count++;
        }
        if ($pagareFecha !== null && now()->parse($pagareFecha)->lte(now()->subYears(static::PAGARE_VENCIDO_ANIOS))) {
            $count++;
        }
        if (in_array($rangoRotacion, ['cero', 'critico'], true)) {
            $count++;
        }
        if ($asamProg > 0 && $asamReal === 0) {
            $count++;
        }

        return $count;
    }
}

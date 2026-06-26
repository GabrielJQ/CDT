<?php

namespace App\Servicios;

use App\Models\User;
use Illuminate\Http\Request;

class ServicioAlcanceUsuario
{
    private const NO_ACCESS = '__NO_ACCESS__';

    public function __construct(
        private ServicioPeriodosImportacion $periodos,
    ) {}

    /**
     * @return array{region: string, uo: string, periodo_importacion_id: int|null}
     */
    public function filtroEfectivo(Request $request): array
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return ['region' => self::NO_ACCESS, 'uo' => self::NO_ACCESS, 'periodo_importacion_id' => null];
        }

        $filtro = $this->resolverFiltro($user, [
            'region' => (string) $request->cookie('region_filter', ''),
            'uo' => (string) $request->cookie('uo_filter', ''),
        ]);

        $periodo = $this->periodos->obtenerActivo('regular', $user);

        $filtro['periodo_importacion_id'] = $periodo?->id;

        return $filtro;
    }

    /**
     * @param  array{region?: string|null, uo?: string|null}  $requested
     * @return array{region: string, uo: string, periodo_importacion_id: int|null}
     */
    public function resolverFiltro(User $user, array $requested = []): array
    {
        $requestedRegion = trim((string) ($requested['region'] ?? ''));
        $requestedUo = trim((string) ($requested['uo'] ?? ''));

        $base = fn (array $extra = []): array => $extra + ['region' => '', 'uo' => '', 'periodo_importacion_id' => null];

        if ($user->hasGlobalAccess()) {
            return $base(['region' => $requestedRegion, 'uo' => $requestedUo]);
        }

        if ($user->isRegional()) {
            $region = $user->region?->clave;
            if ($region === null || $region === '') {
                return $base(['region' => self::NO_ACCESS, 'uo' => self::NO_ACCESS]);
            }

            $uo = '';
            if ($requestedUo !== '' && $user->region?->unidadesOperativas()->where('clave', $requestedUo)->exists()) {
                $uo = $requestedUo;
            }

            return $base(['region' => $region, 'uo' => $uo]);
        }

        if ($user->isUnidad()) {
            $region = $user->region?->clave;
            $uo = $user->unidadOperativa?->clave;
            if ($region === null || $region === '' || $uo === null || $uo === '') {
                return $base(['region' => self::NO_ACCESS, 'uo' => self::NO_ACCESS]);
            }

            return $base(['region' => $region, 'uo' => $uo]);
        }

        return $base(['region' => self::NO_ACCESS, 'uo' => self::NO_ACCESS]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $jerarquia
     * @return array<int, array<string, mixed>>
     */
    public function filtrarJerarquia(User $user, array $jerarquia): array
    {
        if ($user->hasGlobalAccess()) {
            return $jerarquia;
        }

        $filtro = $this->resolverFiltro($user);

        return collect($jerarquia)
            ->filter(fn (array $region): bool => (string) ($region['clave'] ?? '') === $filtro['region'])
            ->map(function (array $region) use ($filtro): array {
                if ($filtro['uo'] !== '') {
                    $region['uos'] = collect($region['uos'] ?? [])
                        ->filter(fn (array $uo): bool => (string) ($uo['clave'] ?? '') === $filtro['uo'])
                        ->values()
                        ->all();
                }

                return $region;
            })
            ->values()
            ->all();
    }

    public function scopeType(User $user): string
    {
        if ($user->hasGlobalAccess()) {
            return 'global';
        }

        if ($user->isRegional()) {
            return 'regional';
        }

        return 'unidad';
    }
}

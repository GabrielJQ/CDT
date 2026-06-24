<?php

use App\Servicios\ServicioAlcanceUsuario;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    private function uoFilter(): array
    {
        $filtro = app(ServicioAlcanceUsuario::class)->filtroEfectivo(request());
        $region = $filtro['region'];
        $uo = $filtro['uo'];

        if (empty($region) && empty($uo)) {
            return [];
        }

        $conn = DB::connection('pgsql_imports');

        if (! empty($uo)) {
            $names = $conn->table('tiendas')
                ->where('es_activo', true)
                ->where('Clave_UniOpe', $uo)
                ->distinct()
                ->pluck('Nombre_UniOpe')
                ->toArray();
        } else {
            $names = $conn->table('tiendas')
                ->where('es_activo', true)
                ->where('Clave_Regional', $region)
                ->distinct()
                ->pluck('Nombre_UniOpe')
                ->toArray();
        }

        return array_filter($names);
    }

    private function query()
    {
        $conn = DB::connection('pgsql_imports');
        $query = $conn->table('tiendas_casa_x_casa')->where('es_activo', true);
        $uoFilter = $this->uoFilter();
        if (! empty($uoFilter)) {
            $query->whereIn('unidad_operativa', $uoFilter);
        }

        return $query;
    }

    public function mapaData(): array
    {
        $q = $this->query();

        $totalCount = (clone $q)->count();

        $conCoordenadas = (clone $q)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('latitud', '!=', 0)
            ->where('longitud', '!=', 0)
            ->count();

        $anaqueles = [
            'instalados' => (clone $q)->where('anaqueles_instalados', true)->count(),
            'pendientes' => (clone $q)->where('anaqueles_instalados', false)->count(),
        ];

        return compact('totalCount', 'conCoordenadas', 'anaqueles');
    }
};
?>

@php
    extract($this->mapaData());
    $sinCoordenadas = max(0, $totalCount - $conCoordenadas);
    $pct = fn (int|float $value, int|float|null $base = null) => ($base ?? $totalCount) > 0 ? round($value / ($base ?? $totalCount) * 100, 1) : 0;
@endphp
<div class="page-shell">
    <section class="page-hero">
        <div class="page-hero-content">
            <div>
                <p class="eyebrow">Tiendas de Salud</p>
                <h1 class="page-heading">Mapa Casa por Casa</h1>
                <p class="page-subheading">Visualiza la cobertura territorial CxC, avance de anaqueles y tiendas con coordenadas disponibles por zona del mapa.</p>
            </div>
            <a href="{{ route('casa-x-casa.directorio') }}" class="btn-secondary">Abrir directorio</a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-3 mb-6 md:grid-cols-5">
        <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Total</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($totalCount) }}</p>
        </div>
        <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🟢 Instalados</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($anaqueles['instalados'] ?? 0) }} <span class="text-sm font-normal text-gray-400">({{ $pct($anaqueles['instalados'] ?? 0) }}%)</span></p>
        </div>
        <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-amber-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🟠 Pendientes</p>
            <p class="text-2xl font-bold text-amber-600">{{ number_format($anaqueles['pendientes'] ?? 0) }} <span class="text-sm font-normal text-gray-400">({{ $pct($anaqueles['pendientes'] ?? 0) }}%)</span></p>
        </div>
        <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">📍 Con coordenadas</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($conCoordenadas) }} <span class="text-sm font-normal text-gray-400">({{ $pct($conCoordenadas) }}%)</span></p>
        </div>
        <div class="kpi-gold-accent bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-gray-400">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">❌ Sin coordenadas</p>
            <p class="text-2xl font-bold text-gray-500">{{ number_format($sinCoordenadas) }} <span class="text-sm font-normal text-gray-400">({{ $pct($sinCoordenadas) }}%)</span></p>
        </div>
    </div>

    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Mostrando <strong id="visible-count">0</strong> tiendas visibles en la zona actual
        <span id="limited-label" class="hidden text-amber-600 dark:text-amber-300">(límite de carga alcanzado, acerca el zoom para ver más detalle)</span>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_20rem]">
        <div class="institutional-card p-2">
            <div id="map"></div>
        </div>
        <aside class="priority-panel">
            <p class="eyebrow">Avance</p>
            <h2 class="text-lg font-extrabold text-gray-900 dark:text-gray-100">Estado de anaqueles</h2>
            <div class="mt-4 space-y-3">
                <div class="priority-item">
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Instalados</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Marcadores verdes en el mapa.</p>
                    </div>
                    <span class="status-pill status-ok">{{ number_format($anaqueles['instalados'] ?? 0) }} · {{ $pct($anaqueles['instalados'] ?? 0) }}%</span>
                </div>
                <div class="priority-item">
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Pendientes</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Marcadores ámbar para seguimiento.</p>
                    </div>
                    <span class="status-pill status-warning">{{ number_format($anaqueles['pendientes'] ?? 0) }} · {{ $pct($anaqueles['pendientes'] ?? 0) }}%</span>
                </div>
                <div class="priority-item">
                    <div>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Sin coordenadas</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No se muestran hasta completar latitud/longitud.</p>
                    </div>
                    <span class="status-pill {{ $sinCoordenadas > 0 ? 'status-warning' : 'status-ok' }}">{{ number_format($sinCoordenadas) }} · {{ $pct($sinCoordenadas) }}%</span>
                </div>
            </div>
        </aside>
    </div>
</div>

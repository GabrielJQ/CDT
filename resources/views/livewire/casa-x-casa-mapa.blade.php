@php
    extract($this->mapaData());
    $sinCoordenadas = max(0, $totalCount - $conCoordenadas);
    $pct = fn (int|float $value, int|float|null $base = null) => ($base ?? $totalCount) > 0 ? round($value / ($base ?? $totalCount) * 100, 1) : 0;
@endphp
<div class="page-shell" wire:loading.class="opacity-70" wire:target="almacen,estado,uo,estatus,anaquelStatus,buscar,clearFilters">
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

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Almacén</label>
                <input type="text" wire:model.live.debounce.400ms="almacen" placeholder="Buscar..." class="input-filter">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estado</label>
                <select wire:model.live="estado" class="input-filter">
                    <option value="">Todos</option>
                    @foreach($filterOptions['estados'] ?? [] as $e)
                        <option value="{{ $e }}">{{ $e }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">UO</label>
                <select wire:model.live="uo" class="input-filter">
                    <option value="">Todas</option>
                    @foreach($filterOptions['unidadesOperativas'] ?? [] as $u)
                        <option value="{{ $u }}">{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estatus</label>
                <select wire:model.live="estatus" class="input-filter">
                    <option value="">Todos</option>
                    @foreach($filterOptions['estatusList'] ?? [] as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Anaquel</label>
                <select wire:model.live="anaquelStatus" class="input-filter">
                    <option value="">Todos</option>
                    <option value="instalados">Instalados</option>
                    <option value="pendientes">Pendientes</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="clearFilters" class="btn-secondary">Limpiar</button>
            </div>
        </div>
    </div>

    <x-export-button route="export.casa-x-casa-mapa" :params="['almacen' => 'almacen', 'estado' => 'estado', 'uo' => 'uo', 'estatus' => 'estatus', 'anaquelStatus' => 'anaquelStatus', 'buscar' => 'buscar']" class="mb-6" />

    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Mostrando <strong id="visible-count">0</strong> tiendas visibles en la zona actual
        <span id="limited-label" class="hidden text-amber-600 dark:text-amber-300">(límite de carga alcanzado, acerca el zoom para ver más detalle)</span>
        <span wire:loading class="ml-2 text-[#988256] font-semibold">Actualizando...</span>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_20rem]">
        <div class="institutional-card p-2">
            <div wire:ignore id="map"></div>
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

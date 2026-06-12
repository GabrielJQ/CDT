@extends('layouts.app', ['pageTitle' => 'Dashboard CxC'])

@section('title', 'Salud Casa por Casa — CDT')

@section('content')
@php
    $pct = fn (int|float $value, int|float|null $base = null) => ($base ?? $total) > 0 ? round($value / ($base ?? $total) * 100, 1) : 0;
@endphp
<div class="page-shell">
    <section class="page-hero">
        <div class="page-hero-content">
            <div>
                <p class="eyebrow">Tiendas de Salud</p>
                <h1 class="page-heading">Seguimiento Casa por Casa</h1>
                <p class="page-subheading">Monitorea estatus, anaqueles, aviso de funcionamiento y cruce contra el catalogo general de tiendas.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('casa-x-casa.directorio') }}" class="btn-institutional">Directorio CxC</a>
                <a href="{{ route('casa-x-casa.mapa') }}" class="btn-secondary">Ver mapa</a>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 lg:gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-3 lg:px-4 py-2 lg:py-3">
            <div class="text-lg lg:text-xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($total) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">🏪 Total tiendas CxC</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-3 lg:px-4 py-2 lg:py-3">
            <div class="text-lg lg:text-xl font-bold text-green-600">{{ number_format($anaqueles['instalados']) }} <span class="text-xs font-normal text-gray-400">({{ $pct($anaqueles['instalados']) }}%)</span></div>
            <div class="text-xs text-green-500">📦 Anaqueles instalados</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-3 lg:px-4 py-2 lg:py-3">
            <div class="text-lg lg:text-xl font-bold text-blue-600">{{ number_format($aviso['con_aviso']) }} <span class="text-xs font-normal text-gray-400">({{ $pct($aviso['con_aviso']) }}%)</span></div>
            <div class="text-xs text-blue-500">✅ Con aviso de funcionamiento</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-3 lg:px-4 py-2 lg:py-3">
            <div class="text-lg lg:text-xl font-bold text-purple-600">{{ number_format($cruce['enTiendas']) }} <span class="text-xs font-normal text-gray-400">({{ $pct($cruce['enTiendas']) }}%)</span></div>
            <div class="text-xs text-purple-500">🔗 También en catálogo general</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-5 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-3">📊 Estatus de tiendas</h3>
            @if($porEstatus->isNotEmpty())
                <div class="space-y-2">
                    @foreach($porEstatus as $e)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-400">{{ $e->estatus ?: 'Sin estatus' }}</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ number_format($e->total) }} <span class="text-gray-400">({{ $pct($e->total) }}%)</span></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $total > 0 ? round($e->total / $total * 100) : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 py-8 text-center">Sin datos de estatus</p>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-3">📦 Anaqueles instalados</h3>
            <div class="flex items-center justify-center h-32">
                <div class="text-center">
                    <div class="text-4xl font-bold text-green-600">{{ $anaqueles['instalados'] }}</div>
                    <div class="text-xs text-gray-500">Instalados ({{ $pct($anaqueles['instalados']) }}%)</div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-2">{{ $anaqueles['pendientes'] }}</div>
                    <div class="text-xs text-gray-500">Pendientes ({{ $pct($anaqueles['pendientes']) }}%)</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-3">📋 Aviso de funcionamiento</h3>
            <div class="flex items-center justify-center h-32">
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600">{{ $aviso['con_aviso'] }}</div>
                    <div class="text-xs text-gray-500">Con aviso ({{ $pct($aviso['con_aviso']) }}%)</div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-2">{{ $aviso['sin_aviso'] }}</div>
                    <div class="text-xs text-gray-500">Sin aviso ({{ $pct($aviso['sin_aviso']) }}%)</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-3">🔗 Cruce con catálogo general</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-600 dark:text-gray-400">En ambas tablas</span>
                    <span class="font-semibold text-green-600">{{ number_format($cruce['enTiendas']) }} <span class="text-gray-400">({{ $pct($cruce['enTiendas']) }}%)</span></span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-600 dark:text-gray-400">Solo en CxC</span>
                    <span class="font-semibold text-orange-600">{{ number_format($cruce['soloCxc']) }} <span class="text-gray-400">({{ $pct($cruce['soloCxc']) }}%)</span></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $total > 0 ? round($cruce['enTiendas'] / $total * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-3">🔤 Tipo de anaquel</h3>
            @if($porTipoAnaquel->isNotEmpty())
                <div class="space-y-2">
                    @foreach($porTipoAnaquel as $t)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-400">Tipo {{ $t->tipo_anaquel }}</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ number_format($t->total) }} <span class="text-gray-400">({{ $pct($t->total) }}%)</span></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $total > 0 ? round($t->total / $total * 100) : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 py-8 text-center">Sin datos</p>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-5">
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-3">🏢 Top Unidades Operativas</h3>
            @if($topUos->isNotEmpty())
                <div class="space-y-1.5 max-h-64 overflow-y-auto">
                    @foreach($topUos as $u)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600 dark:text-gray-400 truncate mr-2">{{ $u->unidad_operativa }}</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-100 shrink-0">{{ number_format($u->total) }} <span class="text-gray-400">({{ $pct($u->total) }}%)</span></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                            <div class="bg-indigo-500 h-1 rounded-full" style="width: {{ $total > 0 ? round($u->total / $total * 100) : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 py-8 text-center">Sin datos</p>
            @endif
        </div>
    </div>
</div>
@endsection

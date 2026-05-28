@extends('layouts.app', ['page-title' => 'Dashboard'])

@section('title', 'Dashboard — CDT')

@section('content')
    @isset($error)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">{{ $error }}</div>
    @endisset

    {{-- Welcome & count --}}
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Bienvenido al Panel de Monitoreo</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $totalCount }} tiendas registradas en el sistema</p>
            </div>
            <div class="text-4xl">🏪</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Connectivity Module Card --}}
        <a href="/conectividad" class="block bg-white rounded-xl shadow p-5 hover:shadow-lg transition border-l-4 border-blue-500 group">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-600 transition">📡 Conectividad</h3>
                <span class="text-sm text-blue-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if(!empty($connectivityKpis))
                <div class="grid grid-cols-3 gap-3 text-center">
                    @foreach(['TELEFONIA', 'Señal de celular', 'INTERNET'] as $key)
                        @php $k = $connectivityKpis[$key] ?? null; @endphp
                        @if($k)
                            <div class="p-2 bg-gray-50 rounded-lg">
                                <div class="text-lg font-bold text-gray-800">{{ $k['pctYes'] }}%</div>
                                <div class="text-xs text-gray-500">{{ $k['icon'] }} {{ $k['label'] }}</div>
                                <div class="text-xs text-gray-400">{{ $k['yes'] }} tiendas</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400">No hay datos de conectividad</p>
            @endif
        </a>

        {{-- Critical Stores Module Card --}}
        <a href="/tiendas-criticas" class="block bg-white rounded-xl shadow p-5 hover:shadow-lg transition border-l-4 border-red-500 group">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-red-600 transition">⚠️ Tiendas Críticas</h3>
                <span class="text-sm text-red-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($criticalSummary)
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-2 bg-red-50 rounded-lg">
                        <div class="text-lg font-bold text-red-600">{{ $criticalSummary['rojo'] }}</div>
                        <div class="text-xs text-gray-500">🔴 Críticas</div>
                    </div>
                    <div class="p-2 bg-yellow-50 rounded-lg">
                        <div class="text-lg font-bold text-yellow-600">{{ $criticalSummary['amarillo'] }}</div>
                        <div class="text-xs text-gray-500">🟡 Monitoreo</div>
                    </div>
                    <div class="p-2 bg-green-50 rounded-lg">
                        <div class="text-lg font-bold text-green-600">{{ $criticalSummary['verde'] }}</div>
                        <div class="text-xs text-gray-500">🟢 Normales</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">No hay datos de tiendas críticas</p>
            @endif
        </a>
    </div>

    {{-- Future modules placeholder --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow p-5 border border-dashed border-gray-300 opacity-50">
            <div class="text-center">
                <div class="text-3xl mb-2">📊</div>
                <h3 class="text-sm font-bold text-gray-500 uppercase">Ventas por Línea</h3>
                <p class="text-xs text-gray-400">Próximamente</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border border-dashed border-gray-300 opacity-50">
            <div class="text-center">
                <div class="text-3xl mb-2">🔍</div>
                <h3 class="text-sm font-bold text-gray-500 uppercase">Auditoría</h3>
                <p class="text-xs text-gray-400">Próximamente</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border border-dashed border-gray-300 opacity-50">
            <div class="text-center">
                <div class="text-3xl mb-2">🗺️</div>
                <h3 class="text-sm font-bold text-gray-500 uppercase">Directorio / Mapa</h3>
                <p class="text-xs text-gray-400">Próximamente</p>
            </div>
        </div>
    </div>
@endsection

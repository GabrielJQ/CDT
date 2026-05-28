@extends('layouts.app', ['page-title' => 'Dashboard'])

@section('title', 'Dashboard — CDT')

@section('content')
    @isset($error)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">{{ $error }}</div>
    @endisset

    {{-- Row 1 — 4 Metric KPIs --}}
    <div class="grid grid-cols-2 gap-4 mb-6">
        {{-- Total --}}
        <div class="bg-white rounded-xl shadow p-5">
            <div class="text-3xl font-bold text-gray-800">{{ $totalCount }}</div>
            <div class="text-sm text-gray-500 mt-1">🏪 Total de tiendas</div>
        </div>

        {{-- Críticas --}}
        <div class="bg-white rounded-xl shadow p-5">
            <div class="text-3xl font-bold text-red-600">{{ $criticalSummary['rojo'] }}</div>
            <div class="text-sm text-red-500 mt-1">⚠️ Tiendas críticas</div>
        </div>

        {{-- Sin conectividad --}}
        <div class="bg-white rounded-xl shadow p-5">
            <div class="text-3xl font-bold text-gray-800">{{ $sinConectividad }}</div>
            <div class="text-sm text-gray-500 mt-1">📡 Sin conectividad</div>
        </div>

        {{-- Aperturas este mes --}}
        <div class="bg-white rounded-xl shadow p-5">
            <div class="text-3xl font-bold text-blue-600">{{ $aperturasEsteMes }}</div>
            <div class="text-sm text-blue-500 mt-1">📅 Aperturas este mes</div>
        </div>
    </div>

    {{-- Row 2 — Module Access Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
        {{-- Conectividad --}}
        <a href="/conectividad" class="block bg-white rounded-xl shadow p-5 hover:shadow-lg transition border-l-4 border-blue-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-600 transition">📡 Conectividad</h3>
                <span class="text-xs text-blue-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if(!empty($connectivityKpis))
                <div class="grid grid-cols-3 gap-2 text-center">
                    @foreach(['TELEFONIA', 'Señal de celular', 'INTERNET'] as $key)
                        @php $k = $connectivityKpis[$key] ?? null; @endphp
                        @if($k)
                            <div class="p-1.5 bg-gray-50 rounded-lg">
                                <div class="text-base font-bold text-gray-800">{{ $k['pctYes'] }}%</div>
                                <div class="text-xs text-gray-500">{{ $k['icon'] }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400">Sin datos</p>
            @endif
        </a>

        {{-- Información de Tiendas --}}
        <a href="/informacion-tiendas" class="block bg-white rounded-xl shadow p-5 hover:shadow-lg transition border-l-4 border-red-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-red-600 transition">⚠️ Info. Tiendas</h3>
                <span class="text-xs text-red-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($criticalSummary)
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-1.5 bg-red-50 rounded-lg">
                        <div class="text-base font-bold text-red-600">{{ $criticalSummary['rojo'] }}</div>
                        <div class="text-xs text-gray-500">🔴 Críticas</div>
                    </div>
                    <div class="p-1.5 bg-yellow-50 rounded-lg">
                        <div class="text-base font-bold text-yellow-600">{{ $criticalSummary['amarillo'] }}</div>
                        <div class="text-xs text-gray-500">🟡 Monitoreo</div>
                    </div>
                    <div class="p-1.5 bg-green-50 rounded-lg">
                        <div class="text-base font-bold text-green-600">{{ $criticalSummary['verde'] }}</div>
                        <div class="text-xs text-gray-500">🟢 Normales</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">Sin datos</p>
            @endif
        </a>

        {{-- Mapa --}}
        <a href="/mapa" class="block bg-white rounded-xl shadow p-5 hover:shadow-lg transition border-l-4 border-emerald-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-emerald-600 transition">🗺️ Mapa</h3>
                <span class="text-xs text-emerald-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($geoStats)
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-1.5 bg-emerald-50 rounded-lg">
                        <div class="text-base font-bold text-emerald-600">{{ $geoStats['conCoordenadas'] }}</div>
                        <div class="text-xs text-gray-500">🟢 Geolocalizadas</div>
                    </div>
                    <div class="p-1.5 bg-gray-50 rounded-lg">
                        <div class="text-base font-bold text-gray-500">{{ $geoStats['sinCoordenadas'] }}</div>
                        <div class="text-xs text-gray-500">⚪ Sin coord.</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">Sin datos</p>
            @endif
        </a>

        {{-- Aperturas --}}
        <a href="/aperturas" class="block bg-white rounded-xl shadow p-5 hover:shadow-lg transition border-l-4 border-purple-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-purple-600 transition">🏗️ Aperturas</h3>
                <span class="text-xs text-purple-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($aperturasKpi)
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-1.5 bg-purple-50 rounded-lg">
                        <div class="text-base font-bold text-purple-600">{{ $aperturasKpi['total'] }}</div>
                        <div class="text-xs text-gray-500">Total</div>
                    </div>
                    <div class="p-1.5 bg-blue-50 rounded-lg">
                        <div class="text-base font-bold text-blue-600">{{ $aperturasKpi['esteAnio'] }}</div>
                        <div class="text-xs text-gray-500">Éste año</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">Sin datos</p>
            @endif
        </a>

        {{-- Directorio --}}
        <a href="/directorio" class="block bg-white rounded-xl shadow p-5 hover:shadow-lg transition border-l-4 border-amber-500 group">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-amber-600 transition">📋 Directorio</h3>
                <span class="text-xs text-amber-600 opacity-0 group-hover:opacity-100 transition">Ver más →</span>
            </div>
            @if($directorioStats)
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-1.5 bg-green-50 rounded-lg">
                        <div class="text-base font-bold text-green-600">{{ $directorioStats['completos'] }}</div>
                        <div class="text-xs text-gray-500">🟢 Completos</div>
                    </div>
                    <div class="p-1.5 bg-amber-50 rounded-lg">
                        <div class="text-base font-bold text-amber-600">{{ $directorioStats['incompletos'] }}</div>
                        <div class="text-xs text-gray-500">🟡 Incompletos</div>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">Sin datos</p>
            @endif
        </a>
    </div>

    @isset($updatedAt)
        <p class="text-xs text-gray-400 text-right">Última actualización: {{ $updatedAt }}</p>
    @endisset
@endsection
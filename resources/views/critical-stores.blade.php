@extends('layouts.app', ['page-title' => 'Tiendas Críticas'])

@section('title', 'Tiendas Críticas — Dashboard CDT')

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">🔴 Críticas</p>
            <p class="text-3xl font-bold text-red-600">{{ $summary['rojo'] }}</p>
            <p class="text-xs text-gray-400">{{ $totalCount > 0 ? round($summary['rojo'] / $totalCount * 100) : 0 }}% del total</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">🟡 Monitoreo</p>
            <p class="text-3xl font-bold text-yellow-600">{{ $summary['amarillo'] }}</p>
            <p class="text-xs text-gray-400">{{ $totalCount > 0 ? round($summary['amarillo'] / $totalCount * 100) : 0 }}% del total</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">🟢 Normales</p>
            <p class="text-3xl font-bold text-green-600">{{ $summary['verde'] }}</p>
            <p class="text-xs text-gray-400">{{ $totalCount > 0 ? round($summary['verde'] / $totalCount * 100) : 0 }}% del total</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase tracking-wide">🏪 Total tiendas</p>
            <p class="text-3xl font-bold text-blue-600">{{ $totalCount }}</p>
            <p class="text-xs text-gray-400">{{ $filteredCount !== $totalCount ? 'Filtradas: ' . $filteredCount : 'Sin filtros' }}</p>
        </div>
    </div>

    {{-- Desglose por factor --}}
    @if(!empty($summary['desgloseLabels']))
        <div class="bg-white rounded-xl shadow p-5 mb-6">
            <p class="text-sm text-gray-500 uppercase tracking-wide mb-3">📊 Factores más recurrentes</p>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($summary['desgloseLabels'] as $factor)
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-lg font-bold text-gray-800">{{ $factor['count'] }}</div>
                        <div class="text-xs text-gray-500">{{ $factor['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow p-4 mb-6">
        <form method="GET" action="/tiendas-criticas" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs text-gray-500 uppercase mb-1">Almacén</label>
                <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
                       placeholder="Buscar almacén..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs text-gray-500 uppercase mb-1">Nivel</label>
                <select name="nivel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                    <option value="">Todos</option>
                    <option value="rojo" {{ $filters['nivel'] === 'rojo' ? 'selected' : '' }}>🔴 Crítico</option>
                    <option value="amarillo" {{ $filters['nivel'] === 'amarillo' ? 'selected' : '' }}>🟡 Monitoreo</option>
                    <option value="verde" {{ $filters['nivel'] === 'verde' ? 'selected' : '' }}>🟢 Normal</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Filtrar
                </button>
                <a href="/tiendas-criticas" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition inline-block">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    @if(count($stores) > 0)
        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <div class="px-4 py-2 text-sm text-gray-500 border-b border-gray-100">
                Mostrando <strong>{{ $filteredCount }}</strong> de <strong>{{ $totalCount }}</strong> tiendas
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Almacén</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Municipio</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Factores</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($stores as $store)
                        @php
                            $e = $store['_critico'];
                            $nombre = $store['Nombre_Almacen'] ?? '—';
                            $municipio = $store['Municipio'] ?? '—';
                            $estado = $store['Estado'] ?? '—';

                            $levelConfig = [
                                'rojo' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Crítico'],
                                'amarillo' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Monitoreo'],
                                'verde' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Normal'],
                            ];
                            $cfg = $levelConfig[$e['level']] ?? $levelConfig['verde'];

                            $activeFactors = array_filter($e['labels'], function ($key) use ($e) {
                                return $e['conditions'][$key];
                            }, ARRAY_FILTER_USE_KEY);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cfg['bg'] }} {{ $cfg['text'] }}">
                                    {{ $e['count'] }} — {{ $cfg['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">{{ $nombre }}</td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $municipio }}</td>
                            <td class="px-4 py-3 text-center font-mono text-lg">
                                @foreach(['capital_bajo', 'comite_vencido', 'auditoria_elevada', 'pagare_proximo', 'rotacion_baja', 'asamblea_pendiente'] as $key)
                                    @if($e['conditions'][$key] ?? false)
                                        <span title="{{ $e['labels'][$key]['label'] ?? $key }}" class="cursor-help">🔴</span>
                                    @else
                                        <span class="text-gray-300">⚪</span>
                                    @endif
                                @endforeach
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 max-w-xs">
                                @if(count($activeFactors) > 0)
                                    <ul class="list-disc list-inside">
                                        @foreach($activeFactors as $factor)
                                            <li>{{ $factor['label'] }}: <span class="text-gray-400">{{ $factor['detail'] }}</span></li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-gray-400">Sin incidencias</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white rounded-xl shadow p-6 text-center text-gray-500">
            No hay datos para mostrar.
        </div>
    @endif
@endsection

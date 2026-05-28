@extends('layouts.app', ['page-title' => 'Apertura de Tiendas'])

@section('title', 'Aperturas — Dashboard CDT')

@push('head')
<style>
    .store-row { transition: background 0.15s; }
    .store-row:hover { background: #f9fafb; }
    .store-row.recent { background: #ecfdf5; }
    .store-row.recent:hover { background: #d1fae5; }
    .badge { display: inline-flex; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; }
</style>
@endpush

@section('content')
    @isset($error)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">{{ $error }}</div>
    @endisset

    <div id="app">
        {{-- KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">🏪 Tiendas</p>
                <p class="text-2xl font-bold text-gray-800">{{ $kpis['total'] }}</p>
                @if($filteredCount !== $totalCount)
                    <p class="text-xs text-gray-400">de {{ $totalCount }} totales</p>
                @endif
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">📅 Abiertas este mes</p>
                <p class="text-2xl font-bold text-green-600">{{ $kpis['esteMes'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-amber-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">📅 Abiertas este año</p>
                <p class="text-2xl font-bold text-amber-600">{{ $kpis['esteAnio'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-gray-400">
                <p class="text-xs text-gray-500 uppercase tracking-wide">⚠️ Sin fecha</p>
                <p class="text-2xl font-bold text-gray-600">{{ $kpis['sinFecha'] }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <form method="GET" action="/aperturas" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Almacén</label>
                    <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
                           placeholder="Buscar..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Desde</label>
                    <input type="date" name="desde" value="{{ $filters['desde'] }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Hasta</label>
                    <input type="date" name="hasta" value="{{ $filters['hasta'] }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Filtrar</button>
                    <a href="/aperturas" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition inline-block">Limpiar</a>
                </div>
            </form>
        </div>

        {{-- Count --}}
        <div class="text-sm text-gray-500 mb-2">
            Mostrando <strong>{{ count($stores) }}</strong> tiendas
            @if($filteredCount !== $totalCount)
                (filtradas de <strong>{{ $totalCount }}</strong>)
            @endif
        </div>

        {{-- Table --}}
        @if(count($stores) > 0)
            <div class="bg-white rounded-xl shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Almacén</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Localidad</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Municipio</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Apertura</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Antigüedad</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cap. Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">📞 Tel.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">🌐 Internet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @php $threeMonthsAgo = now()->subMonths(3); @endphp
                        @foreach($stores as $store)
                            @php
                                $fecha = $store['_fecha_apertura'] ?? null;
                                $isRecent = $fecha && $fecha->gte($threeMonthsAgo);
                                $capTot = (float) str_replace([',', '$', ' '], '', $store['Cap_Tot'] ?? '0');
                                $tel = strtoupper(trim($store['TELEFONIA'] ?? ''));
                                $net = strtoupper(trim($store['INTERNET'] ?? ''));
                            @endphp
                            <tr class="store-row{{ $isRecent ? ' recent' : '' }}">
                                <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">{{ $store['Nombre_Almacen'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $store['Localidad'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-mono text-gray-700">{{ $store['No_Tienda_Actual'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $store['Municipio'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-mono text-gray-700 whitespace-nowrap">
                                    @if($fecha)
                                        {{ $fecha->format('d/m/Y') }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600 whitespace-nowrap">
                                    @if($fecha)
                                        @php
                                            $diffMeses = (int) $fecha->diffInMonths(now());
                                            $diffDias = (int) $fecha->diffInDays(now());
                                            if ($diffMeses >= 12) {
                                                $label = round($diffMeses / 12, 1) . ' años';
                                            } elseif ($diffMeses >= 1) {
                                                $label = $diffMeses . ' meses';
                                            } else {
                                                $label = $diffDias . ' días';
                                            }
                                            $badgeColor = $diffMeses <= 3 ? 'bg-green-100 text-green-800' : ($diffMeses <= 12 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600');
                                        @endphp
                                        <span class="badge {{ $badgeColor }}">
                                            {{ $label }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-gray-700 whitespace-nowrap">
                                    @if($capTot > 0)
                                        ${{ number_format($capTot, 2) }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($tel === 'S')
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Sí</span>
                                    @elseif($tel === 'N')
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">No</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($net === 'S')
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Sí</span>
                                    @elseif($net === 'N')
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">No</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-xl shadow p-6 text-center text-gray-500">
                No hay tiendas para mostrar con los filtros actuales.
            </div>
        @endif
    </div>
@endsection

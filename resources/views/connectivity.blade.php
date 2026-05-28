@extends('layouts.app', ['page-title' => 'Conectividad'])

@section('title', 'Conectividad — Dashboard CDT')

@section('content')
    {{-- KPI Cards --}}
    @if(!empty($kpis))
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            @foreach(['TELEFONIA', 'Señal de celular', 'INTERNET'] as $key)
                @php $k = $kpis[$key] ?? null; @endphp
                @if($k)
                    @php $barYes = $kpis['_total'] > 0 ? round($k['yes'] / $kpis['_total'] * 100) : 0; @endphp
                    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-green-500">
                        <p class="text-sm text-gray-500 uppercase tracking-wide">{{ $k['icon'] }} {{ $k['label'] }}</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $k['yes'] }} <span class="text-sm font-normal text-gray-400">/ {{ $kpis['_total'] }} ({{ $barYes }}%)</span></p>
                        <div class="mt-2 flex gap-4 text-xs">
                            <span class="text-green-600 font-semibold">Sí: {{ $k['yes'] }}</span>
                            <span class="text-red-500 font-semibold">No: {{ $k['no'] }}</span>
                            @if($k['undef'] > 0)
                                <span class="text-gray-400 font-semibold">—: {{ $k['undef'] }}</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Compañía distribution --}}
        @if(!empty($kpis['_compania']))
            <div class="bg-white rounded-xl shadow p-5 mb-6">
                <p class="text-sm text-gray-500 uppercase tracking-wide mb-3">📡 Distribución por Compañía (tiendas con señal)</p>
                <div class="flex flex-wrap gap-6">
                    @foreach($kpis['_compania'] as $comp => $info)
                        <div class="flex-1 min-w-[120px]">
                            <div class="text-lg font-bold text-gray-800">{{ $comp }}</div>
                            <div class="text-2xl font-bold text-blue-600">{{ $info['pct'] }}%</div>
                            <div class="text-xs text-gray-400">{{ $info['count'] }} tiendas</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow p-4 mb-6">
        <form method="GET" action="/conectividad" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs text-gray-500 uppercase mb-1">Almacén</label>
                <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
                       placeholder="Buscar almacén..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs text-gray-500 uppercase mb-1">📞 Teléfono</label>
                <select name="telefono" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                    <option value="">Todos</option>
                    <option value="si" {{ $filters['telefono'] === 'si' ? 'selected' : '' }}>Sí</option>
                    <option value="no" {{ $filters['telefono'] === 'no' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs text-gray-500 uppercase mb-1">📱 Señal Celular</label>
                <select name="senial" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                    <option value="">Todos</option>
                    <option value="si" {{ $filters['senial'] === 'si' ? 'selected' : '' }}>Sí</option>
                    <option value="no" {{ $filters['senial'] === 'no' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs text-gray-500 uppercase mb-1">Compañía</label>
                <select name="compania" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                    <option value="">Todas</option>
                    @foreach($filterOptions['companias'] ?? [] as $comp)
                        <option value="{{ $comp }}" {{ $filters['compania'] === $comp ? 'selected' : '' }}>{{ $comp }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs text-gray-500 uppercase mb-1">🌐 Internet</label>
                <select name="internet" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                    <option value="">Todos</option>
                    <option value="si" {{ $filters['internet'] === 'si' ? 'selected' : '' }}>Sí</option>
                    <option value="no" {{ $filters['internet'] === 'no' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Filtrar
                </button>
                <a href="/conectividad" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition inline-block">
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Almacén</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tienda #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Municipio</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">📞 Teléfono</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">📱 Señal Celular</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Compañía</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">🌐 Internet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($stores as $store)
                        @php
                            $nombre = $store['Nombre_Almacen'] ?? ($store['Nombre_Sucursal'] ?? '—');
                            $tiendaNo = $store['No_Tienda_Actual'] ?? '';
                            $municipio = $store['Municipio'] ?? '—';
                            $tel = strtoupper(trim($store['TELEFONIA'] ?? ''));
                            $cel = strtoupper(trim($store['Señal de celular'] ?? ''));
                            $comp = trim($store['Compañía'] ?? '');
                            $net = strtoupper(trim($store['INTERNET'] ?? ''));
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">{{ $nombre }}</td>
                            <td class="px-4 py-3 text-center text-gray-700 font-mono">{{ $tiendaNo ? number_format((float)$tiendaNo) : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $municipio }}</td>
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
                                @if($cel === 'S')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Sí</span>
                                @elseif($cel === 'N')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">No</span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 whitespace-nowrap">{{ $comp ?: '—' }}</td>
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
            No hay datos para mostrar.
        </div>
    @endif
@endsection

@extends('layouts.app', ['page-title' => 'Auditoría'])

@section('title', 'Auditoría — Dashboard CDT')

@push('head')
<style>
    .store-row { transition: background 0.15s; }
    .store-row:hover { background: #f9fafb; }
    .store-row.risk-rojo { background: #fef2f2; }
    .store-row.risk-rojo:hover { background: #fee2e2; }
    .store-row.risk-amarillo { background: #fffbeb; }
    .store-row.risk-amarillo:hover { background: #fef3c7; }
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
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-red-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">🏛️ Comités vencidos</p>
                <p class="text-2xl font-bold text-red-600">{{ $kpis['comitesVencidos'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-orange-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">🔍 Auditoría > $500k</p>
                <p class="text-2xl font-bold text-orange-600">{{ $kpis['auditoriaAlta'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-amber-500">
                <p class="text-xs text-gray-500 uppercase tracking-wide">📉 Rotación baja (&lt;1.5)</p>
                <p class="text-2xl font-bold text-amber-600">{{ $kpis['rotacionBaja'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow p-4 border-l-4 border-gray-400">
                <p class="text-xs text-gray-500 uppercase tracking-wide">📅 Aud. pendiente (&gt;3 meses)</p>
                <p class="text-2xl font-bold text-gray-600">{{ $kpis['auditoriaPendiente'] }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <form method="GET" action="/auditoria" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Almacén</label>
                    <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
                           placeholder="Buscar..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="min-w-[140px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Nivel de riesgo</label>
                    <select name="nivel"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                        <option value="">Todos</option>
                        <option value="rojo" {{ $filters['nivel'] === 'rojo' ? 'selected' : '' }}>🔴 Crítico</option>
                        <option value="amarillo" {{ $filters['nivel'] === 'amarillo' ? 'selected' : '' }}>🟡 Monitoreo</option>
                        <option value="verde" {{ $filters['nivel'] === 'verde' ? 'selected' : '' }}>🟢 Normal</option>
                    </select>
                </div>
                <div class="min-w-[150px]">
                    <label class="block text-xs text-gray-500 uppercase mb-1">Estado del comité</label>
                    <select name="estado_comite"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                        <option value="">Todos</option>
                        <option value="vigente" {{ $filters['estado_comite'] === 'vigente' ? 'selected' : '' }}>🟢 Vigente</option>
                        <option value="proximo_a_vencer" {{ $filters['estado_comite'] === 'proximo_a_vencer' ? 'selected' : '' }}>🟡 Próximo a vencer</option>
                        <option value="vencido" {{ $filters['estado_comite'] === 'vencido' ? 'selected' : '' }}>🔴 Vencido</option>
                        <option value="sin_fecha" {{ $filters['estado_comite'] === 'sin_fecha' ? 'selected' : '' }}>⚪ Sin fecha</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Filtrar</button>
                    <a href="/auditoria" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition inline-block">Limpiar</a>
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Municipio</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Vigencia</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Comité</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Fch. Audit</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado Aud.</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Imp. Res. Audi.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rotación</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Riesgo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($stores as $store)
                            @php
                                $a = $store['_audit'] ?? [];
                                $level = $a['level'] ?? 'verde';
                                $vigenciaDate = $a['vigencia'] ?? null;
                                $impuesto = $a['impuesto'] ?? 0;
                                $rotacion = $a['rotacion'] ?? 0;
                                $fchAuditDate = $a['fchAudit'] ?? null;
                                $mesesSinAuditoria = $a['mesesSinAuditoria'] ?? null;
                                $estadoComite = $a['estadoComite'] ?? 'sin_fecha';

                                $badgeComite = [
                                    'vigente' => ['bg-green-100 text-green-800', '🟢 Vigente'],
                                    'proximo_a_vencer' => ['bg-yellow-100 text-yellow-800', '🟡 Próximo a vencer'],
                                    'vencido' => ['bg-red-100 text-red-800', '🔴 Vencido'],
                                    'sin_fecha' => ['bg-gray-100 text-gray-500', '⚪ Sin fecha'],
                                ][$estadoComite] ?? ['bg-gray-100 text-gray-500', '⚪ Sin fecha'];

                                $badgeRiesgo = [
                                    'rojo' => ['bg-red-100 text-red-800', '🔴 Crítico'],
                                    'amarillo' => ['bg-yellow-100 text-yellow-800', '🟡 Monitoreo'],
                                    'verde' => ['bg-green-100 text-green-800', '🟢 Normal'],
                                ][$level] ?? ['bg-gray-100 text-gray-500', '—'];

                                $rowRisk = $level === 'rojo' ? 'risk-rojo' : ($level === 'amarillo' ? 'risk-amarillo' : '');
                            @endphp
                            <tr class="store-row {{ $rowRisk }}">
                                <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">{{ $store['Nombre_Almacen'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $store['Localidad'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $store['Municipio'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-mono text-gray-700 whitespace-nowrap">
                                    @if($vigenciaDate)
                                        {{ $vigenciaDate->format('d/m/Y') }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="badge {{ $badgeComite[0] }}">{{ $badgeComite[1] }}</span>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-gray-700 whitespace-nowrap">
                                    @if($fchAuditDate)
                                        {{ $fchAuditDate->format('d/m/Y') }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @php
                                        $auditBadge = ['bg-gray-100 text-gray-500', '⚪ Sin fecha', ''];
                                        if ($fchAuditDate) {
                                            $mesesInt = (int) round($mesesSinAuditoria);
                                            if ($mesesInt >= 12) {
                                                $anos = (int) ($mesesInt / 12);
                                                $resto = $mesesInt % 12;
                                                $labelMeses = $anos . ' año' . ($anos > 1 ? 's' : '');
                                                if ($resto > 0) $labelMeses .= ' ' . $resto . ' mes' . ($resto > 1 ? 'es' : '');
                                            } else {
                                                $labelMeses = $mesesInt . ' mes' . ($mesesInt > 1 ? 'es' : '');
                                            }
                                            if ($mesesSinAuditoria >= 3) {
                                                $auditBadge = ['bg-red-100 text-red-800', '🔴 Vencida', $labelMeses];
                                            } else {
                                                $auditBadge = ['bg-green-100 text-green-800', '🟢 Al día', $labelMeses];
                                            }
                                        }
                                    @endphp
                                    <span class="badge {{ $auditBadge[0] }}">{{ $auditBadge[1] }}</span>
                                    @if($auditBadge[2])
                                        <br><span class="text-xs text-gray-400">{{ $auditBadge[2] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-gray-700 whitespace-nowrap">
                                    @if($impuesto > 0)
                                        ${{ number_format($impuesto, 2) }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-gray-700 whitespace-nowrap">
                                    @if($rotacion > 0)
                                        {{ number_format($rotacion, 2) }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="badge {{ $badgeRiesgo[0] }}">{{ $badgeRiesgo[1] }}</span>
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

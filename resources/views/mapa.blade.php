@extends('layouts.app', ['pageTitle' => 'Mapa de Tiendas'])

@section('title', 'Mapa — Dashboard CDT')

@push('head')
@vite('resources/js/mapa.js')
<style>
 #map { height: 520px; border-radius: 0.75rem; z-index: 0; }
 .leaflet-popup-content { font-size: 0.85rem; line-height: 1.4; }
 .leaflet-popup-content strong { color: #166534; }
    .geo-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; }
    .dark .leaflet-popup-content strong { color: #4ade80; }
</style>
@endpush

@section('content')
 @isset($error)
 <div class="bg-red-100 dark:bg-red-900/50 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6">{{ $error }}</div>
 @endisset

 <div class="page-shell">

  <section class="page-hero">
  <div class="page-hero-content">
  <div>
  <p class="eyebrow">Cobertura territorial</p>
  <h1 class="page-heading">Mapa de tiendas</h1>
  <p class="page-subheading">Ubica tiendas con coordenadas validas y prioriza registros con problemas de georreferencia para mejorar el analisis territorial.</p>
  </div>
  <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-export">Exportar CSV</a>
  </div>
  </section>

 {{-- Stats --}}
 <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-blue-500">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Total</p>
 <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $totalCount }}</p>
 </div>
 @foreach(['OK' => 'border-green-500', 'SIN_COORDENADAS' => 'border-gray-400', 'FUERA_MEXICO' => 'border-red-500', 'FUERA_ESTADO' => 'border-orange-400'] as $status => $border)
 @php $g = $geoLabels[$status] ?? []; @endphp
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 {{ $border }}">
 <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $g['icon'] ?? '' }} {{ $g['label'] ?? $status }}</p>
 <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $stats[$status] ?? 0 }} <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ $totalCount > 0 ? round(($stats[$status] ?? 0) / $totalCount * 100, 1) : 0 }}%)</span></p>
 </div>
 @endforeach
 </div>

 {{-- Filters --}}
 <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
 <form method="GET" action="{{ url('/mapa') }}" class="flex flex-wrap items-end gap-3">
 <div class="flex-1 min-w-[160px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Almacén</label>
 <input type="text" name="almacen" value="{{ $filters['almacen'] }}"
 placeholder="Buscar..."
  class="input-filter">
 </div>
 <div class="min-w-[180px]">
 <label class="block text-xs text-gray-500 dark:text-gray-400 uppercase mb-1">Estado geolocalización</label>
  <select name="estado_geo" class="input-filter">
 <option value="">Todos</option>
 @foreach($geoLabels as $key => $g)
 <option value="{{ $key }}" {{ $filters['estado_geo'] === $key ? 'selected' : '' }}>{{ $g['icon'] }} {{ $g['label'] }}</option>
 @endforeach
 </select>
 </div>
 <div class="flex gap-2">
  <button type="submit" class="btn-filter">Filtrar</button>
  <a href="{{ url('/mapa') }}" class="btn-secondary">Limpiar</a>
  <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-export">⬇ CSV</a>
 </div>
 </form>
 </div>

 {{-- Información de conteo --}}
 <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
 Mostrando <strong>{{ count($stores) }}</strong> tiendas en el mapa
 @if($filteredCount !== $totalCount)
 (filtradas de <strong>{{ $totalCount }}</strong>)
 @endif
 </div>

  {{-- Map --}}
  <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_20rem]">
  <div class="institutional-card p-2">
  <div id="map"></div>
  </div>
  <aside class="priority-panel">
  <p class="eyebrow">Incidencias</p>
  <h2 class="text-lg font-extrabold text-gray-900 dark:text-gray-100">Calidad de coordenadas</h2>
  <div class="mt-4 space-y-3">
  <div class="priority-item">
  <div>
  <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Sin coordenadas</p>
  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Registros que no pueden mostrarse en el mapa.</p>
  </div>
  <span class="status-pill {{ ($stats['SIN_COORDENADAS'] ?? 0) > 0 ? 'status-warning' : 'status-ok' }}">{{ $stats['SIN_COORDENADAS'] ?? 0 }}</span>
  </div>
  <div class="priority-item">
  <div>
  <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Fuera de Mexico</p>
  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Coordenadas fuera del rango geografico esperado.</p>
  </div>
  <span class="status-pill {{ ($stats['FUERA_MEXICO'] ?? 0) > 0 ? 'status-critical' : 'status-ok' }}">{{ $stats['FUERA_MEXICO'] ?? 0 }}</span>
  </div>
  <div class="priority-item">
  <div>
  <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">{{ $geoMismatchLabel ?? ($geoLabels['FUERA_ESTADO']['label'] ?? 'No corresponde al filtro territorial') }}</p>
  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tiendas con posible error de captura territorial.</p>
  </div>
  <span class="status-pill {{ ($stats['FUERA_ESTADO'] ?? 0) > 0 ? 'status-warning' : 'status-ok' }}">{{ $stats['FUERA_ESTADO'] ?? 0 }}</span>
  </div>
  </div>
  </aside>
  </div>

 {{-- CRÍTICAS list --}}
 @if(count($criticales) > 0)
  <div class="table-shell">
 <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
 <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">⚠️ Tiendas sin coordenadas</p>
 <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 font-semibold px-2.5 py-0.5 rounded-full">{{ count($criticales) }}</span>
 </div>
 <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
 <thead class="bg-gray-50 dark:bg-gray-800">
 <tr>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Almacén</th>
 <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tienda #</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Municipio</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
 <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase hidden md:table-cell">Latitud</th>
 <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase hidden md:table-cell">Longitud</th>
 <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Problema</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
 @foreach($criticales as $store)
 @php
 $geo = $store['_geo'] ?? [];
 $gLabel = $geoLabels[$geo['status'] ?? ''] ?? [];
 $badgeClass = $geo['status'] === 'SIN_COORDENADAS' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : ($geo['status'] === 'FUERA_MEXICO' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300');
 @endphp
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
 <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $store['Nombre_Almacen'] ?? '—' }}</td>
 <td class="px-4 py-3 text-center font-mono text-gray-700 dark:text-gray-300">{{ $store['No_Tienda_Actual'] ?? '—' }}</td>
 <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $store['Municipio'] ?? '—' }}</td>
 <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $store['Estado'] ?? '—' }}</td>
 <td class="px-4 py-3 text-center font-mono text-xs text-gray-600 dark:text-gray-300 hidden md:table-cell">{{ $geo['lat'] ?? '—' }}</td>
 <td class="px-4 py-3 text-center font-mono text-xs text-gray-600 dark:text-gray-300 hidden md:table-cell">{{ $geo['lon'] ?? '—' }}</td>
 <td class="px-4 py-3">
 <span class="geo-badge {{ $badgeClass }}">
 {{ $gLabel['icon'] ?? '' }} {{ $geo['mensaje'] ?? $geo['status'] ?? '—' }}
 </span>
 </td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 @elseif(count($stores) > 0)
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 text-center text-gray-500 dark:text-gray-400">
  ✅ Todas las tiendas filtradas tienen coordenadas válidas.
  </div>
  @endif
 </div>
@endsection

@push('footer')
<script>
 document.addEventListener('DOMContentLoaded', function () {
 function initMap() {
 if (typeof L.markerClusterGroup !== 'function') {
 window.addEventListener('markercluster-ready', initMap);
 return;
 }
 var stores = @json($stores);
 var hasMarkers = false;

 var map = L.map('map', { zoomControl: true });

 L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
 attribution: '&copy; OpenStreetMap contributors',
 maxZoom: 18,
 }).addTo(map);

 var clusters = L.markerClusterGroup({
 maxClusterRadius: 50,
 spiderfyOnMaxZoom: true,
 showCoverageOnHover: false,
 zoomToBoundsOnClick: true,
 chunkedLoading: true,
 chunkInterval: 50,
 });

 var bounds = [];

 stores.forEach(function (store) {
 var geo = store._geo || {};
 if (geo.status === 'SIN_COORDENADAS') return;
 var lat = geo.lat;
 var lon = geo.lon;
 if (lat == null || lon == null) return;

 var colorMap = { OK: '#22c55e', FUERA_MEXICO: '#ef4444', FUERA_ESTADO: '#f59e0b' };
 var color = colorMap[geo.status] || '#6b7280';

 var popupHtml =
 '<div style="min-width:200px">' +
 '<strong style="font-size:0.9rem">' + (store.Nombre_Almacen || '—') + '</strong><br>' +
 '<span style="color:#6b7280">#' + (store.No_Tienda_Actual || '—') + '</span><br>' +
 '<span>' + (store.Municipio || '—') + ', ' + (store.Estado || '—') + '</span><br>' +
 '<span style="color:#2563eb;font-size:0.75rem">📍 ' + (store.Nombre_UniOpe || '—') + '</span><br>' +
 '<hr style="margin:6px 0;border-color:#e5e7eb">' +
 '<span>📊 Vta_Mes: <strong>$' + (store.Vta_Mes ? Number(store.Vta_Mes).toLocaleString() : '—') + '</strong></span><br>' +
 '<span>💰 Cap_Tot: <strong>$' + (store.Cap_Tot ? Number(store.Cap_Tot).toLocaleString() : '—') + '</strong></span><br>' +
 '<hr style="margin:6px 0;border-color:#e5e7eb">' +
 '<span style="color:#9ca3af;font-size:0.75rem">📍 ' + lat.toFixed(4) + ', ' + lon.toFixed(4) + '</span>';

 if (geo.status !== 'OK') {
 popupHtml += '<br><span style="color:' + color + ';font-weight:600;font-size:0.75rem">⚠️ ' + (geo.mensaje || '') + '</span>';
 }
 popupHtml += '</div>';

 var marker = L.circleMarker([lat, lon], {
 radius: 8,
 fillColor: color,
 color: '#ffffff',
 weight: 2,
 opacity: 1,
 fillOpacity: 0.8
 });

 marker.bindPopup(popupHtml);
 clusters.addLayer(marker);
 bounds.push([lat, lon]);
 hasMarkers = true;
 });

 if (hasMarkers) {
 map.addLayer(clusters);
 map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
 } else {
 map.setView([23.6, -102.0], 5);
 }

 var legend = L.control({ position: 'bottomright' });
 legend.onAdd = function () {
 var div = L.DomUtil.create('div', '');
 div.style.background = 'white';
 div.style.borderRadius = '0.5rem';
 div.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.1)';
 div.style.padding = '8px 12px';
 div.style.fontSize = '12px';
 div.innerHTML =
 '<div style="font-weight:600;margin-bottom:4px">Leyenda</div>' +
 '<div><span style="color:#22c55e">●</span> Válidas</div>' +
  '<div><span style="color:#f59e0b">●</span> ' + @json($geoMismatchLabel ?? ($geoLabels['FUERA_ESTADO']['label'] ?? 'No corresponde al filtro territorial')) + '</div>' +
 '<div><span style="color:#ef4444">●</span> Fuera de México</div>';
 return div;
 };
 legend.addTo(map);

 setTimeout(function () { map.invalidateSize(); }, 300);
 }
 initMap();
 });
</script>
@endpush


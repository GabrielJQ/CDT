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
    .map-legend { background: #fff; color: #1f2937; border: 1px solid rgba(229, 231, 235, 0.95); border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 8px 12px; font-size: 12px; }
    .dark .map-legend { background: #111827; color: #e5e7eb; border-color: rgba(75, 85, 99, 0.95); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.45); }
    .map-legend-title { font-weight: 700; margin-bottom: 4px; color: #111827; }
    .dark .map-legend-title { color: #f9fafb; }
</style>
@endpush

@section('content')
    <livewire:mapa-content />
@endsection

@push('footer')
<script>
 document.addEventListener('DOMContentLoaded', function () {
 function initMap() {
 if (!window.markerclusterReady || typeof L.markerClusterGroup !== 'function') {
 window.addEventListener('markercluster-ready', initMap, { once: true });
 return;
 }
 var hasMarkers = false;
 var initialLoad = true;
 var fetchTimer = null;
 var latestRequestId = 0;
 var activeRequest = null;
 var suppressNextMoveFetch = false;
 var hasError = false;

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

 var markerBounds = [];

 var LoadingControl = L.Control.extend({
 options: { position: 'topleft' },
 onAdd: function () {
  var div = L.DomUtil.create('div', 'map-legend');
  div.id = 'map-loading';
  div.style.cssText = 'display:none;background:#fff;color:#1f2937;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);z-index:1000;';
  div.innerHTML = '<span class="loading-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #d1d5db;border-top-color:#988256;border-radius:50%;animation:map-spin 0.8s linear infinite;vertical-align:middle;margin-right:8px;"></span> Cargando...';
  return div;
 }
 });
 var loadingCtrl = new LoadingControl().addTo(map);

 var ErrorControl = L.Control.extend({
 options: { position: 'topleft' },
 onAdd: function () {
  var div = L.DomUtil.create('div', 'map-legend');
  div.id = 'map-error';
  div.style.cssText = 'display:none;background:#fef2f2;color:#991b1b;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);border:1px solid #fecaca;z-index:1000;';
  div.innerHTML = '<span style="display:inline-block;margin-right:6px;">⚠️</span> Error al cargar datos. Reintentando...';
  return div;
 }
 });
 var errorCtrl = new ErrorControl().addTo(map);

 function showLoading() {
  var el = document.getElementById('map-loading');
  if (el) el.style.display = 'block';
 }

 function hideLoading() {
  var el = document.getElementById('map-loading');
  if (el) el.style.display = 'none';
 }

 function showError(msg) {
  hasError = true;
  var el = document.getElementById('map-error');
  if (el) {
   el.style.display = 'block';
   if (msg) el.innerHTML = '<span style="display:inline-block;margin-right:6px;">⚠️</span> ' + msg;
  }
 }

 function hideError() {
  hasError = false;
  var el = document.getElementById('map-error');
  if (el) el.style.display = 'none';
 }

 function renderStores(storesToRender) {
 clusters.clearLayers();
 markerBounds = [];
 hasMarkers = false;
 storesToRender.forEach(function (store) {
 var geo = store._geo || {};
 if (geo.status === 'SIN_COORDENADAS') return;
 var lat = geo.lat;
 var lon = geo.lon;
 if (lat == null || lon == null) return;

 var isBienestarFilter = getLivewireFilter('tiendaSalud') === 'salud';
 var isBienestar = isBienestarFilter || ((store._cxc || {}).esTiendaBienestar === true);
 var colorMap = { OK: '#22c55e', FUERA_MEXICO: '#ef4444', FUERA_ESTADO: '#f59e0b' };
 var color = isBienestar ? '#7c3aed' : (colorMap[geo.status] || '#6b7280');

 var popupHtml =
 '<div style="min-width:200px">' +
 '<strong style="font-size:0.9rem">' + (store.Nombre_Almacen || '—') + '</strong><br>' +
 '<span style="color:#6b7280">#' + (store.No_Tienda_Actual || '—') + '</span><br>' +
 '<span>' + (store.Municipio || '—') + ', ' + (store.Estado || '—') + '</span><br>' +
  '<span style="color:#2563eb;font-size:0.75rem">📍 ' + (store.Nombre_UniOpe || '—') + '</span><br>' +
  (isBienestar ? '<span style="display:inline-block;margin-top:4px;color:#7c3aed;font-weight:700;font-size:0.75rem">🟣 Tienda de Salud Bienestar</span><br>' : '') +
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
 markerBounds.push([lat, lon]);
 hasMarkers = true;
 });

 if (hasMarkers) {
 map.addLayer(clusters);
 if (initialLoad) {
 suppressNextMoveFetch = true;
 map.fitBounds(markerBounds, { padding: [30, 30], maxZoom: 14 });
 }
 } else {
 if (initialLoad) map.setView([23.6, -102.0], 5);
 }
 initialLoad = false;
 }

 function getLivewireFilter(key) {
  try {
   if (typeof Livewire === 'undefined') return '';
   var c = Livewire.first();
   return (c && typeof c.get === 'function') ? (c.get(key) || '') : '';
  } catch(e) { return ''; }
 }

 function fetchViewportStores() {
  var requestId = ++latestRequestId;
  if (activeRequest) activeRequest.abort();
  activeRequest = new AbortController();
  hideError();
  showLoading();
  var mapBounds = map.getBounds();
  var dataUrl = new URL(@json(route('mapa.data')), window.location.origin);
  var currentTiendaSalud = getLivewireFilter('tiendaSalud');
  var currentEstadoGeo = getLivewireFilter('estado_geo');
  var currentAlmacen = getLivewireFilter('almacen');
  if (currentAlmacen) dataUrl.searchParams.set('almacen', currentAlmacen);
  if (currentEstadoGeo) dataUrl.searchParams.set('estado_geo', currentEstadoGeo);
  if (currentTiendaSalud) dataUrl.searchParams.set('tienda_salud', currentTiendaSalud);
  dataUrl.searchParams.set('north', mapBounds.getNorth());
  dataUrl.searchParams.set('south', mapBounds.getSouth());
  dataUrl.searchParams.set('east', mapBounds.getEast());
  dataUrl.searchParams.set('west', mapBounds.getWest());

 var timeoutId = setTimeout(function () {
  if (activeRequest && !activeRequest.signal.aborted) {
   activeRequest.abort();
  }
 }, 45000);

 fetch(dataUrl.toString(), { headers: { 'Accept': 'application/json' }, signal: activeRequest.signal })
  .then(function (response) {
   clearTimeout(timeoutId);
   if (!response.ok) throw new Error('Error del servidor (' + response.status + ')');
   return response.json();
  })
  .then(function (payload) {
  if (requestId !== latestRequestId) return;
  hideError();
  renderStores(payload.stores || []);
  })
  .catch(function (error) {
   clearTimeout(timeoutId);
   if (error.name === 'AbortError') return;
   showError('No se pudieron cargar datos del mapa. Verifica tu conexi\u00f3n.');
   })
  .finally(function () {
   if (requestId === latestRequestId) hideLoading();
   });
  }
  window.__fetchViewportStores = fetchViewportStores;

  function scheduleViewportFetch() {
 if (suppressNextMoveFetch) {
 suppressNextMoveFetch = false;
 return;
 }
 clearTimeout(fetchTimer);
 fetchTimer = setTimeout(fetchViewportStores, 250);
 }

 map.setView([23.6, -102.0], 5);
 map.on('moveend zoomend', scheduleViewportFetch);
 fetchViewportStores();

  var legend = L.control({ position: 'bottomright' });
  legend.onAdd = function () {
  var div = L.DomUtil.create('div', 'map-legend');
  div.innerHTML =
   '<div class="map-legend-title">Leyenda</div>' +
   '<div><span style="color:#22c55e">●</span> V\u00e1lidas</div>' +
   '<div><span style="color:#7c3aed">●</span> Tienda de Salud Bienestar CxC</div>' +
  '<div><span style="color:#f59e0b">●</span> ' + @json($geoMismatchLabel ?? ($geoLabels['FUERA_ESTADO']['label'] ?? 'No corresponde al filtro territorial')) + '</div>' +
 '<div><span style="color:#ef4444">●</span> Fuera de M\u00e9xico</div>';
 return div;
 };
 legend.addTo(map);

 setTimeout(function () { map.invalidateSize(); }, 300);
 }
  initMap();

   function setupFilterListener() {
    Livewire.on('mapa-filters-updated', function () {
     if (window.__fetchViewportStores) window.__fetchViewportStores();
    });
   }
   if (typeof Livewire !== 'undefined') {
    setupFilterListener();
   } else {
    document.addEventListener('livewire:init', setupFilterListener);
   }
  });

  (function () {
   var style = document.createElement('style');
   style.textContent = '@keyframes map-spin { to { transform: rotate(360deg); } }';
   document.head.appendChild(style);
  })();
</script>
@endpush


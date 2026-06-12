@extends('layouts.app', ['pageTitle' => 'Mapa CxC'])

@section('title', 'Mapa Casa por Casa — CDT')

@push('head')
@vite('resources/js/mapa.js')
<style>
    #map { height: 520px; border-radius: 0.75rem; z-index: 0; }
    .leaflet-popup-content { font-size: 0.85rem; line-height: 1.4; }
    .leaflet-popup-content strong { color: #166534; }
    .dark .leaflet-popup-content strong { color: #4ade80; }
</style>
@endpush

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Total</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($totalCount) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">📍 Con coordenadas</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($conCoordenadas) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-gray-400">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">❌ Sin coordenadas</p>
            <p class="text-2xl font-bold text-gray-500">{{ number_format($totalCount - $conCoordenadas) }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:p-6 mb-6">
        <div id="map"></div>
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

            stores.forEach(function (s) {
                var lat = parseFloat(s.latitud);
                var lng = parseFloat(s.longitud);
                if (isNaN(lat) || isNaN(lng)) return;

                var color = s.anaqueles_instalados ? '#22c55e' : '#f59e0b';

                var popupHtml =
                    '<div style="min-width:180px">' +
                    '<strong>' + (s.almacen || '—') + '</strong><br>' +
                    '<span style="color:#6b7280">#' + (s.no_tienda || '—') + '</span><br>' +
                    '<span>' + (s.municipio || '—') + ', ' + (s.estado || '—') + '</span><br>' +
                    '<span style="color:#2563eb;font-size:0.75rem">📍 ' + (s.unidad_operativa || '—') + '</span><br>' +
                    '<hr style="margin:6px 0;border-color:#e5e7eb">' +
                    '<span>📦 Anaquel: <strong>' + (s.tipo_anaquel || '—') + '</strong></span><br>' +
                    '<span>' + (s.anaqueles_instalados ? '✅ Instalado' : '⏳ Pendiente') + '</span><br>' +
                    '<hr style="margin:6px 0;border-color:#e5e7eb">' +
                    '<a href="/casa-x-casa/tienda/' + s.id + '" style="color:#2563eb;font-size:0.8rem;">Ver detalle →</a>' +
                    '</div>';

                var marker = L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: color,
                    color: '#ffffff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                });

                marker.bindPopup(popupHtml);
                clusters.addLayer(marker);
                bounds.push([lat, lng]);
                hasMarkers = true;
            });

            if (hasMarkers) {
                map.addLayer(clusters);
                map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
            } else {
                map.setView([23.6, -102.0], 5);
            }

            setTimeout(function () { map.invalidateSize(); }, 300);
        }
        initMap();
    });
</script>
@endpush

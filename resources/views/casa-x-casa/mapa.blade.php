@extends('layouts.app', ['pageTitle' => 'Mapa CxC'])

@section('title', 'Mapa Casa por Casa — CDT')

@push('head')
@vite('resources/js/mapa.js')
<style>
    #map { height: 520px; border-radius: 0.75rem; z-index: 0; }
    .leaflet-popup-content { font-size: 0.85rem; line-height: 1.4; }
    .leaflet-popup-content strong { color: #166534; }
    .dark .leaflet-popup-content strong { color: #4ade80; }
    .map-legend { background: #fff; color: #1f2937; border: 1px solid rgba(229, 231, 235, 0.95); border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 8px 12px; font-size: 12px; }
    .dark .map-legend { background: #111827; color: #e5e7eb; border-color: rgba(75, 85, 99, 0.95); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.45); }
    .map-legend-title { font-weight: 700; margin-bottom: 4px; color: #111827; }
    .dark .map-legend-title { color: #f9fafb; }
</style>
@endpush

@section('content')
    <livewire:casa-x-casa-mapa />
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
            var visibleCount = document.getElementById('visible-count');
            var limitedLabel = document.getElementById('limited-label');

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
            map.addLayer(clusters);

            function esc(value) {
                return window.CdtTables.escapeHtml(value || '—');
            }

            function renderStores(stores, limited) {
                clusters.clearLayers();
                var bounds = [];
                hasMarkers = false;

                stores.forEach(function (store) {
                    var lat = parseFloat(store.latitud);
                    var lng = parseFloat(store.longitud);
                    if (Number.isNaN(lat) || Number.isNaN(lng)) return;

                    var installed = store.anaqueles_instalados === true || store.anaqueles_instalados === 1 || store.anaqueles_instalados === '1';
                    var color = installed ? '#22c55e' : '#f59e0b';

                    var popupHtml =
                        '<div style="min-width:180px">' +
                        '<strong>' + esc(store.almacen) + '</strong><br>' +
                        '<span style="color:#6b7280">#' + esc(store.no_tienda) + '</span><br>' +
                        '<span>' + esc(store.municipio) + ', ' + esc(store.estado) + '</span><br>' +
                        '<span style="color:#2563eb;font-size:0.75rem">📍 ' + esc(store.unidad_operativa) + '</span><br>' +
                        '<hr style="margin:6px 0;border-color:#e5e7eb">' +
                        '<span>📦 Anaquel: <strong>' + esc(store.tipo_anaquel) + '</strong></span><br>' +
                        '<span>' + (installed ? '✅ Instalado' : '⏳ Pendiente') + '</span><br>' +
                        '<hr style="margin:6px 0;border-color:#e5e7eb">' +
                        '<a href="/casa-x-casa/tienda/' + encodeURIComponent(store.id) + '" style="color:#2563eb;font-size:0.8rem;">Ver detalle →</a>' +
                        '</div>';

                    var marker = L.circleMarker([lat, lng], {
                        radius: 8,
                        fillColor: color,
                        color: '#ffffff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8,
                    });

                    marker.bindPopup(popupHtml);
                    clusters.addLayer(marker);
                    bounds.push([lat, lng]);
                    hasMarkers = true;
                });

                visibleCount.textContent = stores.length.toLocaleString('es-MX');
                limitedLabel.classList.toggle('hidden', !limited);

                if (hasMarkers && initialLoad) {
                    suppressNextMoveFetch = true;
                    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
                }
                initialLoad = false;
            }

            function fetchViewportStores() {
                var requestId = ++latestRequestId;
                if (activeRequest) activeRequest.abort();
                activeRequest = new AbortController();
                var mapBounds = map.getBounds();
                var dataUrl = new URL(@json(route('casa-x-casa.mapa.data')), window.location.origin);
                dataUrl.searchParams.set('north', mapBounds.getNorth());
                dataUrl.searchParams.set('south', mapBounds.getSouth());
                dataUrl.searchParams.set('east', mapBounds.getEast());
                dataUrl.searchParams.set('west', mapBounds.getWest());

                fetch(dataUrl.toString(), { headers: { 'Accept': 'application/json' }, signal: activeRequest.signal })
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        if (requestId !== latestRequestId) return;
                        renderStores(payload.stores || [], payload.limited || false);
                    })
                    .catch(function (error) {
                        if (error.name === 'AbortError') return;
                    });
            }

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
                    '<div><span style="color:#22c55e">●</span> Anaquel instalado</div>' +
                    '<div><span style="color:#f59e0b">●</span> Anaquel pendiente</div>';
                return div;
            };
            legend.addTo(map);

            setTimeout(function () { map.invalidateSize(); }, 300);
        }

        initMap();
    });
</script>
@endpush

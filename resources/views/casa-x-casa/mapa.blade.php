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
    <div class="page-shell">
        <section class="page-hero">
            <div class="page-hero-content">
                <div>
                    <p class="eyebrow">Tiendas de Salud</p>
                    <h1 class="page-heading">Mapa Casa por Casa</h1>
                    <p class="page-subheading">Visualiza la cobertura territorial CxC, avance de anaqueles y tiendas con coordenadas disponibles por zona del mapa.</p>
                </div>
                <a href="{{ route('casa-x-casa.directorio') }}" class="btn-secondary">Abrir directorio</a>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-3 mb-6 md:grid-cols-5">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-blue-500">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🏪 Total</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($totalCount) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-green-500">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🟢 Instalados</p>
                <p class="text-2xl font-bold text-green-600">{{ number_format($anaqueles['instalados'] ?? 0) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-amber-500">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">🟠 Pendientes</p>
                <p class="text-2xl font-bold text-amber-600">{{ number_format($anaqueles['pendientes'] ?? 0) }}</p>
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

        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            Mostrando <strong id="visible-count">0</strong> tiendas visibles en la zona actual
            <span id="limited-label" class="hidden text-amber-600 dark:text-amber-300">(límite de carga alcanzado, acerca el zoom para ver más detalle)</span>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_20rem]">
            <div class="institutional-card p-2">
                <div id="map"></div>
            </div>
            <aside class="priority-panel">
                <p class="eyebrow">Avance</p>
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-gray-100">Estado de anaqueles</h2>
                <div class="mt-4 space-y-3">
                    <div class="priority-item">
                        <div>
                            <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Instalados</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Marcadores verdes en el mapa.</p>
                        </div>
                        <span class="status-pill status-ok">{{ number_format($anaqueles['instalados'] ?? 0) }}</span>
                    </div>
                    <div class="priority-item">
                        <div>
                            <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Pendientes</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Marcadores ámbar para seguimiento.</p>
                        </div>
                        <span class="status-pill status-warning">{{ number_format($anaqueles['pendientes'] ?? 0) }}</span>
                    </div>
                    <div class="priority-item">
                        <div>
                            <p class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Sin coordenadas</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No se muestran hasta completar latitud/longitud.</p>
                        </div>
                        <span class="status-pill {{ ($totalCount - $conCoordenadas) > 0 ? 'status-warning' : 'status-ok' }}">{{ number_format($totalCount - $conCoordenadas) }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
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
                var div = L.DomUtil.create('div', '');
                div.style.background = 'white';
                div.style.borderRadius = '0.5rem';
                div.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.1)';
                div.style.padding = '8px 12px';
                div.style.fontSize = '12px';
                div.innerHTML =
                    '<div style="font-weight:600;margin-bottom:4px">Leyenda</div>' +
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

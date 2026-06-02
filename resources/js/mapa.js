import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import L from 'leaflet';

window.L = L;

import('leaflet.markercluster').then(() => {
    window.dispatchEvent(new CustomEvent('markercluster-ready'));
});

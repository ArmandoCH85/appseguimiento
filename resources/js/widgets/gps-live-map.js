import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Fix default icon issue in Leaflet
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

class GpsLiveMap {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.deviceId = options.deviceId;
        this.tenantId = options.tenantId;
        this.map = null;
        this.marker = null;
        this.pathLine = null;
        this.pathCoords = [];
        this.echo = null;

        this.init();
    }

    init() {
        this.map = L.map(this.containerId).setView([-34.6037, -58.3816], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(this.map);

        this.pathLine = L.polyline([], {
            color: '#10b981',
            weight: 4,
            opacity: 0.8,
        }).addTo(this.map);

        this.connectWebSocket();
    }

    connectWebSocket() {
        if (!this.deviceId || !this.tenantId) return;

        this.echo = window.Echo;

        if (!this.echo) {
            console.error('GpsLiveMap: window.Echo no está disponible. Asegurate de que echo.js se cargó primero.');
            return;
        }

        const channelName = `private-gps.tenant.${this.tenantId}.device.${this.deviceId}`;

        this.echo.channel(channelName)
            .listen('.location.update', (data) => {
                this.updateLocation(data);
            });
    }

    updateLocation(data) {
        const lat = parseFloat(data.latitud);
        const lng = parseFloat(data.longitud);

        if (isNaN(lat) || isNaN(lng)) return;

        const latlng = [lat, lng];

        if (!this.marker) {
            this.marker = L.marker(latlng).addTo(this.map);
            this.map.setView(latlng, 15);
        } else {
            this.marker.setLatLng(latlng);
            this.map.panTo(latlng);
        }

        this.pathCoords.push(latlng);
        this.pathLine.setLatLngs(this.pathCoords);
    }

    loadInitialPoints(points) {
        if (!points || points.length === 0) return;

        const coords = points.map(p => [
            parseFloat(p.latitude),
            parseFloat(p.longitude)
        ]);

        this.pathCoords = coords;
        this.pathLine.setLatLngs(coords);

        const lastPoint = coords[coords.length - 1];
        this.marker = L.marker(lastPoint).addTo(this.map);

        const bounds = L.latLngBounds(coords);
        this.map.fitBounds(bounds, { padding: [50, 50] });
    }

    disconnect() {
        if (this.echo && this.deviceId && this.tenantId) {
            const channelName = `private-gps.tenant.${this.tenantId}.device.${this.deviceId}`;
            this.echo.leave(channelName);
        }
    }
}

export default GpsLiveMap;

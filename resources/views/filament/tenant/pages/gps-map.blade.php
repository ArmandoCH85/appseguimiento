<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    {{-- Selector de dispositivo --}}
    <form method="GET" class="mb-3">
        <select
            name="device_id"
            onchange="this.form.submit()"
            class="w-full max-w-sm rounded-lg border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
        >
            <option value="">-- Seleccioná un dispositivo --</option>
            @foreach($devices as $device)
                <option value="{{ $device->id }}" @selected($selectedDeviceId === $device->id)>
                    {{ $device->imei }} — {{ $device->user->name ?? 'Sin usuario' }}
                </option>
            @endforeach
        </select>
    </form>

    @if($selectedDeviceId)
        {{-- Polling cada 30s --}}
        <div wire:poll.30s="refreshPoints"></div>

        {{-- Mapa a pantalla completa --}}
        <div wire:ignore>
            <div
                id="gps-map"
                style="height: calc(100vh - 180px); width: 100%; border-radius: 0.5rem; z-index: 0;"
            ></div>
        </div>

        <script>
        (function () {
            var initialPoints = @json($initialPoints);

            function toCoords(points) {
                return points.map(function (p) {
                    return [parseFloat(p.latitude), parseFloat(p.longitude)];
                });
            }

            function updateMap(points) {
                if (!window.__gpsMap || !points || points.length === 0) return;

                var coords = toCoords(points);
                window.__gpsPath.setLatLngs(coords);

                var last = coords[coords.length - 1];
                if (window.__gpsMarker) {
                    window.__gpsMarker.setLatLng(last);
                } else {
                    window.__gpsMarker = L.marker(last).addTo(window.__gpsMap);
                }

                window.__gpsMap.fitBounds(L.latLngBounds(coords), { padding: [50, 50] });
            }

            function initMap() {
                if (window.__gpsMap) return;

                var map = L.map('gps-map').setView([-34.6037, -58.3816], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(map);

                window.__gpsMap    = map;
                window.__gpsPath   = L.polyline([], { color: '#10b981', weight: 4, opacity: 0.8 }).addTo(map);
                window.__gpsMarker = null;

                updateMap(initialPoints);
            }

            function boot() {
                if (window.L) {
                    initMap();
                    return;
                }
                var s = document.createElement('script');
                s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                s.onload = initMap;
                document.head.appendChild(s);
            }

            window.addEventListener('gps-points-updated', function (e) {
                updateMap(e.detail.points);
            });

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
        </script>
    @else
        <div class="flex items-center justify-center text-gray-500 text-sm" style="height: calc(100vh - 180px);">
            Seleccioná un dispositivo para ver el mapa.
        </div>
    @endif
</x-filament-panels::page>

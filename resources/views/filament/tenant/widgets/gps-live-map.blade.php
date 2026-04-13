<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Mapa GPS</x-slot>
        <x-slot name="description">Últimos 10 puntos. Se actualiza cada 30 segundos.</x-slot>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

        <form method="GET" class="mb-4">
            <select name="device_id" onchange="this.form.submit()"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">-- Seleccioná un dispositivo --</option>
                @foreach($devices as $device)
                    <option value="{{ $device->id }}" @selected($selectedDeviceId === $device->id)>
                        {{ $device->imei }} — {{ $device->user->name ?? 'Sin usuario' }}
                    </option>
                @endforeach
            </select>
        </form>

        @if($selectedDeviceId)
            <div wire:poll.30s="refreshPoints"></div>

            <div wire:ignore>
                <div id="gps-map" style="height: 500px; width: 100%; border-radius: 0.5rem; z-index: 0;"></div>
            </div>

            <script>
            (function () {
                var initialPoints = @json($initialPoints);

                function coordsFromPoints(points) {
                    return points.map(function (p) {
                        return [parseFloat(p.latitude), parseFloat(p.longitude)];
                    });
                }

                function updateMap(points) {
                    if (!window.__gpsMap || !points || points.length === 0) return;

                    var coords = coordsFromPoints(points);
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
                    if (!window.L) return;

                    var map = L.map('gps-map').setView([-34.6037, -58.3816], 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap',
                        maxZoom: 19,
                    }).addTo(map);

                    window.__gpsMap    = map;
                    window.__gpsPath   = L.polyline([], { color: '#10b981', weight: 4, opacity: 0.8 }).addTo(map);
                    window.__gpsMarker = null;

                    updateMap(initialPoints);
                }

                function loadLeafletAndInit() {
                    if (window.L) {
                        initMap();
                        return;
                    }
                    var script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = initMap;
                    document.head.appendChild(script);
                }

                // Actualización desde polling de Livewire
                window.addEventListener('gps-points-updated', function (e) {
                    updateMap(e.detail.points);
                });

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', loadLeafletAndInit);
                } else {
                    loadLeafletAndInit();
                }
            })();
            </script>
        @else
            <div class="text-center text-gray-500 py-8">
                Seleccioná un dispositivo para ver el mapa.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

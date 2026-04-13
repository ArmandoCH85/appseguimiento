<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    {{-- Barra superior --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">

        {{-- Selector de dispositivo --}}
        <div class="flex-1 min-w-48 max-w-xs">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="selectedDeviceId">
                    <option value="">— Seleccioná un dispositivo —</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}">
                            {{ $device->imei }}{{ $device->user ? ' · ' . $device->user->name : '' }}
                        </option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        {{-- Estado --}}
        @if($selectedDeviceId)
            <div class="flex items-center gap-2" wire:poll.30s="refreshPoints">
                <x-filament::badge color="success" icon="heroicon-m-signal">
                    En vivo · cada 30s
                </x-filament::badge>

                @if($lastUpdatedAt)
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        Última actualización: {{ $lastUpdatedAt }}
                    </span>
                @endif

                <div wire:loading wire:target="refreshPoints, updatedSelectedDeviceId">
                    <x-filament::loading-indicator class="h-4 w-4 text-primary-500" />
                </div>
            </div>
        @else
            <x-filament::badge color="gray" icon="heroicon-m-signal-slash">
                Sin dispositivo
            </x-filament::badge>
        @endif

    </div>

    {{-- Mapa --}}
    <div
        class="overflow-hidden rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10"
        wire:ignore
    >
        <div
            id="gps-map"
            style="height: calc(100vh - 210px); width: 100%; z-index: 0;"
        ></div>
    </div>

    <script>
    (function () {
        var initialPoints   = @json($initialPoints);
        var deviceName      = @json($selectedDeviceName);

        function toCoords(points) {
            return points
                .map(function (p) { return [parseFloat(p.latitude), parseFloat(p.longitude)]; })
                .filter(function (c) { return !isNaN(c[0]) && !isNaN(c[1]); });
        }

        function phoneIcon() {
            return L.divIcon({
                className: '',
                html: [
                    '<div style="',
                    '  background:#10b981;',
                    '  border-radius:50%;',
                    '  width:36px;height:36px;',
                    '  display:flex;align-items:center;justify-content:center;',
                    '  border:2px solid #fff;',
                    '  box-shadow:0 2px 8px rgba(0,0,0,0.35);',
                    '  font-size:18px;',
                    '">📱</div>',
                ].join(''),
                iconSize:    [36, 36],
                iconAnchor:  [18, 18],
                popupAnchor: [0, -22],
            });
        }

        function popupContent() {
            var now = new Date().toLocaleString('es-AR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
            });
            return '<div style="font-family:sans-serif;min-width:160px;">' +
                   '  <div style="font-weight:600;margin-bottom:4px;">📱 ' + deviceName + '</div>' +
                   '  <div style="font-size:12px;color:#6b7280;">🕐 ' + now + '</div>' +
                   '</div>';
        }

        function updateMap(points) {
            if (!window.__gpsMap) return;

            var coords = toCoords(points);

            if (coords.length === 0) {
                window.__gpsPath.setLatLngs([]);
                return;
            }

            window.__gpsPath.setLatLngs(coords);

            var last = coords[coords.length - 1];
            if (window.__gpsMarker) {
                window.__gpsMarker.setLatLng(last);
                window.__gpsMarker.setPopupContent(popupContent());
            } else {
                window.__gpsMarker = L.marker(last, { icon: phoneIcon() })
                    .bindPopup(popupContent())
                    .addTo(window.__gpsMap);
            }

            window.__gpsMap.fitBounds(L.latLngBounds(coords), { padding: [60, 60] });
        }

        function initMap() {
            if (window.__gpsMap) return;

            var map = L.map('gps-map', { zoomControl: true }).setView([-34.6037, -58.3816], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);

            window.__gpsMap    = map;
            window.__gpsPath   = L.polyline([], { color: '#10b981', weight: 4, opacity: 0.85 }).addTo(map);
            window.__gpsMarker = null;

            if (initialPoints.length > 0) {
                updateMap(initialPoints);
            }
        }

        function boot() {
            if (window.L) { initMap(); return; }
            var s = document.createElement('script');
            s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            s.onload = initMap;
            document.head.appendChild(s);
        }

        window.addEventListener('gps-points-updated', function (e) {
            if (e.detail.deviceName) deviceName = e.detail.deviceName;
            updateMap(e.detail.points);
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    })();
    </script>

</x-filament-panels::page>

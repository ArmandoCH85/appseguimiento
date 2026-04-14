<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @if($selectedDeviceId)
        <div wire:poll.30s="refreshPoints"></div>
    @endif

    <style>
        .gps-map-card {
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid rgba(229, 231, 235, 0.8);
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            position: relative;
        }

        .gps-map-legend {
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
            backdrop-filter: blur(10px);
        }

        .gps-map-legend-dot {
            display: inline-block;
            width: 0.65rem;
            height: 0.65rem;
            border-radius: 9999px;
        }

        .gps-map-empty-overlay {
            position: absolute;
            inset: 0;
            z-index: 450;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.72), rgba(255, 255, 255, 0.64));
            backdrop-filter: blur(2px);
        }

        .gps-map-empty-panel {
            margin: 0 1.5rem;
            max-width: 28rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.72);
            background: rgba(255, 255, 255, 0.96);
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
        }

        .gps-map-marker {
            position: relative;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gps-map-marker__icon {
            width: 32px;
            height: 32px;
            background: #10b981;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            border: 2px solid #ffffff;
        }

        .gps-map-marker__icon svg {
            width: 20px;
            height: 20px;
            color: #ffffff;
        }

        .gps-map-marker__pulse {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.25);
            animation: gps-map-pulse 2s ease-out infinite;
        }

        .gps-map-tooltip {
            border-radius: 0.75rem !important;
            border: 1px solid rgba(229, 231, 235, 0.8) !important;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.15) !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        .gps-map-tooltip .leaflet-tooltip-content {
            padding: 0.75rem 1rem !important;
        }

        .gps-map-start-marker {
            width: 12px;
            height: 12px;
            border-radius: 9999px;
            background: #0f172a;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2);
        }

        @keyframes gps-map-pulse {
            0% {
                transform: scale(0.9);
                opacity: 0.85;
            }

            100% {
                transform: scale(1.8);
                opacity: 0;
            }
        }

        #gps-map {
            height: 78dvh;
            min-height: 36rem;
            width: 100%;
            z-index: 0;
        }

        .dark .gps-map-card {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.28);
        }

        .dark .gps-map-legend {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(3, 7, 18, 0.86);
        }

        .dark .gps-map-tooltip {
            border-color: rgba(255, 255, 255, 0.15) !important;
            background: rgba(17, 24, 39, 0.98) !important;
        }

        .dark .gps-map-empty-overlay {
            background: linear-gradient(135deg, rgba(3, 7, 18, 0.82), rgba(3, 7, 18, 0.7), rgba(3, 7, 18, 0.62));
        }

        .dark .gps-map-empty-panel {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(17, 24, 39, 0.96);
        }

        #gps-map .leaflet-control-zoom {
            border: 0;
            overflow: hidden;
            border-radius: 0.9rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        }

        #gps-map .leaflet-control-zoom a {
            width: 2.5rem;
            height: 2.5rem;
            line-height: 2.5rem;
            border: 0;
        }

        .gps-map-update-card {
            transition: all 200ms ease;
        }

        .gps-map-update-card:hover {
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.12);
            transform: translateY(-1px);
        }

        .gps-map-clock-pulse {
            animation: gps-map-clock-pulse 2s ease-in-out infinite;
        }

        @keyframes gps-map-clock-pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.6;
            }
        }
    </style>

    <div class="space-y-4">
        {{-- HEADER: Solo selector y estado --}}
        <x-filament::section>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0 flex-1">
                    <label for="gps-device-select" class="text-sm font-medium text-gray-950 dark:text-white">
                        Dispositivo
                    </label>

                    <div class="mt-2 max-w-xl pb-4 lg:pb-0">
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="gps-device-select" wire:model.live="selectedDeviceId">
                                <option value="">— Selecciona un dispositivo —</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}">
                                        {{ $device->imei }}{{ $device->user ? ' · ' . $device->user->name : '' }}
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>

                <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap text-[10px] sm:text-xs">
                    @if($selectedDeviceId)
                        <x-filament::badge color="success" icon="heroicon-m-signal" size="sm" class="shrink-0">
                            En vivo
                        </x-filament::badge>
                        <x-filament::badge color="gray" icon="heroicon-m-clock" size="sm" class="shrink-0">
                            Panel: <span class="font-mono tabular-nums">{{ $lastUpdatedAt }}</span>
                        </x-filament::badge>
                        @if($deviceGpsTime)
                            <x-filament::badge color="info" icon="heroicon-m-device-phone-mobile" size="sm" class="shrink-0">
                                GPS: <span class="font-mono tabular-nums">{{ $deviceGpsTime }}</span>
                            </x-filament::badge>
                        @endif
                    @else
                        <x-filament::badge color="gray" icon="heroicon-m-signal-slash" size="sm" class="shrink-0">
                            Sin dispositivo
                        </x-filament::badge>
                    @endif

                    <div wire:loading wire:target="refreshPoints,updatedSelectedDeviceId" class="shrink-0 ml-1">
                        <x-filament::loading-indicator class="h-4 w-4 text-primary-500" />
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- MAPA COMO PROTAGONISTA --}}
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
            {{-- Columna principal: Mapa (9 columnas) --}}
            <div class="xl:col-span-9">
                <x-filament::section class="relative">
            

                    {{-- Badges informativos sobre el mapa --}}
                    @if($selectedDevice && $pointsCount > 0)
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <x-filament::badge color="success" icon="heroicon-m-map-pin">
                                Puntos: {{ $pointsCount }}
                            </x-filament::badge>

                            @if($latestPoint)
                                <x-filament::badge color="info" icon="heroicon-m-map-pin">
                                    {{ $latestPoint['accuracy_human'] }}
                                </x-filament::badge>
                            @endif
                        </div>
                    @endif

                    <div class="gps-map-card relative">
                        <div wire:ignore>
                            <div id="gps-map"></div>
                        </div>

                        @if(! $selectedDevice)
                            <div class="gps-map-empty-overlay">
                                <div class="gps-map-empty-panel">
                                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        <x-filament::icon icon="heroicon-o-map" class="h-8 w-8" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                                        Seleccioná un dispositivo
                                    </h3>
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        Elegí un dispositivo del selector para comenzar el monitoreo en vivo.
                                    </p>
                                </div>
                            </div>
                        @elseif($pointsCount === 0)
                            <div class="gps-map-empty-overlay">
                                <div class="gps-map-empty-panel">
                                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                                        <x-filament::icon icon="heroicon-o-map-pin" class="h-8 w-8" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                                        Sin puntos GPS recientes
                                    </h3>
                                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        El dispositivo está seleccionado, pero todavía no hay puntos para mostrar.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            </div>

            {{-- Panel lateral: Solo información esencial (3 columnas) --}}
            <div class="xl:col-span-3">
                <div class="space-y-4">
                    @if($latestPoint)
                        <x-filament::section>
                            <x-slot name="heading" class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-map-pin" class="h-4 w-4" />
                                Último punto
                            </x-slot>

                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Coordenadas</dt>
                                    <dd class="mt-1 font-mono text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ number_format($latestPoint['latitude'], 6) }}, {{ number_format($latestPoint['longitude'], 6) }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Hora GPS</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $latestPoint['time_human'] }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Precisión</dt>
                                    <dd class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $latestPoint['accuracy_human'] }}</dd>
                                </div>
                            </dl>
                        </x-filament::section>
                    @endif

            
                </div>
            </div>
        </div>
    </div>

    <script>
        window.__gpsMapPageState = {
            points: @json($initialPoints),
            deviceName: @json($selectedDeviceName),
            deviceId: @json($selectedDevice['id'] ?? null),
            deviceGpsTime: @json($deviceGpsTime),
        };

        (function () {
            const defaultCenter = [-12.046374, -77.042793];

            function getState() {
                return window.__gpsMapPageState || { points: [], deviceName: '', deviceId: null, deviceGpsTime: null };
            }

            function toCoords(points) {
                return (points || [])
                    .map((point) => [parseFloat(point.latitude), parseFloat(point.longitude)])
                    .filter((coords) => !Number.isNaN(coords[0]) && !Number.isNaN(coords[1]));
            }

            function phoneMarkerIcon() {
                const phoneSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                    <path d="M10.5 1.5H8.25C7.007 1.5 6 2.507 6 3.75v16.5c0 1.243 1.007 2.25 2.25 2.25h7.5c1.243 0 2.25-1.007 2.25-2.25V3.75c0-1.243-1.007-2.25-2.25-2.25H13.5m-6 0V3h9V1.5m-9 0h9m-3.75 4.5v3m-3 0h6"/>
                </svg>`;

                return L.divIcon({
                    className: '',
                    html: `<span class="gps-map-marker"><span class="gps-map-marker__pulse"></span><span class="gps-map-marker__icon">${phoneSvg}</span></span>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20],
                    tooltipAnchor: [0, -25],
                });
            }

            function formatGpsTime(gpsTime) {
                if (!gpsTime) return 'Sin datos';
                return gpsTime;
            }

            function createTooltipContent(deviceName, deviceGpsTime) {
                const now = new Date();
                const options = {
                    timeZone: 'America/Lima',
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                };

                const limaTime = now.toLocaleString('es-PE', options);
                const [datePart, timePart] = limaTime.split(', ');

                return `
                    <div class="text-sm">
                        <div class="font-semibold text-gray-900 dark:text-white mb-2">
                            ${deviceName || 'Dispositivo GPS'}
                        </div>
                        ${deviceGpsTime ? `
                        <div class="text-xs text-gray-600 dark:text-gray-300">
                            <div class="flex justify-between gap-4">
                                <span class="font-medium">Último GPS:</span>
                                <span class="font-mono font-semibold">${formatGpsTime(deviceGpsTime)}</span>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
            }

            function ensureMap() {
                if (window.__gpsMap) {
                    return window.__gpsMap;
                }

                const map = L.map('gps-map', { zoomControl: true }).setView(defaultCenter, 12);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(map);

                window.__gpsMap = map;
                window.__gpsPath = L.polyline([], {
                    color: '#10b981',
                    weight: 4,
                    opacity: 0.9,
                    lineCap: 'round',
                    lineJoin: 'round',
                }).addTo(map);
                window.__gpsMarker = null;
                window.__gpsStartMarker = null;

                return map;
            }

            function removeMarker(markerKey) {
                if (window[markerKey]) {
                    window.__gpsMap.removeLayer(window[markerKey]);
                    window[markerKey] = null;
                }
            }

            function shouldRecenter(map, lastPoint, forceFit) {
                if (forceFit) {
                    return true;
                }

                const bounds = map.getBounds();

                if (! bounds.isValid()) {
                    return true;
                }

                return ! bounds.pad(-0.2).contains(lastPoint);
            }

            function updateMap(points, options = {}) {
                const map = ensureMap();
                const coords = toCoords(points);

                if (! coords.length) {
                    window.__gpsPath.setLatLngs([]);
                    removeMarker('__gpsMarker');
                    removeMarker('__gpsStartMarker');
                    map.setView(defaultCenter, 12);
                    return;
                }

                window.__gpsPath.setLatLngs(coords);

                const firstPoint = coords[0];
                const lastPoint = coords[coords.length - 1];

                if (window.__gpsStartMarker) {
                    window.__gpsStartMarker.setLatLng(firstPoint);
                } else {
                    window.__gpsStartMarker = L.marker(firstPoint, {
                        icon: L.divIcon({
                            className: '',
                            html: '<span class="gps-map-start-marker"></span>',
                            iconSize: [12, 12],
                            iconAnchor: [6, 6],
                        }),
                    }).addTo(map);
                }

                if (window.__gpsMarker) {
                    window.__gpsMarker.setLatLng(lastPoint);
                } else {
                    window.__gpsMarker = L.marker(lastPoint, {
                        icon: phoneMarkerIcon(),
                    }).addTo(map);
                }

                const state = getState();
                const tooltipContent = createTooltipContent(state.deviceName, state.deviceGpsTime);
                window.__gpsMarker.unbindTooltip();
                window.__gpsMarker.bindTooltip(tooltipContent, {
                    direction: 'top',
                    offset: [0, -25],
                    className: 'gps-map-tooltip',
                    sticky: false,
                });

                if (shouldRecenter(map, L.latLng(lastPoint[0], lastPoint[1]), !!options.forceFit)) {
                    if (coords.length === 1) {
                        map.setView(lastPoint, 16);
                    } else {
                        map.fitBounds(L.latLngBounds(coords), {
                            padding: [60, 60],
                            maxZoom: 16,
                        });
                    }
                }
            }

            function bootMap() {
                if (window.L) {
                    ensureMap();
                    updateMap(getState().points, { forceFit: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = function () {
                    ensureMap();
                    updateMap(getState().points, { forceFit: true });
                };

                document.head.appendChild(script);
            }

            if (! window.__gpsMapPageBooted) {
                window.__gpsMapPageBooted = true;

                window.addEventListener('gps-points-updated', function (event) {
                    const detail = event.detail || {};

                    window.__gpsMapPageState = {
                        points: detail.points || [],
                        deviceName: detail.deviceName || '',
                        deviceId: detail.deviceId || null,
                        deviceGpsTime: detail.deviceGpsTime || null,
                    };

                    updateMap(window.__gpsMapPageState.points, {
                        forceFit: !!detail.shouldFit,
                    });
                });

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bootMap, { once: true });
                } else {
                    bootMap();
                }
            } else {
                setTimeout(function () {
                    if (window.L) {
                        updateMap(getState().points, { forceFit: true });
                    }
                }, 0);
            }
        })();
    </script>
</x-filament-panels::page>

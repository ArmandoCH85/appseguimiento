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

        .gps-map-meta-label {
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .gps-map-meta-value {
            margin-top: 0.25rem;
            color: #111827;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .gps-map-meta-muted {
            margin-top: 0.25rem;
            color: #4b5563;
            font-size: 0.95rem;
        }

        .gps-map-timeline-item {
            padding: 0.75rem;
            border-radius: 0.85rem;
            border: 1px solid rgba(229, 231, 235, 0.8);
            background: rgba(255, 255, 255, 0.85);
            transition: all 180ms ease;
        }

        .gps-map-timeline-item--latest {
            border-color: rgba(52, 211, 153, 0.45);
            background: rgba(236, 253, 245, 0.95);
            box-shadow: inset 0 0 0 1px rgba(16, 185, 129, 0.18);
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
            width: 28px;
            height: 28px;
            display: block;
        }

        .gps-map-marker__pulse,
        .gps-map-marker__core {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
        }

        .gps-map-marker__pulse {
            background: rgba(16, 185, 129, 0.18);
            border: 1px solid rgba(16, 185, 129, 0.28);
            animation: gps-map-pulse 1.8s ease-out infinite;
        }

        .gps-map-marker__core {
            inset: 5px;
            background: #10b981;
            border: 3px solid #ffffff;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
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
            height: 72dvh;
            min-height: 32rem;
            width: 100%;
            z-index: 0;
        }

        .dark .gps-map-card {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.28);
        }

        .dark .gps-map-meta-label {
            color: #9ca3af;
        }

        .dark .gps-map-meta-value {
            color: #f9fafb;
        }

        .dark .gps-map-meta-muted {
            color: #d1d5db;
        }

        .dark .gps-map-timeline-item {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.04);
        }

        .dark .gps-map-timeline-item--latest {
            border-color: rgba(16, 185, 129, 0.45);
            background: rgba(16, 185, 129, 0.1);
            box-shadow: inset 0 0 0 1px rgba(16, 185, 129, 0.24);
        }

        .dark .gps-map-legend {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(3, 7, 18, 0.86);
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
    </style>

    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0 flex-1">
                    <label for="gps-device-select" class="text-sm font-medium text-gray-950 dark:text-white">
                        Dispositivo
                    </label>

                    <div class="mt-2 max-w-xl">
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="gps-device-select" wire:model.live="selectedDeviceId">
                                <option value="">— Seleccioná un dispositivo —</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}">
                                        {{ $device->imei }}{{ $device->user ? ' · ' . $device->user->name : '' }}
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if($selectedDeviceId)
                        <x-filament::badge color="success" icon="heroicon-m-signal">
                            En vivo
                        </x-filament::badge>

                        <div class="rounded-xl border border-gray-200/80 bg-white px-3 py-2 text-sm text-gray-600 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-300">
                            Última actualización del panel:
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $lastUpdatedAt }}</span>
                        </div>
                    @else
                        <x-filament::badge color="gray" icon="heroicon-m-signal-slash">
                            Sin dispositivo
                        </x-filament::badge>
                    @endif

                    <div wire:loading wire:target="refreshPoints,updatedSelectedDeviceId">
                        <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
                    </div>
                </div>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="xl:col-span-8">
                <x-filament::section>
                    <x-slot name="heading">Seguimiento en vivo</x-slot>
                    <x-slot name="description">
                        Últimos 10 puntos recibidos para el dispositivo seleccionado.
                    </x-slot>

                    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-gray-200/80 bg-gray-50/90 p-4 dark:border-white/10 dark:bg-white/[0.03] md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <p class="gps-map-meta-label">Dispositivo actual</p>
                            <p class="mt-1 truncate text-base font-semibold text-gray-950 dark:text-white">
                                {{ $selectedDevice['imei'] ?? 'Sin dispositivo seleccionado' }}
                            </p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                {{ $selectedDevice['user_name'] ?? 'Seleccioná un dispositivo para comenzar el monitoreo en vivo.' }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <x-filament::badge color="{{ $pointsCount > 0 ? 'success' : 'gray' }}">
                                Puntos visibles: {{ $pointsCount }}
                            </x-filament::badge>

                            @if($latestPoint)
                                <x-filament::badge color="info">
                                    Precisión actual: {{ $latestPoint['accuracy_human'] }}
                                </x-filament::badge>
                            @endif
                        </div>
                    </div>

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
                                        Seleccioná un dispositivo para comenzar el monitoreo en vivo.
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
                                        El dispositivo está seleccionado, pero todavía no recibió puntos para mostrar en el mapa.
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($selectedDevice && $pointsCount > 0)
                            <div class="gps-map-legend absolute left-4 top-4 z-[500]">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                                    Referencia
                                </p>
                                <div class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                    <div class="flex items-center gap-2">
                                        <span class="gps-map-legend-dot bg-slate-900 dark:bg-slate-100"></span>
                                        <span>Inicio del tramo</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="gps-map-legend-dot bg-emerald-500"></span>
                                        <span>Recorrido reciente</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="gps-map-legend-dot bg-emerald-500 ring-4 ring-emerald-500/20"></span>
                                        <span>Posición actual</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            </div>

            <div class="xl:col-span-4">
                <div class="space-y-6">
                    <x-filament::section>
                        <x-slot name="heading">Dispositivo actual</x-slot>
                        <x-slot name="description">Contexto operativo del equipo seleccionado.</x-slot>

                        <dl class="space-y-4">
                            <div>
                                <dt class="gps-map-meta-label">IMEI</dt>
                                <dd class="gps-map-meta-value">{{ $selectedDevice['imei'] ?? 'Sin selección' }}</dd>
                            </div>

                            <div>
                                <dt class="gps-map-meta-label">Usuario asignado</dt>
                                <dd class="gps-map-meta-value">{{ $selectedDevice['user_name'] ?? 'Sin usuario asignado' }}</dd>
                                @if($selectedDevice['user_email'] ?? null)
                                    <p class="gps-map-meta-muted">{{ $selectedDevice['user_email'] }}</p>
                                @endif
                            </div>

                            <div>
                                <dt class="gps-map-meta-label">Puntos visibles</dt>
                                <dd class="gps-map-meta-value">{{ $pointsCount }}</dd>
                            </div>
                        </dl>
                    </x-filament::section>

                    <x-filament::section>
                        <x-slot name="heading">Último punto</x-slot>
                        <x-slot name="description">Ubicación y precisión más recientes.</x-slot>

                        @if($latestPoint)
                            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="gps-map-meta-label">Latitud</dt>
                                    <dd class="gps-map-meta-value">{{ number_format($latestPoint['latitude'], 6) }}</dd>
                                </div>

                                <div>
                                    <dt class="gps-map-meta-label">Longitud</dt>
                                    <dd class="gps-map-meta-value">{{ number_format($latestPoint['longitude'], 6) }}</dd>
                                </div>

                                <div>
                                    <dt class="gps-map-meta-label">Hora GPS</dt>
                                    <dd class="gps-map-meta-value">{{ $latestPoint['time_human'] }}</dd>
                                </div>

                                <div>
                                    <dt class="gps-map-meta-label">Precisión</dt>
                                    <dd class="gps-map-meta-value">{{ $latestPoint['accuracy_human'] }}</dd>
                                </div>
                            </dl>
                        @else
                            <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                                Todavía no hay un punto GPS disponible para mostrar detalle.
                            </p>
                        @endif
                    </x-filament::section>

                    <x-filament::section>
                        <x-slot name="heading">Actividad reciente</x-slot>
                        <x-slot name="description">Secuencia de los últimos puntos cargados.</x-slot>

                        <div class="space-y-3">
                            @forelse($recentPoints as $point)
                                <div @class([
                                    'gps-map-timeline-item',
                                    'gps-map-timeline-item--latest' => $point['is_latest'],
                                ])>
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                                {{ $point['is_latest'] ? 'Posición actual' : 'Punto registrado' }}
                                            </p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $point['time_human'] }}
                                            </p>
                                        </div>

                                        <x-filament::badge color="{{ $point['is_latest'] ? 'success' : 'gray' }}">
                                            {{ $point['accuracy_human'] }}
                                        </x-filament::badge>
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm text-gray-600 dark:text-gray-300">
                                        <div>
                                            <p class="gps-map-meta-label">Latitud</p>
                                            <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ number_format($point['latitude'], 6) }}</p>
                                        </div>
                                        <div>
                                            <p class="gps-map-meta-label">Longitud</p>
                                            <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ number_format($point['longitude'], 6) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    Cuando lleguen puntos GPS recientes, vas a ver el historial corto en esta columna.
                                </p>
                            @endforelse
                        </div>
                    </x-filament::section>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.__gpsMapPageState = {
            points: @json($initialPoints),
            deviceName: @json($selectedDeviceName),
            deviceId: @json($selectedDevice['id'] ?? null),
        };

        (function () {
            const defaultCenter = [-12.046374, -77.042793];

            function getState() {
                return window.__gpsMapPageState || { points: [], deviceName: '', deviceId: null };
            }

            function toCoords(points) {
                return (points || [])
                    .map((point) => [parseFloat(point.latitude), parseFloat(point.longitude)])
                    .filter((coords) => !Number.isNaN(coords[0]) && !Number.isNaN(coords[1]));
            }

            function currentMarkerIcon() {
                return L.divIcon({
                    className: '',
                    html: '<span class="gps-map-marker"><span class="gps-map-marker__pulse"></span><span class="gps-map-marker__core"></span></span>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });
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
                        icon: currentMarkerIcon(),
                    }).addTo(map);
                }

                if (getState().deviceName) {
                    window.__gpsMarker.bindTooltip(getState().deviceName, {
                        direction: 'top',
                        offset: [0, -18],
                    });
                }

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

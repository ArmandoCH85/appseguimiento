<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        .gps-report-card {
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid rgba(229, 231, 235, 0.8);
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            position: relative;
        }

        .gps-report-marker {
            position: relative;
            width: 40px;
            height: 40px;
            display: block;
        }

        .gps-report-marker__pulse,
        .gps-report-marker__core {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
        }

        .gps-report-marker__pulse {
            background: rgba(16, 185, 129, 0.18);
            border: 1px solid rgba(16, 185, 129, 0.28);
            animation: gps-report-pulse 1.8s ease-out infinite;
        }

        .gps-report-marker__icon {
            inset: 5px;
            background: #10b981;
            border: 3px solid #ffffff;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.35);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gps-report-start-marker {
            width: 12px;
            height: 12px;
            border-radius: 9999px;
            background: #0f172a;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2);
        }

        @keyframes gps-report-pulse {
            0% { transform: scale(0.9); opacity: 0.85; }
            100% { transform: scale(1.8); opacity: 0; }
        }

        #gps-report-map {
            height: 78dvh;
            min-height: 32rem;
            width: 100%;
            z-index: 0;
        }

        .dark .gps-report-card {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.28);
        }

        .dark .gps-report-legend {
            border-color: rgba(255, 255, 255, 0.1);
            background: #1f2937;
        }

        .dark .gps-report-empty-overlay {
            background: linear-gradient(135deg, rgba(3, 7, 18, 0.82), rgba(3, 7, 18, 0.7), rgba(3, 7, 18, 0.62));
        }

        .dark .gps-report-empty-panel {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(17, 24, 39, 0.96);
        }

        #gps-report-map .leaflet-control-zoom {
            border: 0;
            overflow: hidden;
            border-radius: 0.9rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        }

        #gps-report-map .leaflet-control-zoom a {
            width: 2.5rem;
            height: 2.5rem;
            line-height: 2.5rem;
            border: 0;
        }

        .gps-report-empty-overlay {
            position: absolute;
            inset: 0;
            z-index: 450;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.72), rgba(255, 255, 255, 0.64));
            backdrop-filter: blur(2px);
        }

        .gps-report-empty-panel {
            margin: 0 1.5rem;
            max-width: 28rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.72);
            background: rgba(255, 255, 255, 0.96);
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
        }

        .gps-report-legend {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(229, 231, 235, 0.8);
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .gps-report-legend-dot {
            display: inline-block;
            width: 0.65rem;
            height: 0.65rem;
            border-radius: 9999px;
        }
    </style>

    <div class="space-y-4">
        {{ $this->form }}

        {{-- MAPA — siempre presente, con overlay si no hay datos --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-map-pin" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    <span>Mapa de Recorrido</span>
                </div>
            </x-slot>

<div class="gps-report-card relative">
                @if($reportGenerated && !empty($reportPoints))
                    <div class="absolute left-3 top-3 z-[500] flex items-center gap-2 rounded-lg border border-gray-200/80 bg-white px-2.5 py-1.5 shadow dark:border-white/10 dark:bg-gray-900">
                        <x-filament::badge color="success" size="sm">
                            <x-filament::icon icon="heroicon-s-map-pin" class="h-3.5 w-3.5" />
                            {{ $pointsCount }}
                        </x-filament::badge>
                        <x-filament::badge color="info" size="sm">
                            <x-filament::icon icon="heroicon-s-arrows-right-left" class="h-3.5 w-3.5" />
                            {{ $distanceFormatted }}
                        </x-filament::badge>
                        <x-filament::badge color="warning" size="sm">
                            <x-filament::icon icon="heroicon-s-clock" class="h-3.5 w-3.5" />
                            {{ $durationFormatted }}
                        </x-filament::badge>
                        @php
                            $formData = $this->form->getState();
                            $dateFilter = $formData['dateFilter'] ?? 'today';
                            $startDate = $formData['startDate'] ?? '';
                            $endDate = $formData['endDate'] ?? '';
                        @endphp
                        <x-filament::badge color="gray" size="sm">
                            <x-filament::icon icon="heroicon-s-calendar-days" class="h-3.5 w-3.5" />
                            @if($dateFilter === 'today') Hoy
                            @elseif($dateFilter === 'yesterday') Ayer
                            @else {{ $startDate }} → {{ $endDate }}
                            @endif
                        </x-filament::badge>
                    </div>
                @endif

                <div wire:ignore>
                    <div id="gps-report-map"></div>
                </div>

                @if(!$reportGenerated || empty($reportPoints))
                    <div class="gps-report-empty-overlay">
                        <div class="gps-report-empty-panel">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                                <x-filament::icon icon="heroicon-o-map-pin" class="h-8 w-8" />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                                @if(!$reportGenerated)
                                    Seleccioná un dispositivo y generá el reporte
                                @else
                                    Sin datos de recorrido
                                @endif
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                @if(!$reportGenerated)
                                    Elegí un dispositivo y hacé clic en "Generar Reporte" para ver el recorrido en el mapa.
                                @else
                                    No se encontraron puntos GPS para el período seleccionado.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                @if(!empty($reportPoints))
                    <div class="gps-report-legend absolute right-3 top-3 z-[500]">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-gray-700 dark:text-gray-200">
                                <span class="gps-report-legend-dot bg-slate-900 dark:bg-slate-100"></span>
                                Inicio
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-gray-700 dark:text-gray-200">
                                <span class="gps-report-legend-dot bg-emerald-500"></span>
                                Recorrido
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-gray-700 dark:text-gray-200">
                                <span class="gps-report-legend-dot bg-emerald-500 ring-4 ring-emerald-500/20"></span>
                                Fin
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>

    <script>
        window.__gpsReportPoints = @json($reportPoints);
        window.__gpsReportSegments = @json($reportSegments);

        function initOrUpdateMap(points, segments) {
            var defaultCenter = [-12.046374, -77.042793];

            function toCoords(pts) {
                return (pts || [])
                    .map(function (point) { return [parseFloat(point.latitude), parseFloat(point.longitude)]; })
                    .filter(function (coords) { return !Number.isNaN(coords[0]) && !Number.isNaN(coords[1]); });
            }

            function pointMarkerIcon() {
                var phoneSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"><path d="M10.5 1.5H8.25C7.007 1.5 6 2.507 6 3.75v16.5c0 1.243 1.007 2.25 2.25 2.25h7.5c1.243 0 2.25-1.007 2.25-2.25V3.75c0-1.243-1.007-2.25-2.25-2.25H13.5m-6 0V3h9V1.5m-9 0h9m-3.75 4.5v3m-3 0h6"/></svg>';

                return L.divIcon({
                    className: '',
                    html: '<span class="gps-report-marker"><span class="gps-report-marker__pulse"></span><span class="gps-report-marker__icon">' + phoneSvg + '</span></span>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 20],
                });
            }

            function startMarkerIcon() {
                return L.divIcon({
                    className: '',
                    html: '<span class="gps-report-start-marker"></span>',
                    iconSize: [12, 12],
                    iconAnchor: [6, 6],
                });
            }

            // Destroy previous map if it exists
            if (window.__gpsReportMap) {
                window.__gpsReportMap.remove();
                window.__gpsReportMap = null;
            }
            window.__gpsReportPolylines = [];
            window.__gpsReportStartMarker = null;
            window.__gpsReportEndMarker = null;

            var container = document.getElementById('gps-report-map');
            if (!container) return;

            var map = L.map('gps-report-map', { zoomControl: true }).setView(defaultCenter, 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);

            window.__gpsReportMap = map;
            window.__gpsReportPolylines = [];

            var allCoords = [];

            if (segments && segments.length > 0) {
                // Draw each segment as a separate polyline
                var segmentColors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#ec4899', '#14b8a6', '#6366f1'];

                for (var s = 0; s < segments.length; s++) {
                    var segCoords = toCoords(segments[s]);
                    if (segCoords.length < 2) {
                        allCoords = allCoords.concat(segCoords);
                        continue;
                    }

                    var color = segmentColors[s % segmentColors.length];
                    var polyline = L.polyline(segCoords, {
                        color: color,
                        weight: 4,
                        opacity: 0.9,
                        lineCap: 'round',
                        lineJoin: 'round',
                    }).addTo(map);

                    window.__gpsReportPolylines.push(polyline);
                    allCoords = allCoords.concat(segCoords);
                }
            } else {
                // Fallback: single polyline from flat points
                var coords = toCoords(points);
                if (coords.length > 1) {
                    var polyline = L.polyline(coords, {
                        color: '#10b981',
                        weight: 4,
                        opacity: 0.9,
                        lineCap: 'round',
                        lineJoin: 'round',
                    }).addTo(map);
                    window.__gpsReportPolylines.push(polyline);
                }
                allCoords = coords;
            }

            // Start and end markers from ALL points
            if (allCoords.length > 0) {
                window.__gpsReportStartMarker = L.marker(allCoords[0], {
                    icon: startMarkerIcon(),
                }).addTo(map);

                window.__gpsReportEndMarker = L.marker(allCoords[allCoords.length - 1], {
                    icon: pointMarkerIcon(),
                }).addTo(map);

                if (allCoords.length === 1) {
                    map.setView(allCoords[0], 16);
                } else {
                    map.fitBounds(L.latLngBounds(allCoords), {
                        padding: [60, 60],
                        maxZoom: 16,
                    });
                }
            }

            // Invalidate size after DOM morph
            setTimeout(function () {
                map.invalidateSize();
            }, 200);
        }

        window.initOrUpdateMap = initOrUpdateMap;

        // Listen for Livewire v3 dispatched browser events
        document.addEventListener('gps-report-generated', function (event) {
            var points = (event.detail && event.detail.points) || window.__gpsReportPoints;
            var segments = (event.detail && event.detail.segments) || window.__gpsReportSegments;
            window.__gpsReportPoints = points;
            window.__gpsReportSegments = segments;
            setTimeout(function () {
                initOrUpdateMap(points, segments);
            }, 150);
        });

        // Initial render if points already exist (page reload with data)
        document.addEventListener('DOMContentLoaded', function () {
            if (window.__gpsReportPoints && window.__gpsReportPoints.length > 0) {
                initOrUpdateMap(window.__gpsReportPoints, window.__gpsReportSegments);
            }
        });
    </script>
</x-filament-panels::page>
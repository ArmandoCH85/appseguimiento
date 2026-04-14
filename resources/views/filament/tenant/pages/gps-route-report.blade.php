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
            height: 65dvh;
            min-height: 28rem;
            width: 100%;
            z-index: 0;
        }

        .dark .gps-report-card {
            border-color: rgba(255, 255, 255, 0.1);
            background: #111827;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.28);
        }

        .dark .gps-report-legend {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(3, 7, 18, 0.86);
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
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
            backdrop-filter: blur(10px);
        }

        .gps-report-legend-dot {
            display: inline-block;
            width: 0.65rem;
            height: 0.65rem;
            border-radius: 9999px;
        }
    </style>

    <div class="space-y-4">
        {{-- FILTROS: Formulario Filament v5 --}}
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button wire:click="generateReport" icon="heroicon-m-magnifying-glass" color="primary">
                Generar Reporte
            </x-filament::button>

            @if($reportGenerated && !empty($reportPoints))
                <x-filament::button wire:click="exportToExcel" icon="heroicon-m-arrow-down-tray" color="success" outlined>
                    Exportar Excel
                </x-filament::button>
            @endif

            <div wire:loading wire:target="generateReport,exportToExcel">
                <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
            </div>
        </div>

        {{-- MAPA — siempre presente, con overlay si no hay datos --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-map-pin" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    <span>Mapa de Recorrido</span>
                </div>
            </x-slot>

            @if($reportGenerated)
                {{-- Resumen rápido --}}
                <div class="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg border border-gray-200/80 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Puntos</p>
                        <p class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ $pointsCount }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200/80 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Distancia</p>
                        <p class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ $distanceFormatted }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200/80 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Duración</p>
                        <p class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ $durationFormatted }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200/80 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Período</p>
                        <p class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                            @php
                                $formData = $this->form->getState();
                                $dateFilter = $formData['dateFilter'] ?? 'today';
                                $startDate = $formData['startDate'] ?? '';
                                $endDate = $formData['endDate'] ?? '';
                            @endphp
                            @if($dateFilter === 'today') Hoy
                            @elseif($dateFilter === 'yesterday') Ayer
                            @else {{ $startDate }} → {{ $endDate }}
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <div class="gps-report-card relative">
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
                    <div class="gps-report-legend absolute left-4 top-4 z-[500]">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400">
                            Referencia
                        </p>
                        <div class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                            <div class="flex items-center gap-2">
                                <span class="gps-report-legend-dot bg-slate-900 dark:bg-slate-100"></span>
                                <span>Punto de inicio</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="gps-report-legend-dot bg-emerald-500"></span>
                                <span>Recorrido</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="gps-report-legend-dot bg-emerald-500 ring-4 ring-emerald-500/20"></span>
                                <span>Punto final</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        @if($reportGenerated)
            {{-- TABLA DE ACTIVIDAD --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        <span>Actividad del Dispositivo</span>
                    </div>
                </x-slot>
                <x-slot name="description">
                    {{ $reportSummary['imei'] ?? '' }}{{ ($reportSummary['user_name'] ?? '') ? ' · ' . $reportSummary['user_name'] : '' }}
                    — {{ $reportSummary['period'] ?? '' }}
                </x-slot>

                @if(!empty($reportPoints))
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                                <thead class="bg-gray-50 dark:bg-white/[0.03]">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">#</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-400">Hora GPS</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">Latitud</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">Longitud</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Precisión</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-400">Velocidad</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                    @foreach($this->getPaginatedPoints() as $index => $point)
                                        @php $globalIndex = ($currentPage - 1) * $perPage + $index + 1; @endphp
                                        <tr class="@if($loop->first && $currentPage === 1)bg-emerald-50/80 dark:bg-emerald-500/10 @endif hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                            <td class="whitespace-nowrap px-4 py-2.5 font-medium text-gray-950 dark:text-white">
                                                {{ $globalIndex }}
                                                @if($globalIndex === 1)
                                                    <x-filament::badge size="xs" color="success" class="ml-1">Inicio</x-filament::badge>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-gray-900 dark:text-white">
                                                {{ $point['time_human'] }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2.5 text-right font-mono text-gray-900 dark:text-white">
                                                {{ number_format($point['latitude'], 8) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2.5 text-right font-mono text-gray-900 dark:text-white">
                                                {{ number_format($point['longitude'], 8) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2.5 text-center">
                                                <x-filament::badge size="xs" color="info">
                                                    {{ $point['accuracy_human'] }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2.5 text-center font-medium text-gray-900 dark:text-white">
                                                {{ $point['speed_human'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination Controls --}}
                        @if($this->getTotalPages() > 1)
                            <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 dark:border-white/10">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Mostrando {{ ($currentPage - 1) * $perPage + 1 }}–{{ min($currentPage * $perPage, count($reportPoints)) }} de {{ count($reportPoints) }} puntos
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-filament::button size="sm" color="gray" outlined wire:click="previousPage" :disabled="$currentPage <= 1">
                                        Anterior
                                    </x-filament::button>

                                    @for($i = max(1, $currentPage - 2); $i <= min($this->getTotalPages(), $currentPage + 2); $i++)
                                        <x-filament::button size="sm" :color="$i === $currentPage ? 'primary' : 'gray'" :outlined="$i !== $currentPage" wire:click="goToPage({{ $i }})">
                                            {{ $i }}
                                        </x-filament::button>
                                    @endfor

                                    <x-filament::button size="sm" color="gray" outlined wire:click="nextPage" :disabled="$currentPage >= $this->getTotalPages()">
                                        Siguiente
                                    </x-filament::button>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-lg border border-gray-200/80 p-8 text-center dark:border-white/10">
                        <x-filament::icon icon="heroicon-o-map-pin" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                        <p class="mt-3 text-sm font-medium text-gray-600 dark:text-gray-300">
                            No hay puntos de actividad para mostrar.
                        </p>
                    </div>
                @endif
            </x-filament::section>
        @endif
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
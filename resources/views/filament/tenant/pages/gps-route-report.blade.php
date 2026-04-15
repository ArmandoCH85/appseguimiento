<x-filament-panels::page>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        .fi-section-content .fi-grid > div {
            align-items: flex-end;
        }

        .fi-section-content .fi-select-wrapper .fi-select-input,
        .fi-section-content .fi-datepicker-wrapper .fi-datepicker-input {
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.01em;
        }

        .fi-section-content .fi-select-option-label {
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }

        .gps-report-card {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 70dvh;
            min-height: 22rem;
            margin: 0;
            padding: 0;
            overflow: hidden;
            border-radius: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
            position: relative;
        }

        @media (min-width: 768px) {
            .gps-report-card {
                height: 78dvh;
                min-height: 32rem;
            }
        }

        .gps-report-map-wrap {
            flex: 1 1 auto;
            min-height: 0;
        }

        .gps-report-marker {
            position: relative;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gps-report-marker__icon {
            width: 36px;
            height: 36px;
            background: #0f172a; /* Slate 900 corporativo */
            border-radius: 50%; /* Circular moderno */
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.3);
            border: 3px solid #ffffff;
            z-index: 10;
        }

        .gps-report-marker__icon svg {
            width: 18px;
            height: 18px;
            color: #10b981; /* Acento esmeralda */
        }

        .gps-report-marker__pulse {
            position: absolute;
            inset: 2px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.25);
            border: 1px solid rgba(16, 185, 129, 0.4);
            animation: gps-report-pulse 2s ease-out infinite;
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
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .dark .gps-report-card {
            border-color: transparent;
            background: transparent;
            box-shadow: none;
        }

        .dark .gps-report-player {
            border-color: rgba(255, 255, 255, 0.1);
            background: #1f2937;
        }

        .dark .gps-report-player button {
            background: #111827;
            color: #e5e7eb;
            border-color: rgba(55, 65, 81, 0.9);
            box-shadow: none;
        }

        .dark .gps-report-player button:hover {
            background-color: #0b1220;
        }

        .dark .gps-report-player button:active {
            background-color: #0b1220;
        }

        .dark .gps-report-player input[type="range"] {
            background: linear-gradient(to right, #10b981 0%, #10b981 var(--gps-slider-pct), #374151 var(--gps-slider-pct), #374151 100%);
        }

        .dark .gps-report-player input[type="range"]::-webkit-slider-thumb {
            background: #10b981;
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

        .gps-report-player {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-top: 1px solid rgba(229, 231, 235, 0.8);
            background: #ffffff;
        }

        .gps-report-player button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            border: 1px solid rgba(229, 231, 235, 0.9);
            cursor: pointer;
            transition: transform 0.15s ease, background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            flex-shrink: 0;
            background: #f9fafb;
            color: #0f172a;
            box-shadow: 0 1px 0 rgba(15, 23, 42, 0.04);
        }

        .gps-report-player button:hover {
            background-color: #f3f4f6;
            transform: translateY(-1px);
        }

        .gps-report-player button:active {
            transform: translateY(0) scale(0.97);
            background-color: #e5e7eb;
        }

        .gps-report-player button:focus-visible {
            outline: 2px solid #10b981;
            outline-offset: 2px;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.18);
        }

        .gps-report-player button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
        }

        .gps-report-player button.play-btn {
            background-color: #10b981;
            color: #ffffff;
            border-color: rgba(16, 185, 129, 0.35);
            box-shadow: 0 10px 18px rgba(16, 185, 129, 0.18);
        }

        .gps-report-player button.play-btn:hover {
            background-color: #059669;
        }

        .gps-report-player button.reset-btn {
            background-color: #ffffff;
            color: #0f172a;
        }

        .gps-report-player button.reset-btn:hover {
            background-color: #f3f4f6;
        }

        .gps-report-player input[type="range"] {
            flex: 1;
            -webkit-appearance: none;
            appearance: none;
            height: 0.375rem;
            border-radius: 9999px;
            --gps-slider-pct: 0%;
            background: linear-gradient(to right, #10b981 0%, #10b981 var(--gps-slider-pct), #e5e7eb var(--gps-slider-pct), #e5e7eb 100%);
            outline: none;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .gps-report-player input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 0.875rem;
            height: 0.875rem;
            border-radius: 9999px;
            background: #ffffff;
            cursor: pointer;
            border: 2px solid #10b981;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.16);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .gps-report-player input[type="range"]::-moz-range-thumb {
            width: 0.875rem;
            height: 0.875rem;
            border-radius: 9999px;
            background: #ffffff;
            cursor: pointer;
            border: 2px solid #10b981;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.16);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .gps-report-player input[type="range"]:focus-visible::-webkit-slider-thumb,
        .gps-report-player input[type="range"]:focus-visible::-moz-range-thumb {
            transform: scale(1.08);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.18), 0 8px 18px rgba(15, 23, 42, 0.16);
        }

        .gps-player-timestamp {
            display: flex;
            align-items: center;
            padding: 0.1875rem 0.5rem;
            border-radius: 0.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }

        .dark .gps-player-timestamp {
            background: rgba(15, 23, 42, 0.55);
            border-color: rgba(148, 163, 184, 0.22);
        }

        .gps-report-player .speed-btn {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid rgba(229, 231, 235, 0.9);
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .gps-report-player .speed-btn:hover {
            background: #f3f4f6;
        }

        .gps-report-player .speed-btn.active {
            background: #10b981;
            color: #ffffff;
            border-color: rgba(16, 185, 129, 0.35);
        }

        .dark .gps-report-player button.reset-btn {
            background-color: #111827;
            color: #e5e7eb;
            border-color: rgba(55, 65, 81, 0.9);
        }

        .dark .gps-report-player button.reset-btn:hover {
            background-color: #0b1220;
        }

        .dark .gps-report-player .speed-btn {
            background: #111827;
            color: #e5e7eb;
            border-color: rgba(55, 65, 81, 0.9);
        }

        .dark .gps-report-player .speed-btn:hover {
            background: #4b5563;
        }

        .dark .gps-report-player .speed-btn.active {
            background: #10b981;
            color: #ffffff;
        }

        @keyframes gps-tracker-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        .gps-tracker-icon {
            animation: gps-tracker-bounce 1.5s ease-in-out infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .gps-tracker-icon {
                animation: none;
            }

            .gps-report-player button,
            .gps-report-player input[type="range"],
            .gps-report-player input[type="range"]::-webkit-slider-thumb,
            .gps-report-player input[type="range"]::-moz-range-thumb,
            .gps-report-player .speed-btn {
                transition: none;
            }
        }
    </style>

    <div class="space-y-4">
        {{ $this->form }}

        <div wire:loading.flex class="items-center gap-3 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 dark:border-primary-800 dark:bg-primary-950">
            <x-filament::loading-indicator class="h-5 w-5 text-primary-600 dark:text-primary-400" />
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">Cargando recorrido…</span>
        </div>

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

                <div wire:ignore class="gps-report-map-wrap">
                    <div id="gps-report-map"></div>
                </div>

                <div id="gps-player-container">
                    @if(!empty($reportPoints))
                        <div id="gps-player" class="gps-report-player" wire:key="gps-player-{{ count($mapPoints) }}">
                            <button id="gps-player-reset" type="button" title="Reiniciar" aria-label="Reiniciar" class="reset-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                    <path d="M16 10a6 6 0 1 1-2.2-4.6"/>
                                    <path d="M16 4v4h-4"/>
                                </svg>
                            </button>
                            <button id="gps-player-play" type="button" title="Reproducir" aria-label="Reproducir" aria-pressed="false" class="play-btn">
                                <svg id="gps-player-play-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                    <path d="M8 6.5 14 10l-6 3.5z"/>
                                </svg>
                            </button>
                            <input id="gps-player-slider" type="range" min="0" max="{{ max(count($mapPoints) - 1, 0) }}" value="0" aria-label="Progreso" />
                            <div id="gps-player-timestamp" class="gps-player-timestamp" aria-live="polite" aria-atomic="true">
                                <span id="gps-player-time-text" class="font-mono text-[10px] md:text-[11px] font-medium text-slate-700 dark:text-slate-200 whitespace-nowrap tabular-nums">
                                    {{ $mapPoints[0]['time_human'] ?? '—' }}
                                </span>
                            </div>
                            <span id="gps-player-counter" class="text-xs font-mono font-semibold text-gray-400 dark:text-gray-500 whitespace-nowrap tabular-nums">0/{{ count($mapPoints) }}</span>
                            <div class="flex items-center gap-0.5">
                                <button type="button" class="speed-btn active" data-speed="1" aria-label="Velocidad 1x" aria-pressed="true">1x</button>
                                <button type="button" class="speed-btn" data-speed="2" aria-label="Velocidad 2x" aria-pressed="false">2x</button>
                                <button type="button" class="speed-btn" data-speed="4" aria-label="Velocidad 4x" aria-pressed="false">4x</button>
                            </div>
                        </div>
                    @endif
                </div>

                @if($reportGenerated && empty($reportPoints))
                    <div class="gps-report-empty-overlay">
                        <div class="gps-report-empty-panel">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                                <x-filament::icon icon="heroicon-o-map-pin" class="h-8 w-8" />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                                Sin datos de recorrido
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                No se encontraron puntos GPS para el período seleccionado.
                            </p>
                        </div>
                    </div>
                @endif

            </div>
        </x-filament::section>
    </div>

    <script>
        window.__gpsReportPoints = @json($mapPoints);
        window.__gpsReportSegments = @json($reportSegments);

        if (!window.__gpsPlayer) {
            window.__gpsPlayer = {
                playing: false,
                currentIndex: 0,
                speed: 1,
                intervalId: null,
                trackerMarker: null,
                allCoords: [],
                points: [],
                baseDelayMs: 500,
            };
        }

        function initOrUpdateMap(points, segments) {
            var p = window.__gpsPlayer;
            var defaultCenter = [-12.046374, -77.042793];

            function toCoords(pts) {
                return (pts || [])
                    .map(function (point) { return [parseFloat(point.latitude), parseFloat(point.longitude)]; })
                    .filter(function (coords) { return !Number.isNaN(coords[0]) && !Number.isNaN(coords[1]); });
            }

            function modernMarkerIcon() {
                var navSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" /></svg>';

                return L.divIcon({
                    className: '',
                    html: '<span class="gps-report-marker"><span class="gps-report-marker__pulse"></span><span class="gps-report-marker__icon">' + navSvg + '</span></span>',
                    iconSize: [48, 48],
                    iconAnchor: [24, 24],
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

            function trackerIcon() {
                return L.divIcon({
                    className: '',
                    html: '<div class="gps-tracker-icon" style="width:20px;height:20px;background:#2563eb;border:3px solid #fff;border-radius:50%;box-shadow:0 2px 8px rgba(37,99,235,0.5);"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                });
            }

            // Stop player BEFORE destroying map so marker references are still valid
            if (p.intervalId) {
                clearTimeout(p.intervalId);
                p.intervalId = null;
            }
            p.playing = false;
            p.currentIndex = 0;

            if (window.__gpsReportMap) {
                window.__gpsReportMap.remove();
                window.__gpsReportMap = null;
            }
            window.__gpsReportPolylines = [];
            window.__gpsReportStartMarker = null;
            window.__gpsReportEndMarker = null;

            // Clear stale tracker marker reference (old map was removed)
            p.trackerMarker = null;

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

            if (allCoords.length > 0) {
                window.__gpsReportStartMarker = L.marker(allCoords[0], {
                    icon: startMarkerIcon(),
                }).addTo(map);

                window.__gpsReportEndMarker = L.marker(allCoords[allCoords.length - 1], {
                    icon: modernMarkerIcon(),
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

            p.allCoords = allCoords;
            p.points = points || [];
            p.currentIndex = 0;
            p.playing = false;

            var slider = document.getElementById('gps-player-slider');
            var counter = document.getElementById('gps-player-counter');
            if (slider) {
                slider.max = String(Math.max(allCoords.length - 1, 0));
                slider.value = '0';
                updateSliderVisual(slider);
            }
            if (counter) {
                counter.textContent = '0/' + allCoords.length;
            }

            // Create tracker marker on the new map
            if (allCoords.length > 0) {
                p.trackerMarker = L.marker(allCoords[0], { icon: trackerIcon() }).addTo(map);
                p.trackerMarker.setOpacity(0);
            }

            setTimeout(function () {
                map.invalidateSize();
            }, 200);

            bindPlayerEvents();
        }

        function startPlayer() {
            var p = window.__gpsPlayer;
            if (p.allCoords.length === 0) return;
            if (!p.trackerMarker) return;

            p.playing = true;
            updatePlayButton();

            p.trackerMarker.setOpacity(1);
            advancePlayer();
        }

        function pausePlayer() {
            var p = window.__gpsPlayer;
            p.playing = false;
            if (p.intervalId) {
                clearTimeout(p.intervalId);
                p.intervalId = null;
            }
            updatePlayButton();
        }

        function stopPlayer() {
            var p = window.__gpsPlayer;
            p.playing = false;
            p.currentIndex = 0;
            if (p.intervalId) {
                clearTimeout(p.intervalId);
                p.intervalId = null;
            }
            if (p.trackerMarker) {
                p.trackerMarker.setOpacity(0);
            }
            var slider = document.getElementById('gps-player-slider');
            if (slider) {
                slider.value = '0';
                updateSliderVisual(slider);
            }
            var counter = document.getElementById('gps-player-counter');
            if (counter) counter.textContent = '0/' + p.allCoords.length;
            updateTimestamp(0);
            updatePlayButton();
        }

        function updateTimestamp(index) {
            var p = window.__gpsPlayer;
            var el = document.getElementById('gps-player-time-text');
            if (!el) return;
            var point = p.points[index];
            el.textContent = (point && point.time_human) ? point.time_human : '—';
        }

        function advancePlayer() {
            var p = window.__gpsPlayer;
            if (!p.playing) return;
            if (!p.trackerMarker) return;

            if (p.currentIndex >= p.allCoords.length) {
                pausePlayer();
                return;
            }

            var coord = p.allCoords[p.currentIndex];
            p.trackerMarker.setLatLng(coord);

            // Pan map to follow the tracker when it leaves the visible area
            if (window.__gpsReportMap) {
                if (!window.__gpsReportMap.getBounds().contains(coord)) {
                    window.__gpsReportMap.panTo(coord, { animate: true, duration: 0.4 });
                }
            }

            var slider = document.getElementById('gps-player-slider');
            if (slider) {
                slider.value = String(p.currentIndex);
                updateSliderVisual(slider);
            }
            var counter = document.getElementById('gps-player-counter');
            if (counter) counter.textContent = (p.currentIndex + 1) + '/' + p.allCoords.length;

            updateTimestamp(p.currentIndex);

            p.currentIndex++;

            var delay = p.baseDelayMs / p.speed;
            p.intervalId = setTimeout(advancePlayer, delay);
        }

        function seekPlayer(index) {
            var p = window.__gpsPlayer;
            if (index < 0 || index >= p.allCoords.length) return;
            if (!p.trackerMarker) return;

            p.currentIndex = index;
            p.trackerMarker.setLatLng(p.allCoords[index]);
            p.trackerMarker.setOpacity(1);

            var slider = document.getElementById('gps-player-slider');
            if (slider) {
                slider.value = String(index);
                updateSliderVisual(slider);
            }
            var counter = document.getElementById('gps-player-counter');
            if (counter) counter.textContent = (index + 1) + '/' + p.allCoords.length;

            updateTimestamp(index);

            if (p.playing) {
                if (p.intervalId) clearTimeout(p.intervalId);
                advancePlayer();
            }
        }

        function updatePlayButton() {
            var p = window.__gpsPlayer;
            var btn = document.getElementById('gps-player-play-icon');
            if (!btn) return;
            var playBtn = document.getElementById('gps-player-play');

            if (p.playing) {
                btn.innerHTML = '<path d="M8 7v6"/><path d="M12 7v6"/>';
                if (playBtn) {
                    playBtn.setAttribute('aria-pressed', 'true');
                    playBtn.setAttribute('aria-label', 'Pausar');
                    playBtn.setAttribute('title', 'Pausar');
                }
            } else {
                btn.innerHTML = '<path d="M8 6.5 14 10l-6 3.5z"/>';
                if (playBtn) {
                    playBtn.setAttribute('aria-pressed', 'false');
                    playBtn.setAttribute('aria-label', 'Reproducir');
                    playBtn.setAttribute('title', 'Reproducir');
                }
            }
        }

        function updateSliderVisual(slider) {
            if (!slider) return;
            var max = parseInt(slider.max || '0', 10);
            var val = parseInt(slider.value || '0', 10);
            var pct = (max > 0) ? (val / max) * 100 : 0;
            slider.style.setProperty('--gps-slider-pct', pct + '%');
        }

        function setSpeed(speed) {
            var p = window.__gpsPlayer;
            p.speed = speed;
            document.querySelectorAll('.speed-btn').forEach(function(btn) {
                var active = parseInt(btn.dataset.speed) === speed;
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            if (p.playing) {
                if (p.intervalId) clearTimeout(p.intervalId);
                advancePlayer();
            }
        }

        function bindPlayerEvents() {
            var playBtn = document.getElementById('gps-player-play');
            var resetBtn = document.getElementById('gps-player-reset');
            var slider = document.getElementById('gps-player-slider');
            var speedBtns = document.querySelectorAll('.speed-btn');

            if (playBtn) {
                playBtn.onclick = function() {
                    if (window.__gpsPlayer.playing) {
                        pausePlayer();
                    } else {
                        startPlayer();
                    }
                };
            }

            if (resetBtn) {
                resetBtn.onclick = function() {
                    stopPlayer();
                };
            }

            if (slider) {
                slider.oninput = function(e) {
                    seekPlayer(parseInt(e.target.value, 10));
                    updateSliderVisual(slider);
                };
                updateSliderVisual(slider);
            }

            if (speedBtns.length > 0) {
                speedBtns.forEach(function(btn) {
                    btn.onclick = function() {
                        setSpeed(parseInt(btn.dataset.speed, 10));
                    };
                });
                setSpeed((window.__gpsPlayer && window.__gpsPlayer.speed) ? window.__gpsPlayer.speed : 1);
            }
        }

        window.initOrUpdateMap = initOrUpdateMap;

        // Deduplicate the listener — Livewire re-executes this script on every render
        // (fields use ->live()), causing listeners to accumulate and initOrUpdateMap
        // to fire multiple times, which stops the player and destroys the map.
        if (window.__gpsReportListener) {
            document.removeEventListener('gps-report-generated', window.__gpsReportListener);
        }
        window.__gpsReportListener = function (event) {
            var points = (event.detail && event.detail.points) || window.__gpsReportPoints;
            var segments = (event.detail && event.detail.segments) || window.__gpsReportSegments;
            window.__gpsReportPoints = points;
            window.__gpsReportSegments = segments;
            setTimeout(function () {
                initOrUpdateMap(points, segments);
            }, 150);
        };
        document.addEventListener('gps-report-generated', window.__gpsReportListener);

        if (!window.__gpsReportDomReadyBound) {
            window.__gpsReportDomReadyBound = true;
            document.addEventListener('DOMContentLoaded', function () {
                if (window.__gpsReportPoints && window.__gpsReportPoints.length > 0) {
                    initOrUpdateMap(window.__gpsReportPoints, window.__gpsReportSegments);
                }
            });
        }
    </script>
</x-filament-panels::page>

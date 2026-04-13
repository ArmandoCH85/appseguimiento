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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gps-report-marker__icon {
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

        .gps-report-marker__icon svg {
            width: 20px;
            height: 20px;
            color: #ffffff;
        }

        .gps-report-marker__pulse {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.25);
            animation: gps-report-pulse 2s ease-out infinite;
        }

        @keyframes gps-report-pulse {
            0% { transform: scale(0.9); opacity: 0.85; }
            100% { transform: scale(1.8); opacity: 0; }
        }

        .gps-report-start-marker {
            width: 12px;
            height: 12px;
            border-radius: 9999px;
            background: #0f172a;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.2);
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

        .dark .gps-report-empty-overlay {
            background: linear-gradient(135deg, rgba(3, 7, 18, 0.82), rgba(3, 7, 18, 0.7), rgba(3, 7, 18, 0.62));
        }

        .dark .gps-report-empty-panel {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(17, 24, 39, 0.96);
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

        .dark .gps-report-legend {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(3, 7, 18, 0.86);
        }
    </style>

    <div class="space-y-4">
        {{-- HEADER: Filtros --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-map" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    <span>Filtros del Reporte</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                {{-- Dispositivo --}}
                <div class="lg:col-span-4">
                    <label for="gps-report-device" class="text-sm font-medium text-gray-950 dark:text-white">
                        Dispositivo
                    </label>
                    <div class="mt-2">
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="gps-report-device" wire:model.live="selectedDeviceId">
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

                {{-- Período --}}
                <div class="lg:col-span-3">
                    <label for="gps-report-period" class="text-sm font-medium text-gray-950 dark:text-white">
                        Período
                    </label>
                    <div class="mt-2">
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="gps-report-period" wire:model.live="dateFilter">
                                <option value="today">Hoy</option>
                                <option value="yesterday">Ayer</option>
                                <option value="custom">Personalizado</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>

                {{-- Fecha personalizado --}}
                @if($dateFilter === 'custom')
                    <div class="lg:col-span-2">
                        <label for="gps-report-start" class="text-sm font-medium text-gray-950 dark:text-white">
                            Fecha inicio
                        </label>
                        <div class="mt-2">
                            <x-filament::input.wrapper>
                                <x-filament::input type="date" id="gps-report-start" wire:model.live="startDate" />
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <label for="gps-report-end" class="text-sm font-medium text-gray-950 dark:text-white">
                            Fecha fin
                        </label>
                        <div class="mt-2">
                            <x-filament::input.wrapper>
                                <x-filament::input type="date" id="gps-report-end" wire:model.live="endDate" />
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                @else
                    <div class="hidden lg:block lg:col-span-2"></div>
                    <div class="hidden lg:block lg:col-span-2"></div>
                @endif
            </div>

            {{-- Botones --}}
            <div class="mt-4 flex flex-wrap items-center gap-3">
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
        </x-filament::section>

        {{-- MAPA --}}
        @if($reportGenerated)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-map-pin" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        <span>Mapa de Recorrido</span>
                    </div>
                </x-slot>

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
                            @if($dateFilter === 'today') Hoy
                            @elseif($dateFilter === 'yesterday') Ayer
                            @else {{ $startDate ?? '' }} → {{ $endDate ?? '' }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="gps-report-card relative" 
                     x-data="gpsMapManager()" 
                     x-init="init()"
                     wire:ignore.self>
                    <div id="gps-report-map"></div>

                    @if(!$reportGenerated || empty($reportPoints))
                        <div class="gps-report-empty-overlay" x-show="!hasPoints" x-transition>
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
                                    <span>Punto actual</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            {{-- TABLA DE ACTIVIDAD --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        <span>Actividad del Dispositivo</span>
                    </div>
                </x-slot>
                <x-slot name="description">
                    {{ $reportSummary['imei'] ?? '' }}{{ $reportSummary['user_name'] ? ' · ' . $reportSummary['user_name'] : '' }}
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
        function gpsMapManager() {
            return {
                hasPoints: @json(!empty($reportPoints)),
                points: @json($reportPoints),
                map: null,

                init() {
                    if (this.points.length > 0) {
                        this.$nextTick(() => {
                            this.renderMap();
                        });
                    }
                },

                toCoords(points) {
                    return (points || [])
                        .map((point) => [parseFloat(point.latitude), parseFloat(point.longitude)])
                        .filter((coords) => !Number.isNaN(coords[0]) && !Number.isNaN(coords[1]));
                },

                pointMarkerIcon() {
                    const phoneSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;color:white;">
                        <path d="M10.5 1.5H8.25C7.007 1.5 6 2.507 6 3.75v16.5c0 1.243 1.007 2.25 2.25 2.25h7.5c1.243 0 2.25-1.007 2.25-2.25V3.75c0-1.243-1.007-2.25-2.25-2.25H13.5m-6 0V3h9V1.5m-9 0h9m-3.75 4.5v3m-3 0h6"/>
                    </svg>`;

                    return L.divIcon({
                        className: '',
                        html: `<div style="position:relative;width:40px;height:40px;display:flex;align-items:center;justify-content:center;"><div style="position:absolute;inset:0;border-radius:9999px;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.25);animation:gps-report-pulse 2s ease-out infinite;"></div><div style="width:32px;height:32px;background:#10b981;border-radius:8px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(16,185,129,0.4);border:2px solid white;">${phoneSvg}</div></div>`,
                        iconSize: [40, 40],
                        iconAnchor: [20, 20],
                    });
                },

                startMarkerIcon() {
                    return L.divIcon({
                        className: '',
                        html: '<div style="width:12px;height:12px;border-radius:9999px;background:#0f172a;border:2px solid white;box-shadow:0 4px 10px rgba(15,23,42,0.2);"></div>',
                        iconSize: [12, 12],
                        iconAnchor: [6, 6],
                    });
                },

                renderMap() {
                    const coords = this.toCoords(this.points);

                    // Destroy existing map
                    if (this.map) {
                        this.map.remove();
                        this.map = null;
                    }

                    if (!coords.length) return;

                    this.map = L.map('gps-report-map', { zoomControl: true }).setView(coords[0], 14);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        maxZoom: 19,
                    }).addTo(this.map);

                    L.polyline(coords, {
                        color: '#10b981',
                        weight: 4,
                        opacity: 0.9,
                        lineCap: 'round',
                        lineJoin: 'round',
                    }).addTo(this.map);

                    L.marker(coords[0], { icon: this.startMarkerIcon() }).addTo(this.map);
                    L.marker(coords[coords.length - 1], { icon: this.pointMarkerIcon() }).addTo(this.map);

                    if (coords.length > 1) {
                        this.map.fitBounds(L.latLngBounds(coords), { padding: [60, 60], maxZoom: 16 });
                    } else {
                        this.map.setView(coords[0], 16);
                    }
                }
            };
        }
    </script>

    <style>
        @keyframes gps-report-pulse {
            0% { transform: scale(0.9); opacity: 0.85; }
            100% { transform: scale(1.8); opacity: 0; }
        }
    </style>
</x-filament-panels::page>

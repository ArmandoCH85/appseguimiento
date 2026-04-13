<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Mapa de Rastreo GPS en Tiempo Real
        </x-slot>

        <x-slot name="description">
            Seleccioná un dispositivo para ver su recorrido. Se actualiza cada 30 segundos.
        </x-slot>

        <form method="GET" class="mb-4">
            <select name="device_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">-- Seleccioná un dispositivo --</option>
                @foreach($devices as $device)
                    <option value="{{ $device->id }}" @selected($selectedDeviceId === $device->id)>
                        {{ $device->imei }} — {{ $device->user->name ?? 'Sin usuario' }}
                    </option>
                @endforeach
            </select>
        </form>

        @if($selectedDeviceId)
            {{-- Polling nativo de Livewire: llama refreshPoints() cada 30s --}}
            <div wire:poll.30s="refreshPoints"></div>

            <div wire:ignore>
                <div id="gps-map" style="height: 500px; width: 100%; border-radius: 0.5rem;"></div>
            </div>

            <script>
                (function () {
                    function initGpsMap() {
                        if (window.__gpsMap) return;

                        import('{{ Vite::asset("resources/js/widgets/gps-live-map.js") }}').then(module => {
                            const GpsLiveMap = module.default;
                            window.__gpsMap = new GpsLiveMap('gps-map', {
                                deviceId: '{{ $selectedDeviceId }}',
                                tenantId: '{{ $tenantId }}',
                            });

                            const initialPoints = @json($initialPoints);
                            if (initialPoints.length > 0) {
                                window.__gpsMap.loadInitialPoints(initialPoints);
                            }
                        }).catch(err => console.error('Error loading GPS map:', err));
                    }

                    // Livewire dispatcha browser events con window.addEventListener
                    window.addEventListener('gps-points-updated', (event) => {
                        if (window.__gpsMap) {
                            window.__gpsMap.loadInitialPoints(event.detail.points);
                        }
                    });

                    document.addEventListener('DOMContentLoaded', initGpsMap);
                    if (document.readyState !== 'loading') {
                        initGpsMap();
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

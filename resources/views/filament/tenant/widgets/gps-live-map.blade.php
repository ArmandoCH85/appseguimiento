<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Mapa de Rastreo GPS en Tiempo Real
        </x-slot>

        <x-slot name="description">
            Seleccioná un dispositivo para ver su recorrido en vivo.
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
            <div wire:ignore>
                <div id="gps-map" style="height: 500px; width: 100%; border-radius: 0.5rem;"></div>
            </div>

            <script>
                function initGpsMap() {
                    if (window.__gpsMapInitialized) return;
                    window.__gpsMapInitialized = true;

                    import('{{ Vite::asset("resources/js/widgets/gps-live-map.js") }}').then(module => {
                        const GpsLiveMap = module.default;
                        const map = new GpsLiveMap('gps-map', {
                            deviceId: '{{ $selectedDeviceId }}',
                            tenantId: '{{ $tenantId }}',
                        });

                        const initialPoints = @json($initialPoints);
                        if (initialPoints.length > 0) {
                            map.loadInitialPoints(initialPoints);
                        }
                    }).catch(err => console.error('Error loading GPS map:', err));
                }

                document.addEventListener('DOMContentLoaded', initGpsMap);
                if (document.readyState !== 'loading') {
                    initGpsMap();
                }
            </script>
        @else
            <div class="text-center text-gray-500 py-8">
                Seleccioná un dispositivo para ver el mapa.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Mapa de Rastreo GPS en Tiempo Real
        </x-slot>

        <x-slot name="description">
            Seleccioná un dispositivo para ver su recorrido en vivo.
        </x-slot>

        {{ $this->form }}

        @if($this->deviceId)
            <div wire:ignore>
                <div id="gps-map" style="height: 500px; width: 100%; border-radius: 0.5rem; margin-top: 1rem;"></div>
            </div>

            <script>
                function initGpsMap() {
                    if (window.__gpsMapInitialized) return;
                    window.__gpsMapInitialized = true;

                    import('/resources/js/widgets/gps-live-map.js').then(module => {
                        const GpsLiveMap = module.default;
                        const map = new GpsLiveMap('gps-map', {
                            deviceId: '{{ $this->deviceId }}',
                            tenantId: '{{ $this->tenantId }}',
                        });

                        const initialPoints = @json($this->initialPoints);
                        if (initialPoints.length > 0) {
                            map.loadInitialPoints(initialPoints);
                        }
                    }).catch(err => console.error('Error loading GPS map:', err));
                }

                document.addEventListener('DOMContentLoaded', initGpsMap);
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initGpsMap);
                } else {
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

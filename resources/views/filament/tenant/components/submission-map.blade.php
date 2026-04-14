@props([
    'latitude',
    'longitude',
])

<div
    x-data="{
        map: null,
        marker: null,
        init() {
            // Check if Leaflet is already loaded; if not, load it dynamically
            if (typeof L === 'undefined') {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(link);

                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = () => this.initMap();
                document.head.appendChild(script);
            } else {
                this.initMap();
            }
        },
        initMap() {
            const lat = {{ $latitude }};
            const lng = {{ $longitude }};

            this.map = L.map(this.$refs.map).setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href=\'https://www.openstreetmap.org/copyright\'>OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            const navSvg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='currentColor'><path d='M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z' /></svg>`;

            const customIcon = L.divIcon({
                className: '',
                html: `<span class='gps-minimap-marker'><span class='gps-minimap-marker__pulse'></span><span class='gps-minimap-marker__icon'>${navSvg}</span></span>`,
                iconSize: [48, 48],
                iconAnchor: [24, 24],
            });

            this.marker = L.marker([lat, lng], { icon: customIcon }).addTo(this.map);

            // Fix for map inside modal (sometimes doesn't render fully until resized)
            setTimeout(() => {
                this.map.invalidateSize();
            }, 300);

            // Additional fix for Livewire/Alpine lifecycle
            $watch('$el', () => {
                if (this.map) {
                    setTimeout(() => {
                        this.map.invalidateSize();
                    }, 100);
                }
            });
        }
    }"
    class="w-full"
>
    <style>
        .gps-minimap-marker {
            position: relative;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gps-minimap-marker__icon {
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

        .gps-minimap-marker__icon svg {
            width: 18px;
            height: 18px;
            color: #10b981; /* Acento esmeralda */
        }

        .gps-minimap-marker__pulse {
            position: absolute;
            inset: 2px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.25);
            border: 1px solid rgba(16, 185, 129, 0.4);
            animation: gps-minimap-pulse 2s ease-out infinite;
        }

        @keyframes gps-minimap-pulse {
            0% { transform: scale(0.9); opacity: 0.85; }
            100% { transform: scale(1.8); opacity: 0; }
        }
        
        .leaflet-container {
            z-index: 1 !important;
        }
    </style>

    <div
        x-ref="map"
        style="min-height: 400px; z-index: 10;"
        class="w-full h-96 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden"
    ></div>
</div>
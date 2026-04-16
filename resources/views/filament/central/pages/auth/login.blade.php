{{-- Custom Login view for Central Panel --}}
{{-- Uses <x-filament-panels::page.simple> wrapper (Filament v5 native) --}}
{{-- Layout: filament-panels::components.layout.simple (default from SimplePage) --}}

<x-filament-panels::page.simple class="drrx-login-wrapper">
    {{-- Assets via @push (rendered in <head> and end of <body> by layout.base) --}}
    @push('styles')
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="/css/filament/central-login.css">
    @endpush
    @push('scripts')
        <script src="/js/filament/central-login-canvas.js" defer></script>
    @endpush

    {{-- Layout split horizontal --}}
    <div class="drrx-split-layout">

        {{-- LADO IZQUIERDO: Brand --}}
        <aside class="drrx-brand-side">
            <canvas id="drrxMapCanvas"></canvas>
            <div class="drrx-brand-content">
                {{-- Logo PNG --}}
                <div class="drrx-logo-image-wrapper">
                    <img src="/images/logo-3x.png" alt="DR RouteX" class="drrx-logo-image">
                </div>

                <div class="drrx-live-stats">
                    <div class="drrx-stat-item">
                        <span class="drrx-stat-value" id="drrxStatVehicles">247</span>
                        <span class="drrx-stat-label"><span class="drrx-stat-dot green"></span>PERSONAS</span>
                    </div>
                    <div class="drrx-stat-item">
                        <span class="drrx-stat-value" id="drrxStatRoutes">89</span>
                        <span class="drrx-stat-label"><span class="drrx-stat-dot amber"></span>REPARTO</span>
                    </div>
                    <div class="drrx-stat-item">
                        <span class="drrx-stat-value" id="drrxStatAlerts">3</span>
                        <span class="drrx-stat-label"><span class="drrx-stat-dot blue"></span>LOGISTA</span>
                    </div>
                </div>
            </div>
            <div class="drrx-route-line" aria-hidden="true"></div>
        </aside>

        {{-- LADO DERECHO: Form Filament --}}
        <main class="drrx-form-side">
            <div class="drrx-form-wrapper">
                <div class="drrx-form-header">
                    <p class="drrx-welcome">Bienvenido de vuelta</p>
                    <h2 class="drrx-form-title">Inicia sesión en tu cuenta</h2>
                    <p class="drrx-form-subtitle">Ingresa tus credenciales para acceder al panel de monitoreo</p>
                </div>

                {{-- FORM FILAMENT NATIVO (incluye form + multi-factor + actions) --}}
                {{ $this->content }}
            </div>
        </main>
    </div>
</x-filament-panels::page.simple>

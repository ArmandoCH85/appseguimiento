<x-filament-panels::page.simple>
    <div class="drrx-2fa-container">
        
        {{-- Icono de seguridad y Título --}}
        <div class="drrx-2fa-header">
            <div class="drrx-2fa-icon-wrapper">
                <svg class="drrx-2fa-icon" xmlns="http://www.w3.org/validator" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            
            <h2 class="drrx-2fa-title">
                Verificación en dos pasos
            </h2>
            <p class="drrx-2fa-subtitle">
                Hemos enviado un código de 6 dígitos a tu correo electrónico. Por favor, ingrésalo a continuación.
            </p>
        </div>

        {{-- Formulario principal --}}
        <form wire:submit="verify" class="drrx-2fa-form">
            
            {{-- Input (Ahora usa la configuración PIN definida en Livewire) --}}
            {{ $this->form }}

            {{-- Botón Verificar --}}
            <x-filament::button type="submit" class="drrx-btn-verify" size="lg">
                Verificar código
            </x-filament::button>
        </form>

        {{-- Acciones secundarias (UX Escapes) --}}
        <div class="drrx-2fa-actions">
            
            {{-- Reenviar código (con loading state para evitar clics múltiples) --}}
            <button 
                type="button" 
                wire:click="resendOtp" 
                wire:loading.attr="disabled"
                class="drrx-action-link"
            >
                <span wire:loading.remove wire:target="resendOtp">¿No recibiste el código? Reenviar</span>
                <span wire:loading wire:target="resendOtp">Reenviando...</span>
            </button>

            {{-- Cancelar y volver --}}
            <button 
                type="button" 
                wire:click="cancelLogin" 
                class="drrx-action-link drrx-action-cancel"
            >
                Cancelar y volver al inicio de sesión
            </button>
        </div>

    </div>

    {{-- Estilos personalizados inyectados solo para esta página --}}
    @push('styles')
    <style>
        .drrx-2fa-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 24rem;
            margin: 0 auto;
            gap: 2rem;
            padding: 1rem;
        }

        .drrx-2fa-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.75rem;
        }

        .drrx-2fa-icon-wrapper {
            padding: 0.75rem;
            background-color: rgba(99, 102, 241, 0.1); /* Indigo claro */
            border-radius: 9999px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .drrx-2fa-icon {
            width: 2rem;
            height: 2rem;
            color: #4f46e5; /* Indigo 600 */
        }

        .drrx-2fa-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            color: #111827;
            margin: 0;
        }

        .drrx-2fa-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.5;
            margin: 0;
        }

        .drrx-2fa-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .drrx-btn-verify {
            width: 100%;
            height: 2.75rem;
            font-size: 1rem;
            font-weight: 500;
        }

        .drrx-2fa-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            padding-top: 1.5rem;
            width: 100%;
            border-top: 1px solid #e5e7eb;
        }

        .drrx-action-link {
            font-size: 0.875rem;
            font-weight: 500;
            color: #4f46e5;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s ease;
            padding: 0;
        }

        .drrx-action-link:hover {
            color: #4338ca;
            text-decoration: underline;
        }

        .drrx-action-link:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            text-decoration: none;
        }

        .drrx-action-cancel {
            color: #6b7280;
        }

        .drrx-action-cancel:hover {
            color: #374151;
        }

        /* Input PIN Styling */
        .fi-fo-field-wrp-label { display: none !important; }
        
        input[name="data.otp_code"] {
            text-align: center !important;
            font-size: 1.5rem !important;
            letter-spacing: 0.5em !important;
            padding-left: 0.5em !important; 
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
            font-weight: 600 !important;
            height: 3.5rem !important;
            border-radius: 0.5rem !important;
        }
        
        input[name="data.otp_code"]::placeholder {
            letter-spacing: 0.5em !important;
            color: #d1d5db !important;
            font-weight: 400 !important;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .drrx-2fa-title { color: #f9fafb; }
            .drrx-2fa-subtitle { color: #9ca3af; }
            .drrx-2fa-actions { border-top-color: rgba(255,255,255,0.1); }
            .drrx-action-link { color: #818cf8; }
            .drrx-action-link:hover { color: #a5b4fc; }
            .drrx-action-cancel { color: #9ca3af; }
            .drrx-action-cancel:hover { color: #f3f4f6; }
        }
    </style>
    @endpush
</x-filament-panels::page.simple>
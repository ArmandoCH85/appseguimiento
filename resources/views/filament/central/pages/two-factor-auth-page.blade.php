<x-filament-panels::page.simple>
    <div class="flex flex-col items-center justify-center w-full max-w-sm mx-auto space-y-8">
        
        {{-- Icono de seguridad y Título --}}
        <div class="flex flex-col items-center text-center space-y-3">
            <div class="p-3 bg-primary-50 dark:bg-primary-500/10 rounded-full">
                <svg class="w-8 h-8 text-primary-600 dark:text-primary-500" xmlns="http://www.w3.org/validator" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            
            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Verificación en dos pasos
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Hemos enviado un código de 6 dígitos a tu correo electrónico. Por favor, ingrésalo a continuación.
            </p>
        </div>

        {{-- Formulario principal --}}
        <form wire:submit="verify" class="w-full space-y-6">
            
            {{-- Input (Ahora usa la configuración PIN definida en Livewire) --}}
            {{ $this->form }}

            {{-- Botón Verificar --}}
            <x-filament::button type="submit" class="w-full h-11 text-base font-medium" size="lg">
                Verificar código
            </x-filament::button>
        </form>

        {{-- Acciones secundarias (UX Escapes) --}}
        <div class="flex flex-col items-center space-y-4 pt-4 w-full border-t border-gray-200 dark:border-white/10">
            
            {{-- Reenviar código (con loading state para evitar clics múltiples) --}}
            <button 
                type="button" 
                wire:click="resendOtp" 
                wire:loading.attr="disabled"
                class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="resendOtp">¿No recibiste el código? Reenviar</span>
                <span wire:loading wire:target="resendOtp">Reenviando...</span>
            </button>

            {{-- Cancelar y volver --}}
            <button 
                type="button" 
                wire:click="cancelLogin" 
                class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
            >
                Cancelar y volver al inicio de sesión
            </button>
        </div>

    </div>

    {{-- Estilos personalizados inyectados solo para esta página --}}
    @push('styles')
    <style>
        /* Ocultamos los labels del form nativo de filament para que el PIN resalte más */
        .fi-fo-field-wrp-label { display: none !important; }
        
        /* Ajuste visual del input para que parezca un teclado de PIN */
        input[name="data.otp_code"] {
            text-align: center !important;
            font-size: 2rem !important;
            letter-spacing: 0.5em !important;
            padding-left: 0.5em !important; /* Compensa el tracking para que quede 100% centrado */
            font-family: monospace !important;
            font-weight: 600 !important;
            height: 4rem !important;
        }
        
        /* Placeholder styling */
        input[name="data.otp_code"]::placeholder {
            letter-spacing: 0.5em !important;
            color: #9ca3af !important;
            font-weight: 400 !important;
        }
    </style>
    @endpush
</x-filament-panels::page.simple>
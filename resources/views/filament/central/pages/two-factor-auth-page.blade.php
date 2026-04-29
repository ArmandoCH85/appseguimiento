<x-filament-panels::page.simple>
    <div class="space-y-6">
        <div class="text-center">
            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Autenticación en dos pasos
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Por favor, ingresa el código que hemos enviado a tu correo.
            </p>
        </div>

        <form wire:submit="verify" class="space-y-6">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full">
                Verificar código
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page.simple>

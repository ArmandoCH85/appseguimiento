<?php

namespace App\Filament\Tenant\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class Login extends BaseLogin
{
    public ?string $turnstileToken = null;

    protected string $view = 'filament.tenant.pages.auth.login';

    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        if (! $this->turnstileToken) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Por favor, espera a que el Captcha se resuelva o recarga la página.',
            ]);
        }

        $captcha = \Illuminate\Support\Facades\Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret_key'),
            'response' => $this->turnstileToken,
            'remoteip' => request()->ip(),
        ])->json();

        if (!($captcha['success'] ?? false)) {
            $this->dispatch('reset-captcha');
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'Captcha inválido o expirado. Por favor, intenta nuevamente.',
            ]);
        }

        try {
            return parent::authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('reset-captcha');
            throw $e;
        }
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo electrónico')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes([
                'class' => 'drrx-input',
                'placeholder' => 'usuario@droutex.com',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Contraseña')
            ->password()
            ->required()
            ->extraInputAttributes([
                'class' => 'drrx-input',
                'placeholder' => 'Ingresa tu contraseña',
            ]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Recordarme');
    }
}

<?php

namespace App\Filament\Central\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class Login extends BaseLogin
{
    // NO definir $layout — dejar que Filament use su layout default
    // (filament-panels::components.layout.simple via SimplePage)
    protected string $view = 'filament.central.pages.auth.login';

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

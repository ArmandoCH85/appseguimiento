<?php

namespace App\Filament\Central\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class TwoFactorAuthPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Autenticación 2FA';
    protected string $view = 'filament.central.pages.two-factor-auth-page';
    protected static ?string $slug = '2fa';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('otp_code')
                    ->label('Código OTP')
                    ->required()
                    ->length(6)
                    ->helperText('Ingresa el código de 6 dígitos enviado a tu correo.'),
            ])
            ->statePath('data');
    }

    public function verify(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        if ($user->otp_code !== $data['otp_code']) {
            Notification::make()
                ->title('Código OTP incorrecto.')
                ->danger()
                ->send();
            return;
        }

        if ($user->otp_expires_at && now()->isAfter($user->otp_expires_at)) {
            Notification::make()
                ->title('El código OTP ha expirado.')
                ->danger()
                ->send();
            return;
        }

        // OTP is valid
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        session(['2fa_passed' => true]);

        $this->redirect(Dashboard::getUrl());
    }

    public function getHeading(): string
    {
        return '';
    }

    public function hasLogo(): bool
    {
        return false;
    }
}

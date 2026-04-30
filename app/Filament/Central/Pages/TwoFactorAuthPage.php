<?php

namespace App\Filament\Central\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Pages\SimplePage;
use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Filament\Notifications\Notification;

class TwoFactorAuthPage extends SimplePage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Autenticación 2FA';
    protected string $view = 'filament.central.pages.two-factor-auth-page';
    protected static ?string $slug = '2fa';
    // Removemos protected static bool $shouldRegisterNavigation = false;
    // porque en Filament v3, las páginas que heredan de SimplePage y tienen esto
    // falso a veces no registran la ruta en el nombre esperado.
    // En SimplePage, la navegación lateral ya no existe, por lo que es seguro dejarlo en true (por defecto).

    public function getRouteName(): string
    {
        // Forzamos el nombre de la ruta para que coincida exactamente con lo que busca Ensure2FA
        return 'filament.' . filament()->getCurrentPanel()->getId() . '.pages.2fa';
    }

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
                    ->hiddenLabel()
                    ->required()
                    ->length(6)
                    ->placeholder('X X X X X X')
                    ->extraInputAttributes([
                        'class' => 'text-center text-3xl tracking-[1em] font-mono py-4',
                        'maxlength' => 6,
                        'pattern' => '[0-9]*',
                        'inputmode' => 'numeric',
                        'autocomplete' => 'one-time-code',
                    ]),
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

    public function resendOtp(): void
    {
        $user = Auth::user();
        
        // Prevent spamming
        if (session()->has('last_otp_sent') && now()->diffInSeconds(session('last_otp_sent')) < 60) {
            Notification::make()
                ->title('Por favor espera un momento.')
                ->body('Debes esperar 60 segundos antes de solicitar otro código.')
                ->warning()
                ->send();
            return;
        }

        $otp = (string) rand(100000, 999999);
        
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));
        session(['last_otp_sent' => now()]);

        Notification::make()
            ->title('Código reenviado')
            ->body('Hemos enviado un nuevo código de 6 dígitos a tu correo.')
            ->success()
            ->send();
    }

    public function cancelLogin(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        
        $this->redirect('/central/login');
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

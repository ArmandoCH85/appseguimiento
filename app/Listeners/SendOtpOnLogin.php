<?php

namespace App\Listeners;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class SendOtpOnLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof TenantUser) {
            return;
        }

        // Solo generar y enviar OTP si la sesión de 2FA no está aprobada y si no tiene un código vigente
        if (!session('2fa_passed', false)) {
            // Evitar envíos duplicados en la misma petición (Filament Login event duplicate trigger)
            if (session()->has('otp_sent_in_this_request')) {
                return;
            }

            if ($user->otp_code && $user->otp_expires_at && now()->isBefore($user->otp_expires_at)) {
                // Ya tiene un código válido, no generar uno nuevo para evitar spam
                return;
            }

            $otp = (string) rand(100000, 999999);

            $user->update([
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(5),
            ]);

            session()->flash('otp_sent_in_this_request', true);
            Mail::to($user->email)->send(new OtpMail($otp));
        }
    }
}

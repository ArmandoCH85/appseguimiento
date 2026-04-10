<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Models\Central\CentralUser;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CentralPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('central')
            ->path('central')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->authGuard('central')
            ->discoverResources(
                in: app_path('Filament/Central/Resources'),
                for: 'App\\Filament\\Central\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Central/Pages'),
                for: 'App\\Filament\\Central\\Pages'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Central/Widgets'),
                for: 'App\\Filament\\Central\\Widgets'
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // NOTE: NO tenancy middleware here — this is the central panel
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

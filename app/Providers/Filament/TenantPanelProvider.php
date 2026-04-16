<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\InitializeTenancyByDomainIfApplicable;
use App\Providers\Filament\Concerns\HasDrRouteBranding;
use Filament\Http\Middleware\Authenticate;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
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

class TenantPanelProvider extends PanelProvider
{
    use HasDrRouteBranding;

    public function panel(Panel $panel): Panel
    {
        return $this->applyDrRouteBranding($panel)
            ->id('tenant')
            ->path('app')
            ->login()
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(
                in: app_path('Filament/Tenant/Resources'),
                for: 'App\\Filament\\Tenant\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Tenant/Pages'),
                for: 'App\\Filament\\Tenant\\Pages'
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Tenant/Widgets'),
                for: 'App\\Filament\\Tenant\\Widgets'
            )
            ->middleware([
                InitializeTenancyByDomainIfApplicable::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => Blade::render('@vite(["resources/js/app.js"])')
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

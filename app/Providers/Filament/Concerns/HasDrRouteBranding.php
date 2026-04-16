<?php

declare(strict_types=1);

namespace App\Providers\Filament\Concerns;

use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;

trait HasDrRouteBranding
{
    protected function applyDrRouteBranding(Panel $panel): Panel
    {
        return $panel
            ->brandName('Doctor Security')
            ->brandLogo(asset('images/logo-dr.svg'))
            ->brandLogoHeight('4rem')
            ->darkModeBrandLogo(asset('images/logo-dr-dark.svg'))
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(
                    '<link rel="stylesheet" href="' . asset('css/filament/brand-pro.css') . '" data-navigate-track />'
                )
            );
    }
}


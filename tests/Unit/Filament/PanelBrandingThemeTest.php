<?php

declare(strict_types=1);

use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CentralPanelProvider;
use App\Providers\Filament\TenantPanelProvider;
use Filament\Panel;
use Filament\View\PanelsRenderHook;

dataset('filament panel providers', [
    'central' => [CentralPanelProvider::class],
    'admin' => [AdminPanelProvider::class],
    'tenant' => [TenantPanelProvider::class],
]);

it('configures the shared elegant branding theme for every panel', function (string $providerClass) {
    /** @var \Filament\PanelProvider $provider */
    $provider = new $providerClass(app());
    $panel = $provider->panel(Panel::make());
    $renderHooks = (fn (): array => $this->renderHooks)->call($panel);

    expect($panel->getBrandLogo())->toContain('images/logo-dr.svg')
        ->and($panel->getDarkModeBrandLogo())->toContain('images/logo-dr-dark.svg')
        ->and($panel->getBrandLogoHeight())->toBe('4rem')
        ->and($renderHooks)->toHaveKey(PanelsRenderHook::HEAD_END)
        ->and($renderHooks[PanelsRenderHook::HEAD_END][''])->toHaveCount(1);
})->with('filament panel providers');

it('uses transparent svg backgrounds for both light and dark brand assets', function () {
    $lightLogo = file_get_contents(base_path('public/images/logo-dr.svg'));
    $darkLogo = file_get_contents(base_path('public/images/logo-dr-dark.svg'));
    preg_match('/<path\s+fill="([^"]+)"\s+opacity="1\.000000"\s+stroke="none"/', $lightLogo, $lightMatch);
    preg_match('/<path\s+fill="([^"]+)"\s+opacity="1\.000000"\s+stroke="none"/', $darkLogo, $darkMatch);

    expect($lightLogo)->toContain('fill="none" opacity="1.000000" stroke="none"')
        ->and($lightMatch[1] ?? null)->toBe('none')
        ->and($darkMatch[1] ?? null)->toBe('none');
});

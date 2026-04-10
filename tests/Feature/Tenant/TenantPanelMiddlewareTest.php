<?php

declare(strict_types=1);

use App\Http\Middleware\InitializeTenancyByDomainIfApplicable;
use Illuminate\Session\Middleware\StartSession;

it('initializes tenancy before starting the tenant panel session middleware', function () {
    $route = app('router')->getRoutes()->getByName('filament.tenant.auth.login');

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain(InitializeTenancyByDomainIfApplicable::class);
    expect(array_search(InitializeTenancyByDomainIfApplicable::class, $middleware, true))
        ->toBeLessThan(array_search(StartSession::class, $middleware, true));
});

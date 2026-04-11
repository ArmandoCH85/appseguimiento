<?php

use App\Http\Middleware\RootRedirect;
use Illuminate\Http\Request;

it('redirects root to central on central domain', function () {
    config(['tenancy.central_domains' => ['amsolutions.lat']]);

    $middleware = new RootRedirect();
    $request = Request::create('http://amsolutions.lat/', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response('next');
    });

    expect($response->getStatusCode())->toBe(302)
        ->and($response->getTargetUrl())->toContain('/central');
});

it('redirects root to app on tenant subdomain', function () {
    config(['tenancy.central_domains' => ['amsolutions.lat']]);

    $middleware = new RootRedirect();
    $request = Request::create('http://demo.amsolutions.lat/', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response('next');
    });

    expect($response->getStatusCode())->toBe(302)
        ->and($response->getTargetUrl())->toContain('/app');
});

it('does not redirect non-root paths', function () {
    config(['tenancy.central_domains' => ['amsolutions.lat']]);

    $middleware = new RootRedirect();
    $request = Request::create('http://amsolutions.lat/some-other-path', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response('next');
    });

    expect($response->getStatusCode())->toBe(200);
}); 

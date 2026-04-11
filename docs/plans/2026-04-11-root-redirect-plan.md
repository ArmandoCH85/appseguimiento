# Root Redirect Middleware Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redirect root `/` to `/central` (central domain) or `/app` (tenant subdomain) based on host detection.

**Architecture:** Dedicated middleware `RootRedirect` registered as alias, applied to root route. Checks host against `tenancy.central_domains` config to determine redirect target.

**Tech Stack:** Laravel 13, stancl/tenancy v3, Pest PHP (tests)

---

## Task 1: Create RootRedirect Middleware with TDD

**Files:**
- Test: `tests/Feature/RootRedirectMiddlewareTest.php`
- Create: `app/Http/Middleware/RootRedirect.php`

**Step 1: Write failing test for central domain redirect**

```php
<?php

use App\Http\Middleware\RootRedirect;

it('redirects root to central on central domain', function () {
    config(['tenancy.central_domains' => ['amsolutions.lat']]);

    $response = $this->withHeader('Host', 'amsolutions.lat')
        ->get('/');

    $response->assertRedirect('/central');
});
```

**Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/RootRedirectMiddlewareTest.php --filter="redirects root to central"
```
Expected: FAIL - middleware not found or route returns 200

**Step 3: Create middleware class**

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RootRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->path() !== '/') {
            return $next($request);
        }

        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        if (in_array($host, $centralDomains, true)) {
            return redirect('/central', 302);
        }

        return redirect('/app', 302);
    }
}
```

**Step 4: Add test for tenant subdomain redirect**

```php
it('redirects root to app on tenant subdomain', function () {
    config(['tenancy.central_domains' => ['amsolutions.lat']]);

    $response = $this->withHeader('Host', 'demo.amsolutions.lat')
        ->get('/');

    $response->assertRedirect('/app');
});
```

**Step 5: Add test for non-root paths passthrough**

```php
it('does not redirect non-root paths', function () {
    config(['tenancy.central_domains' => ['amsolutions.lat']]);

    $response = $this->withHeader('Host', 'amsolutions.lat')
        ->get('/some-other-path');

    // Should not redirect (will 404 since route doesn't exist, but no redirect)
    $response->assertStatus(404);
});
```

**Step 6: Run all tests**

```bash
php artisan test tests/Feature/RootRedirectMiddlewareTest.php
```
Expected: All 3 tests pass

**Step 7: Commit**

```bash
git add app/Http/Middleware/RootRedirect.php tests/Feature/RootRedirectMiddlewareTest.php
git commit -m "feat: add RootRedirect middleware for central/tenant root paths"
```

---

## Task 2: Register Middleware and Apply to Root Route

**Files:**
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`

**Step 1: Register middleware alias in bootstrap/app.php**

Add to the `withMiddleware` callback:

```php
$middleware->alias([
    'root.redirect' => \App\Http\Middleware\RootRedirect::class,
]);
```

Full `bootstrap/app.php`:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \App\Http\Middleware\InitializeTenancyByDomainIfApplicable::class,
        ]);

        $middleware->alias([
            'root.redirect' => \App\Http\Middleware\RootRedirect::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

**Step 2: Update root route in routes/web.php**

Replace existing root route with:

```php
Route::get('/', function () {
    // Middleware handles redirect, this fallback should never be reached
})->middleware('root.redirect');
```

**Step 3: Remove old root route test**

The existing `tests/Feature/ExampleTest.php` expects 200 on `/`. Update it:

```php
it('redirects root based on domain', function () {
    // This is now tested in RootRedirectMiddlewareTest.php
})->skip('Redirect tested in RootRedirectMiddlewareTest');
```

Or simply update the test to expect redirect:

```php
it('redirects root', function () {
    $response = $this->get('/');
    $response->assertStatus(302);
});
```

**Step 4: Run full test suite**

```bash
php artisan test
```
Expected: All tests pass

**Step 5: Commit**

```bash
git add bootstrap/app.php routes/web.php tests/Feature/ExampleTest.php
git commit -m "wire RootRedirect middleware to bootstrap and routes"
```

---

## Task 3: Verify End-to-End Behavior

**Step 1: Test central domain redirect manually**

```bash
curl -I -H "Host: amsolutions.lat" http://localhost/
```
Expected: `HTTP/1.1 302 Found` + `Location: /central`

**Step 2: Test tenant subdomain redirect manually**

```bash
curl -I -H "Host: demo.amsolutions.lat" http://localhost/
```
Expected: `HTTP/1.1 302 Found` + `Location: /app`

**Step 3: Verify /central and /app are not redirected**

```bash
curl -I -H "Host: amsolutions.lat" http://localhost/central
curl -I -H "Host: demo.amsolutions.lat" http://localhost/app
```
Expected: Both return 200 (or their actual response, not redirect)

**Step 4: Final commit**

```bash
git status
git add -A
git commit -m "docs: add root redirect design and implementation"
```

---

## Summary

| Task | Files | Est Steps |
|------|-------|-----------|
| 1: Middleware + TDD | `RootRedirect.php`, `RootRedirectMiddlewareTest.php` | 7 |
| 2: Wire up | `bootstrap/app.php`, `routes/web.php`, `ExampleTest.php` | 5 |
| 3: Verify | Manual curl tests | 4 |

Total: 3 tasks, 16 steps, 2 commits minimum

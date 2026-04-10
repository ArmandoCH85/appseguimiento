<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantApiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(tenant() !== null, Response::HTTP_UNAUTHORIZED);
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);
        abort_unless($user->currentAccessToken() !== null, Response::HTTP_UNAUTHORIZED);
        abort_if(! $user->is_active, Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}

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

        if (tenant() === null) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'No autenticado o sesión expirada.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user instanceof User) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'No autenticado o sesión expirada.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->currentAccessToken() === null) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'No autenticado o sesión expirada.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->is_active) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_AUTHORIZED',
                    'message' => 'Usuario inactivo.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

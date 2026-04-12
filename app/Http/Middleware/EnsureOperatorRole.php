<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperatorRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('operator')) {
            return response()->json([
                'error' => [
                    'code' => 'ROLE_NOT_ALLOWED',
                    'message' => 'Solo los operadores pueden acceder a este recurso.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class Ensure2FA
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is not authenticated, let Filament handle the redirect to login
        if (!Auth::check()) {
            return $next($request);
        }

        // If user is authenticated but 2FA is not passed, redirect to 2FA page
        if (!session('2fa_passed', false)) {
            // Avoid infinite loop if we are already on the 2FA page
            // En lugar de depender de routeIs que falla si la ruta no está bien descubierta, usamos Request::is
            if ($request->is('*/2fa') || $request->routeIs('filament.*.auth.logout')) {
                return $next($request);
            }
            
            // Redirect to the correct 2FA page based on the current panel using exact URL path
            $panelPath = filament()->getCurrentPanel()->getPath();
            return redirect('/' . ltrim($panelPath, '/') . '/2fa');
        }

        return $next($request);
    }
}

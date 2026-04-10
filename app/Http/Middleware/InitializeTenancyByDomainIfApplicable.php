<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inicializa tenancy por dominio SOLO si el host actual NO es un dominio central.
 *
 * Se usa en el middleware group 'web' global para que TODAS las rutas
 * (incluidas las de Livewire) pasen por inicialización de tenancy
 * cuando se accede desde un subdominio de tenant.
 */
class InitializeTenancyByDomainIfApplicable
{
    public function __construct(
        private InitializeTenancyByDomain $initializeTenancy,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (in_array($host, config('tenancy.central_domains', []), true)) {
            return $next($request);
        }

        return $this->initializeTenancy->handle($request, $next);
    }
}

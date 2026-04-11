# Diseño: Redirección de raíz por dominio/tenant

## Contexto
Proyecto multi-tenant con `stancl/tenancy` v3 en Laravel 13. Se requiere redirigir automáticamente la ruta raíz `/` a destinos distintos según el tipo de acceso:

- `amsolutions.lat/` → `/central` (panel super admin)
- `{tenant}.amsolutions.lat/` → `/app` (panel tenant)

## Aproximación
Middleware dedicado `RootRedirect` que detecta si la petición es a la ruta raíz y redirige según el host.

## Componentes

### Middleware: `app/Http/Middleware/RootRedirect.php`
- Verifica `$request->path() === '/'`
- Si no es `/`, pasa al siguiente middleware
- Obtiene `host` actual
- Compara contra `config('tenancy.central_domains')`
- Host en central_domains → redirect 302 a `/central`
- Host no en central_domains → redirect 302 a `/app` (tenant)

### Registro: `bootstrap/app.php`
- Alias de middleware: `root.redirect` → `RootRedirect::class`

### Ruta: `routes/web.php`
- Ruta `/` con middleware `root.redirect`
- Sin closure dummy — el middleware maneja la redirección completa

## Flujo

```
Request GET /
  → InitializeTenancyByDomainIfApplicable (ya prepended en web)
  → RootRedirect middleware
    → path === '/' ? 
      → host en central_domains? 
        → redirect /central
      → redirect /app (tenant)
    → next (si path !== '/')
```

## Consideraciones
- Redirect 302 (temporal) para evitar caching agresivo
- No afecta rutas como `/app`, `/central`, ni assets
- Compatible con tenancy existente — no modifica tenancy, solo detecta central vs tenant

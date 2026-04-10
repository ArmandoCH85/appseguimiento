# appseguimiento

Sistema Laravel multi-tenant para seguimiento en campo.

## Base de datos del proyecto

Este proyecto **usa MySQL** como base de datos principal.

### Desarrollo local

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=appseguimiento`
- `DB_USERNAME=root`
- `DB_PASSWORD=123456`

### Testing

- `phpunit.xml` usa `appseguimiento_testing` como base central de tests
- los tenants de `stancl/tenancy` se crean como bases MySQL separadas con prefijo `tenant`

## Notas

- No asumir SQLite para desarrollo ni para tests de tenancy
- `RefreshDatabase` resetea la base central, pero las bases tenant se limpian aparte en los tests

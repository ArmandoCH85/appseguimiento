# Tenant Users Create UX Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Exponer la creación de usuarios en `/app/users` y mejorar la UX del alta/edición tenant sin alterar permisos existentes.

**Architecture:** El cambio es puramente de capa Filament. Se mantiene `UserResource` como fuente de verdad, pero la página de listado mostrará `CreateAction`, y las páginas create/edit usarán copy y estructura más humanas. La lógica de acceso sigue gobernada por `UserPolicy`.

**Tech Stack:** Laravel 13, Filament 5, Pest PHP.

---

### Task 1: Cubrir el botón de creación en el listado

**Files:**
- Create: `C:\Users\arman\Herd\appseguimiento\tests\Unit\Filament\Tenant\ListUsersHeaderActionsTest.php`
- Modify: `C:\Users\arman\Herd\appseguimiento\app\Filament\Tenant\Resources\UserResource\Pages\ListUsers.php`

**Step 1: Write the failing test**
- Verificar que `ListUsers` exponga una header action llamada `create`.

**Step 2: Run test to verify it fails**
- `php artisan test tests/Unit/Filament/Tenant/ListUsersHeaderActionsTest.php`

**Step 3: Write minimal implementation**
- Agregar `CreateAction::make()->label('Crear usuario')` en `getHeaderActions()`.

**Step 4: Run test to verify it passes**
- `php artisan test tests/Unit/Filament/Tenant/ListUsersHeaderActionsTest.php`

### Task 2: Mejorar la UX de alta/edición

**Files:**
- Create: `C:\Users\arman\Herd\appseguimiento\tests\Unit\Filament\Tenant\CreateUserFlowTest.php`
- Modify: `C:\Users\arman\Herd\appseguimiento\app\Filament\Tenant\Resources\UserResource.php`
- Modify: `C:\Users\arman\Herd\appseguimiento\app\Filament\Tenant\Resources\UserResource\Pages\CreateUser.php`
- Modify: `C:\Users\arman\Herd\appseguimiento\app\Filament\Tenant\Resources\UserResource\Pages\EditUser.php`

**Step 1: Write the failing test**
- Verificar títulos y acciones amigables en create/edit.

**Step 2: Run test to verify it fails**
- `php artisan test tests/Unit/Filament/Tenant/CreateUserFlowTest.php`

**Step 3: Write minimal implementation**
- Reorganizar el formulario en secciones, helper texts y labels más claros.
- Agregar títulos/subtítulos y notificación de creación en español.

**Step 4: Run tests to verify they pass**
- `php artisan test tests/Unit/Filament/Tenant/ListUsersHeaderActionsTest.php tests/Unit/Filament/Tenant/CreateUserFlowTest.php`

### Task 3: Validación final

**Files:**
- Modify: `C:\Users\arman\Herd\appseguimiento\tests\Unit\Filament\Tenant\CreateUserFlowTest.php` (si hiciera falta ajustar)

**Step 1: Run relevant suite**
- `php artisan test tests/Unit/Filament/Tenant/ListUsersHeaderActionsTest.php tests/Unit/Filament/Tenant/CreateUserFlowTest.php`

**Step 2: Syntax check touched files**
- `php -l` sobre los archivos modificados.

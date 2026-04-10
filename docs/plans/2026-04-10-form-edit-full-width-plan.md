# Tenant Form Edit Full Width Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Convertir `app/forms/{record}/edit` en una pantalla tipo workspace full-width, con una composición 8/4 más clara y profesional.

**Architecture:** Se mantiene `EditForm` como página de metadata y `BuildForm` como constructor de preguntas. El cambio vive en la capa Filament: schema raíz explícito, sections con spans correctos, rail lateral informativo y acciones mejor jerarquizadas. No se toca la lógica de dominio ni persistencia.

**Tech Stack:** Laravel 13, Filament 5, Pest PHP.

---

### Task 1: Cubrir el layout objetivo con tests

**Files:**
- Create: `C:\Users\arman\Herd\appseguimiento\tests\Unit\Filament\Tenant\EditFormLayoutTest.php`
- Modify: `C:\Users\arman\Herd\appseguimiento\app\Filament\Tenant\Resources\FormResource.php`
- Modify: `C:\Users\arman\Herd\appseguimiento\app\Filament\Tenant\Resources\FormResource\Pages\EditForm.php`

**Step 1: Write the failing test**
- Verificar que la página `EditForm` exponga las acciones `builder`, `preview` y `index`.
- Verificar que el schema básico del recurso use layout full-span y secciones separadas para contenido principal y rail lateral.

**Step 2: Run test to verify it fails**
- `php artisan test tests/Unit/Filament/Tenant/EditFormLayoutTest.php`

**Step 3: Write minimal implementation**
- Crear schema workspace 8/4 en `FormResource`.
- Ajustar copy y header actions en `EditForm`.

**Step 4: Run test to verify it passes**
- `php artisan test tests/Unit/Filament/Tenant/EditFormLayoutTest.php`

### Task 2: Validar que no rompimos el flujo existente

**Files:**
- Verify existing: `C:\Users\arman\Herd\appseguimiento\tests\Unit\Filament\Tenant\CreateFormFlowTest.php`
- Verify existing: `C:\Users\arman\Herd\appseguimiento\tests\Unit\Filament\Tenant\FormResourceTableTest.php`

**Step 1: Run relevant suite**
- `php artisan test tests/Unit/Filament/Tenant/EditFormLayoutTest.php tests/Unit/Filament/Tenant/CreateFormFlowTest.php tests/Unit/Filament/Tenant/FormResourceTableTest.php`

**Step 2: Syntax check touched files**
- `php -l` sobre `FormResource.php` y `EditForm.php`
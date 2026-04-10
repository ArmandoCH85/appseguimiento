# Forms UX Option A Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Separar la creación/edición básica del formulario del constructor de campos para que el flujo sea entendible por usuarios no técnicos.

**Architecture:** El `FormResource` quedará dividido en dos experiencias: `create/edit` para metadatos del formulario y `builder` para preguntas/campos. El flujo de alta redirigirá automáticamente al constructor y el editor básico mostrará una acción explícita para abrirlo.

**Tech Stack:** Laravel 13, Filament 5, Pest, recursos tenant con stancl/tenancy.

---

### Task 1: Congelar el flujo UX esperado con tests

**Files:**
- Create: `tests/Unit/Filament/Tenant/CreateFormFlowTest.php`
- Modify: `tests/Unit/Filament/Tenant/ListFormsHeaderActionsTest.php`
- Modify: `tests/Unit/Filament/Tenant/FormResourceTableTest.php`

**Step 1: Write the failing tests**
- Verificar que `ListForms` expone acción `create`
- Verificar que `CreateForm` redirige a `builder` luego de crear
- Verificar que `EditForm` expone una acción para abrir el constructor
- Verificar que `BuildForm` tiene título amigable en español

**Step 2: Run tests to verify RED**

Run:
`php artisan test tests/Unit/Filament/Tenant/ListFormsHeaderActionsTest.php tests/Unit/Filament/Tenant/CreateFormFlowTest.php tests/Unit/Filament/Tenant/FormResourceTableTest.php`

Expected: failures for redirect/header actions/titles missing.

### Task 2: Separar metadatos de constructor

**Files:**
- Modify: `app/Filament/Tenant/Resources/FormResource.php`
- Modify: `app/Filament/Tenant/Resources/FormResource/Pages/CreateForm.php`
- Modify: `app/Filament/Tenant/Resources/FormResource/Pages/EditForm.php`
- Modify: `app/Filament/Tenant/Resources/FormResource/Pages/BuildForm.php`

**Step 1: Minimal implementation**
- Dejar `FormResource::form()` solo con datos básicos (`name`, `description`, `is_active`) dentro de secciones con helper text.
- Mover el constructor de campos a `BuildForm`, con copy claro, labels humanos y secciones.
- Agregar acción visible para abrir el constructor desde `EditForm`.
- Redirigir `CreateForm` al `builder` al crear.
- Localizar textos UX al español.

**Step 2: Run tests to verify GREEN**

Run:
`php artisan test tests/Unit/Filament/Tenant/ListFormsHeaderActionsTest.php tests/Unit/Filament/Tenant/CreateFormFlowTest.php tests/Unit/Filament/Tenant/FormResourceTableTest.php`

Expected: PASS.

### Task 3: Verificación final del flujo

**Files:**
- None (verification only)

**Step 1: Run focused suite**

Run:
`php artisan test tests/Unit/Filament/Tenant/ListFormsHeaderActionsTest.php tests/Unit/Filament/Tenant/CreateFormFlowTest.php tests/Unit/Filament/Tenant/FormResourceTableTest.php tests/Feature/Central/TenantProvisioningTest.php`

Expected: PASS.

**Step 2: Manual QA notes**
- `/app/forms` muestra `Crear formulario`
- Crear formulario redirige a constructor
- Editar formulario muestra acción para abrir constructor
- Constructor muestra copy y agrupación más clara para campos/opciones

<?php

declare(strict_types=1);

use App\Filament\Central\Resources\TenantResource;
use App\Filament\Central\Resources\TenantResource\Pages\CreateTenant;
use App\Filament\Central\Resources\TenantResource\Pages\EditTenant;
use App\Filament\Central\Resources\TenantResource\Pages\ListTenants;
use App\Models\Central\CentralUser;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('central'));
});

afterEach(fn () => dropCurrentTestTenantDatabases());

// ──────────────────────────────────────────────
// Listado
// ──────────────────────────────────────────────

it('renders the tenant list page for a super admin', function () {
    $admin = CentralUser::factory()->superAdmin()->create();
    actingAs($admin, 'central');

    Livewire::test(ListTenants::class)->assertStatus(200);
});

// ──────────────────────────────────────────────
// Creación via formulario Filament
// ──────────────────────────────────────────────

it('creates a tenant and assembles the full domain from subdomain + base domain', function () {
    $admin = CentralUser::factory()->superAdmin()->create();
    actingAs($admin, 'central');

    $baseDomain = TenantResource::getBaseDomain(); // e.g. "appseguimiento.test"

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name'           => 'Test Company',
            'slug'           => 'test-company',
            'subdomain'      => 'test-company',
            'admin_email'    => 'admin@test-company.test',
            'admin_password' => 'secret123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tenant = Tenant::query()->where('slug', 'test-company')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->name)->toBe('Test Company')
        ->and($tenant->domains->first()->domain)->toBe("test-company.{$baseDomain}");

    $tenantKey = $tenant->getTenantKey();
    expect(DB::select("SHOW DATABASES LIKE 'tenant{$tenantKey}'"))->not->toBeEmpty();

    $tenant->run(function () {
        $user = TenantUser::where('email', 'admin@test-company.test')->first();
        expect($user)->not->toBeNull()
            ->and($user->is_active)->toBeTrue()
            ->and($user->hasRole('admin'))->toBeTrue();
    });
});

it('provisions all roles inside the tenant database on tenant creation', function () {
    $admin = CentralUser::factory()->superAdmin()->create();
    actingAs($admin, 'central');

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name'           => 'Roles Company',
            'slug'           => 'roles-company',
            'subdomain'      => 'roles-company',
            'admin_email'    => 'admin@roles-company.test',
            'admin_password' => 'secret123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tenant = Tenant::query()->where('slug', 'roles-company')->first();

    $tenant->run(function () {
        $roles = Role::pluck('name')->toArray();
        expect($roles)->toContain('admin')
            ->toContain('supervisor')
            ->toContain('operator');
    });
});

// ──────────────────────────────────────────────
// Validaciones del formulario
// ──────────────────────────────────────────────

it('fails validation when admin email is missing', function () {
    $admin = CentralUser::factory()->superAdmin()->create();
    actingAs($admin, 'central');

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name'           => 'Missing Email',
            'slug'           => 'missing-email',
            'subdomain'      => 'missing-email',
            'admin_email'    => '',
            'admin_password' => 'secret123',
        ])
        ->call('create')
        ->assertHasFormErrors(['admin_email' => 'required']);

    expect(Tenant::count())->toBe(0);
});

it('fails validation when admin password is too short', function () {
    $admin = CentralUser::factory()->superAdmin()->create();
    actingAs($admin, 'central');

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name'           => 'Short Pass',
            'slug'           => 'short-pass',
            'subdomain'      => 'short-pass',
            'admin_email'    => 'admin@short-pass.test',
            'admin_password' => '123',
        ])
        ->call('create')
        ->assertHasFormErrors(['admin_password' => 'min']);

    expect(Tenant::count())->toBe(0);
});

it('fails validation when subdomain is already taken', function () {
    $admin = CentralUser::factory()->superAdmin()->create();
    actingAs($admin, 'central');

    // Primer tenant — debe crearse bien
    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name'           => 'First Tenant',
            'slug'           => 'first-tenant',
            'subdomain'      => 'shared',
            'admin_email'    => 'admin@first.test',
            'admin_password' => 'secret123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Segundo tenant con el mismo subdominio — debe fallar en el campo subdomain
    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name'           => 'Second Tenant',
            'slug'           => 'second-tenant',
            'subdomain'      => 'shared',
            'admin_email'    => 'admin@second.test',
            'admin_password' => 'secret123',
        ])
        ->call('create')
        ->assertHasFormErrors(['subdomain']);

    expect(Tenant::count())->toBe(1);
});

// ──────────────────────────────────────────────
// Edición via formulario Filament
// ──────────────────────────────────────────────

it('shows the current domain as read-only in the edit form', function () {
    $admin = CentralUser::factory()->superAdmin()->create();
    actingAs($admin, 'central');

    $baseDomain = TenantResource::getBaseDomain();

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name'           => 'Edit Me Corp',
            'slug'           => 'edit-me',
            'subdomain'      => 'edit-me',
            'admin_email'    => 'admin@edit-me.test',
            'admin_password' => 'secret123',
        ])
        ->call('create');

    $tenant = Tenant::query()->where('slug', 'edit-me')->first();

    // El dominio ensamblado debe ser correcto
    expect($tenant->primary_domain)->toBe("edit-me.{$baseDomain}");

    // El form de edición debe cargar sin errores (el dominio se muestra como Placeholder)
    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->assertStatus(200);
});

it('updates the tenant name via the edit form', function () {
    $admin = CentralUser::factory()->superAdmin()->create();
    actingAs($admin, 'central');

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name'           => 'Original Name',
            'slug'           => 'original-name',
            'subdomain'      => 'original-name',
            'admin_email'    => 'admin@original.test',
            'admin_password' => 'secret123',
        ])
        ->call('create');

    $tenant = Tenant::query()->where('slug', 'original-name')->first();

    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tenant->fresh()->name)->toBe('Updated Name');
});

it('domain cannot be changed after tenant creation', function () {
    $admin = CentralUser::factory()->superAdmin()->create();
    actingAs($admin, 'central');

    $baseDomain = TenantResource::getBaseDomain();

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name'           => 'Stable Domain',
            'slug'           => 'stable-domain',
            'subdomain'      => 'stable-domain',
            'admin_email'    => 'admin@stable.test',
            'admin_password' => 'secret123',
        ])
        ->call('create');

    $tenant = Tenant::query()->where('slug', 'stable-domain')->first();
    $originalDomain = $tenant->primary_domain;

    // El form de edición no tiene campo primary_domain editable — el dominio no cambia
    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->fillForm(['name' => 'Stable Domain Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tenant->fresh()->primary_domain)->toBe($originalDomain)
        ->and($tenant->fresh()->primary_domain)->toBe("stable-domain.{$baseDomain}");
});

<?php

declare(strict_types=1);

use App\Actions\Tenant\CreateTenantAction;
use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// Clean up stancl-created MySQL tenant databases after each test.
// stancl creates real MySQL databases (e.g. tenantacme) — RefreshDatabase
// only rolls back the central DB. We must DROP tenant DBs manually.
afterEach(fn () => dropCurrentTestTenantDatabases());

// Task 1.12 — RED→GREEN: Assert DB is provisioned + migrations ran on Tenant::create()
// Spec: "Create tenant" scenario

it('provisions a tenant database when a tenant is created', function () {
    $tenant = Tenant::create([
        'id'   => 'acme',
        'name' => 'Acme Corp',
        'slug' => 'acme',
    ]);

    // Assert the tenant record was saved to central DB
    expect(Tenant::find('acme'))->not->toBeNull();

    // Assert the MySQL tenant database was created by stancl
    // Note: SHOW DATABASES LIKE does not support PDO bound params — use string interpolation
    $tenantKey = $tenant->getTenantKey();
    $databases = DB::select("SHOW DATABASES LIKE 'tenant{$tenantKey}'");
    expect($databases)->not->toBeEmpty();
});

it('creates a tenant with a primary domain and provisions its database', function () {
    $tenant = app(CreateTenantAction::class)->execute([
        'name'           => 'Acme Domain',
        'slug'           => 'acme-domain',
        'primary_domain' => 'acme-domain.test',
        'admin_email'    => 'admin@acme-domain.test',
        'admin_password' => 'password123',
    ]);

    expect($tenant->domains)->toHaveCount(1)
        ->and($tenant->domains->first()->domain)->toBe('acme-domain.test')
        ->and(DB::select("SHOW DATABASES LIKE 'tenant{$tenant->getTenantKey()}'"))->not->toBeEmpty();
});

it('runs tenant migrations after database provisioning', function () {
    $tenant = Tenant::create([
        'id'   => 'beta',
        'name' => 'Beta Inc',
        'slug' => 'beta',
    ]);

    // Switch to tenant context and verify migrations ran
    $tenant->run(function () {
        $tables = DB::select('SHOW TABLES');
        expect($tables)->not->toBeEmpty();
    });
});

it('creates tenant runtime infrastructure tables required by database session and cache drivers', function () {
    $tenant = Tenant::create([
        'id'   => 'infra',
        'name' => 'Infra Corp',
        'slug' => 'infra',
    ]);

    $tenant->run(function () {
        expect(DB::select("SHOW TABLES LIKE 'sessions'"))->not->toBeEmpty()
            ->and(DB::select("SHOW TABLES LIKE 'cache'"))->not->toBeEmpty()
            ->and(DB::select("SHOW TABLES LIKE 'cache_locks'"))->not->toBeEmpty();
    });
});

it('stores tenant session user ids in a ULID-compatible column', function () {
    $tenant = Tenant::create([
        'id'   => 'session-shape',
        'name' => 'Session Shape Corp',
        'slug' => 'session-shape',
    ]);

    $tenant->run(function () {
        $column = collect(DB::select("SHOW COLUMNS FROM `sessions` LIKE 'user_id'"))->first();

        expect($column)->not->toBeNull()
            ->and(strtolower($column->Type))->toContain('varchar');
    });
});

// Task 1.14 — RED→GREEN: Duplicate slug returns unique constraint error, no extra DB created
// Spec: "Duplicate domain rejected" scenario

it('rejects a duplicate id when creating a tenant', function () {
    Tenant::create([
        'id'   => 'first',
        'name' => 'First Tenant',
        'slug' => 'first',
    ]);

    $countBefore = Tenant::count();

    expect(fn () => Tenant::create([
        'id'   => 'first', // same PK — stancl uses string id
        'name' => 'Duplicate',
        'slug' => 'first-dup',
    ]))->toThrow(\Illuminate\Database\QueryException::class);

    expect(Tenant::count())->toBe($countBefore);
});

it('does not provision an extra database when tenant creation fails due to duplicate id', function () {
    Tenant::create([
        'id'   => 'original',
        'name' => 'Original',
        'slug' => 'original',
    ]);

    try {
        Tenant::create([
            'id'   => 'original',
            'name' => 'Duplicate',
            'slug' => 'original-dup',
        ]);
    } catch (\Throwable) {
        // Expected — duplicate PK
    }

    // Only one tenant DB should exist
    $databases = DB::select('SHOW DATABASES LIKE "tenantoriginal%"');
    expect(count($databases))->toBe(1);

    expect(Tenant::count())->toBe(1);
});

it('rejects a duplicate domain and does not provision an extra database', function () {
    app(CreateTenantAction::class)->execute([
        'name'           => 'Tenant One',
        'slug'           => 'tenant-one',
        'primary_domain' => 'tenant-one.test',
        'admin_email'    => 'admin@tenant-one.test',
        'admin_password' => 'password123',
    ]);

    $dbCountBefore = count(DB::select('SHOW DATABASES LIKE "tenant%"'));

    expect(fn () => app(CreateTenantAction::class)->execute([
        'name'           => 'Tenant Two',
        'slug'           => 'tenant-two',
        'primary_domain' => 'tenant-one.test',
        'admin_email'    => 'admin@tenant-two.test',
        'admin_password' => 'password123',
    ]))->toThrow(ValidationException::class);

    expect(Tenant::count())->toBe(1)
        ->and(Domain::count())->toBe(1)
        ->and(count(DB::select('SHOW DATABASES LIKE "tenant%"')))->toBe($dbCountBefore);
});

it('soft deletes a tenant and preserves its database', function () {
    $tenant = app(CreateTenantAction::class)->execute([
        'name'           => 'Delete Me',
        'slug'           => 'delete-me',
        'primary_domain' => 'delete-me.test',
        'admin_email'    => 'admin@delete-me.test',
        'admin_password' => 'password123',
    ]);

    $tenant->delete();

    expect(Tenant::query()->find($tenant->getKey()))->toBeNull()
        ->and(Tenant::withTrashed()->find($tenant->getKey()))->not->toBeNull()
        ->and(Tenant::withTrashed()->find($tenant->getKey())->trashed())->toBeTrue()
        ->and(DB::select('SHOW DATABASES LIKE "tenantdelete-me"'))->not->toBeEmpty();
});

it('prohibits hard deletion of tenants', function () {
    $tenant = app(CreateTenantAction::class)->execute([
        'name'           => 'No Force Delete',
        'slug'           => 'no-force-delete',
        'primary_domain' => 'no-force-delete.test',
        'admin_email'    => 'admin@no-force-delete.test',
        'admin_password' => 'password123',
    ]);

    expect(fn () => $tenant->forceDelete())
        ->toThrow(LogicException::class);
});

<?php

declare(strict_types=1);

use App\Models\Central\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(fn () => dropCurrentTestTenantDatabases());

function createTenantUser(Tenant $tenant, string $role): array
{
    return $tenant->run(function () use ($role) {
        $userClass = \App\Models\Tenant\User::class;

        $roleModel = Role::findOrCreate($role, 'web');

        $user = $userClass::query()->create([
            'name' => ucfirst($role).' User',
            'email' => "{$role}@example.com",
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole($roleModel);

        return [
            'id' => (string) $user->getKey(),
            'email' => $user->email,
        ];
    });
}

it('returns a sanctum token for valid tenant credentials', function () {
    $tenant = Tenant::create([
        'id' => 'acme',
        'name' => 'Acme Corp',
        'slug' => 'acme',
    ]);

    $user = createTenantUser($tenant, 'operator');

    $this->postJson('/api/v1/acme/auth/login', [
        'email' => $user['email'],
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'role', 'assignments_count'],
        ]);
});

it('rejects a token issued by another tenant', function () {
    $tenantA = Tenant::create([
        'id' => 'alpha',
        'name' => 'Alpha Corp',
        'slug' => 'alpha',
    ]);

    $tenantB = Tenant::create([
        'id' => 'beta',
        'name' => 'Beta Corp',
        'slug' => 'beta',
    ]);

    $userA = createTenantUser($tenantA, 'operator');
    createTenantUser($tenantB, 'operator');

    $token = $this->postJson('/api/v1/alpha/auth/login', [
        'email' => $userA['email'],
        'password' => 'password',
    ])->json('token');

    $this->withToken($token)
        ->getJson('/api/v1/beta/me')
        ->assertUnauthorized();
});

it('forbids operator access to admin-only endpoints', function () {
    $tenant = Tenant::create([
        'id' => 'gamma',
        'name' => 'Gamma Corp',
        'slug' => 'gamma',
    ]);

    $operator = createTenantUser($tenant, 'operator');

    // Create token directly since operator can't use login API
    $token = $tenant->run(function () use ($operator) {
        $userClass = \App\Models\Tenant\User::class;
        $user = $userClass::query()->find($operator['id']);
        return $user->createToken('mobile')->plainTextToken;
    });

    $this->withToken($token)
        ->getJson('/api/v1/gamma/admin/ping')
        ->assertForbidden();
});

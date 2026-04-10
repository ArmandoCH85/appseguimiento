<?php

declare(strict_types=1);

use App\Enums\FormFieldType;
use App\Models\Central\Tenant;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormField;
use App\Models\Tenant\User;
use App\Services\FormVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(fn () => dropCurrentTestTenantDatabases());

function createIsolationToken(Tenant $tenant): string
{
    return $tenant->run(function () {
        $role = Role::findOrCreate('operator', 'web');

        $user = User::query()->create([
            'name' => 'Isolation User',
            'email' => 'isolation@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user->createToken('mobile')->plainTextToken;
    });
}

function createIsolationVersion(Tenant $tenant): string
{
    return $tenant->run(function () {
        $form = Form::query()->create([
            'name' => 'Isolation Form',
            'description' => null,
            'is_active' => true,
        ]);

        FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Text,
            'label' => 'Nota',
            'name' => 'nota',
            'is_required' => true,
            'validation_rules' => [],
            'order' => 1,
            'is_active' => true,
        ]);

        return (string) app(FormVersionService::class)->publish($form)->getKey();
    });
}

it('rejects a tenant token used against another tenant submission endpoint', function () {
    $tenantA = Tenant::create([
        'id' => 'iso-a',
        'name' => 'ISO A',
        'slug' => 'iso-a',
    ]);

    $tenantB = Tenant::create([
        'id' => 'iso-b',
        'name' => 'ISO B',
        'slug' => 'iso-b',
    ]);

    $tokenA = createIsolationToken($tenantA);
    $versionB = createIsolationVersion($tenantB);

    $this->withToken($tokenA)
        ->postJson('/api/iso-b/submissions', [
            'form_version_id' => $versionB,
            'idempotency_key' => 'cross-tenant',
            'latitude' => -12.046374,
            'longitude' => -77.042793,
            'responses' => [
                'nota' => 'No debería pasar',
            ],
        ])
        ->assertUnauthorized();
});

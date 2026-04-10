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

function createTenantApiUser(Tenant $tenant): string
{
    return $tenant->run(function () {
        $role = Role::findOrCreate('admin', 'web');

        $user = User::query()->create([
            'name' => 'Tenant Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user->createToken('mobile')->plainTextToken;
    });
}

function createPublishedForm(Tenant $tenant, string $name, bool $isActive = true): array
{
    return $tenant->run(function () use ($name, $isActive) {
        $form = Form::query()->create([
            'name' => $name,
            'description' => "{$name} description",
            'is_active' => $isActive,
        ]);

        FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Text,
            'label' => 'Comentario',
            'name' => 'comentario',
            'is_required' => true,
            'validation_rules' => ['max:255'],
            'order' => 1,
            'is_active' => true,
        ]);

        $version = app(FormVersionService::class)->publish($form);

        return [
            'form_id' => (string) $form->id,
            'version_id' => (string) $version->id,
        ];
    });
}

it('lists active forms with their current schema snapshot', function () {
    $tenant = Tenant::create([
        'id' => 'forms-api-a',
        'name' => 'Forms API A',
        'slug' => 'forms-api-a',
    ]);

    $token = createTenantApiUser($tenant);

    createPublishedForm($tenant, 'Checklist A', true);
    createPublishedForm($tenant, 'Checklist B', false);

    $this->withToken($token)
        ->getJson('/api/forms-api-a/forms')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Checklist A')
        ->assertJsonPath('data.0.current_version.schema.0.name', 'comentario');
});

it('shows one form with the active version schema snapshot', function () {
    $tenant = Tenant::create([
        'id' => 'forms-api-b',
        'name' => 'Forms API B',
        'slug' => 'forms-api-b',
    ]);

    $token = createTenantApiUser($tenant);
    $published = createPublishedForm($tenant, 'Formulario detalle');

    $this->withToken($token)
        ->getJson("/api/forms-api-b/forms/{$published['form_id']}")
        ->assertOk()
        ->assertJsonPath('data.id', $published['form_id'])
        ->assertJsonPath('data.current_version.id', $published['version_id'])
        ->assertJsonPath('data.current_version.schema.0.label', 'Comentario');
});

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

function createSubmissionToken(Tenant $tenant): string
{
    return $tenant->run(function () {
        $role = Role::findOrCreate('admin', 'web');

        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user->createToken('mobile')->plainTextToken;
    });
}

function createSubmissionFormVersion(Tenant $tenant): string
{
    return $tenant->run(function () {
        $form = Form::query()->create([
            'name' => 'Checklist',
            'description' => 'Checklist diario',
            'is_active' => true,
        ]);

        FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Text,
            'label' => 'Observación',
            'name' => 'observacion',
            'is_required' => true,
            'validation_rules' => ['max:255'],
            'order' => 1,
            'is_active' => true,
        ]);

        return (string) app(FormVersionService::class)->publish($form)->getKey();
    });
}

it('creates a submission with server timestamp', function () {
    $tenant = Tenant::create([
        'id' => 'submission-a',
        'name' => 'Submission A',
        'slug' => 'submission-a',
    ]);

    $token = createSubmissionToken($tenant);
    $versionId = createSubmissionFormVersion($tenant);

    $response = $this->withToken($token)
        ->postJson('/api/v1/submission-a/submissions', [
            'form_version_id' => $versionId,
            'idempotency_key' => 'abc-123',
            'status' => 'pending_photos',
            'latitude' => -12.046374,
            'longitude' => -77.042793,
            'responses' => [
                'observacion' => 'Todo bien',
            ],
            'submitted_at' => '2000-01-01T00:00:00Z',
        ])
        ->assertCreated()
        ->assertJsonPath('data.idempotency_key', 'abc-123')
        ->assertJsonPath('data.responses.0.field_name', 'observacion')
        ->json('data');

    expect($response['submitted_at'])->not->toBe('2000-01-01T00:00:00Z');
});

it('rejects a submission without gps coordinates', function () {
    $tenant = Tenant::create([
        'id' => 'submission-b',
        'name' => 'Submission B',
        'slug' => 'submission-b',
    ]);

    $token = createSubmissionToken($tenant);
    $versionId = createSubmissionFormVersion($tenant);

    $this->withToken($token)
        ->postJson('/api/v1/submission-b/submissions', [
            'form_version_id' => $versionId,
            'idempotency_key' => 'missing-gps',
            'status' => 'pending_photos',
            'responses' => [
                'observacion' => 'Sin GPS',
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

it('returns the original submission when the idempotency key is reused', function () {
    $tenant = Tenant::create([
        'id' => 'submission-c',
        'name' => 'Submission C',
        'slug' => 'submission-c',
    ]);

    $token = createSubmissionToken($tenant);
    $versionId = createSubmissionFormVersion($tenant);

    $payload = [
        'form_version_id' => $versionId,
        'idempotency_key' => 'same-key',
        'status' => 'pending_photos',
        'latitude' => -12.046374,
        'longitude' => -77.042793,
        'responses' => [
            'observacion' => 'Primera',
        ],
    ];

    $first = $this->withToken($token)
        ->postJson('/api/v1/submission-c/submissions', $payload)
        ->assertCreated()
        ->json('data');

    $second = $this->withToken($token)
        ->postJson('/api/v1/submission-c/submissions', array_merge($payload, [
            'responses' => ['observacion' => 'Segunda'],
        ]))
        ->assertOk()
        ->json('data');

    expect($second['id'])->toBe($first['id'])
        ->and($second['responses'][0]['value'])->toBe('Primera');
});

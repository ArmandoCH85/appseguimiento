<?php

declare(strict_types=1);

use App\Enums\FormFieldType;
use App\Models\Central\Tenant;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormField;
use App\Models\Tenant\Submission;
use App\Models\Tenant\User;
use App\Services\FormVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(fn () => dropCurrentTestTenantDatabases());

function createFormFixture(Tenant $tenant): array
{
    return $tenant->run(function () {
        $form = Form::query()->create([
            'name' => 'Inspección inicial',
            'description' => 'Checklist de ingreso',
            'is_active' => true,
        ]);

        $textField = FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Text,
            'label' => 'Observaciones',
            'name' => 'observaciones',
            'is_required' => true,
            'validation_rules' => ['max:500'],
            'order' => 1,
            'is_active' => true,
        ]);

        $selectField = FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Select,
            'label' => 'Estado',
            'name' => 'estado',
            'is_required' => true,
            'validation_rules' => [],
            'order' => 2,
            'is_active' => true,
        ]);

        $selectField->options()->createMany([
            ['label' => 'Bien', 'value' => 'bien', 'order' => 1, 'is_active' => true],
            ['label' => 'Mal', 'value' => 'mal', 'order' => 2, 'is_active' => true],
        ]);

        return [
            'form_id' => (string) $form->id,
            'text_field_id' => (string) $textField->id,
        ];
    });
}

it('creates an immutable schema snapshot when publishing a form', function () {
    $tenant = Tenant::create([
        'id' => 'forms-a',
        'name' => 'Forms A',
        'slug' => 'forms-a',
    ]);

    $fixture = createFormFixture($tenant);

    $tenant->run(function () use ($fixture) {
        $form = Form::query()->findOrFail($fixture['form_id']);

        $version = app(FormVersionService::class)->publish($form);

        expect($version->version_number)->toBe(1)
            ->and($version->published_at)->not->toBeNull()
            ->and($form->fresh()->current_version_id)->toBe($version->id)
            ->and($version->schema_snapshot)->toHaveCount(2)
            ->and($version->schema_snapshot[0]['name'])->toBe('observaciones')
            ->and($version->schema_snapshot[1]['options'])->toHaveCount(2);
    });
});

it('creates a new version after edits and preserves the previous snapshot', function () {
    $tenant = Tenant::create([
        'id' => 'forms-b',
        'name' => 'Forms B',
        'slug' => 'forms-b',
    ]);

    $fixture = createFormFixture($tenant);

    $tenant->run(function () use ($fixture) {
        $form = Form::query()->findOrFail($fixture['form_id']);
        $service = app(FormVersionService::class);

        $firstVersion = $service->publish($form);

        $field = FormField::query()->findOrFail($fixture['text_field_id']);
        $field->update([
            'label' => 'Observaciones finales',
            'validation_rules' => ['max:1000'],
        ]);

        $secondVersion = $service->publish($form->fresh());

        expect($secondVersion->version_number)->toBe(2)
            ->and($secondVersion->id)->not->toBe($firstVersion->id)
            ->and($firstVersion->fresh()->schema_snapshot[0]['label'])->toBe('Observaciones')
            ->and($secondVersion->schema_snapshot[0]['label'])->toBe('Observaciones finales')
            ->and($firstVersion->fresh()->schema_snapshot[0]['validation_rules'])->toBe(['max:500'])
            ->and($secondVersion->schema_snapshot[0]['validation_rules'])->toBe(['max:1000']);
    });
});

it('soft deletes a form without affecting submissions linked to its published version', function () {
    $tenant = Tenant::create([
        'id' => 'forms-c',
        'name' => 'Forms C',
        'slug' => 'forms-c',
    ]);

    $fixture = createFormFixture($tenant);

    $tenant->run(function () use ($fixture) {
        $role = Role::findOrCreate('operator', 'web');

        $user = User::query()->create([
            'name' => 'Operator',
            'email' => 'operator@forms-c.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $form = Form::query()->findOrFail($fixture['form_id']);
        $version = app(FormVersionService::class)->publish($form);

        $submission = Submission::query()->create([
            'form_version_id' => $version->getKey(),
            'user_id' => $user->getKey(),
            'idempotency_key' => 'soft-delete-form',
            'latitude' => -12.046374,
            'longitude' => -77.042793,
            'status' => \App\Enums\SubmissionStatus::PendingPhotos,
            'submitted_at' => now(),
        ]);

        $form->delete();

        expect(Form::query()->find($form->getKey()))->toBeNull()
            ->and(Form::withTrashed()->find($form->getKey()))->not->toBeNull()
            ->and($submission->fresh())->not->toBeNull()
            ->and($submission->fresh()->formVersion->getKey())->toBe($version->getKey());
    });
});

<?php

declare(strict_types=1);

use App\Enums\FormFieldType;
use App\Models\Central\Tenant;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormField;
use App\Models\Tenant\User;
use App\Services\FormVersionService;
use App\Services\SubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

afterEach(fn () => dropCurrentTestTenantDatabases());

it('stores and retrieves field settings as JSON', function () {
    $tenant = Tenant::create([
        'id' => 'settings-a',
        'name' => 'Settings A',
        'slug' => 'settings-a',
    ]);

    $tenant->run(function () {
        $form = Form::query()->create([
            'name' => 'Form with settings',
            'description' => 'Testing settings per field type',
            'is_active' => true,
        ]);

        $numberField = FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Number,
            'label' => 'Edad',
            'name' => 'edad',
            'is_required' => true,
            'settings' => [
                'min_value' => 18,
                'max_value' => 99,
                'step' => 1,
                'unit' => 'años',
            ],
            'order' => 1,
            'is_active' => true,
        ]);

        $textField = FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Text,
            'label' => 'Nombre',
            'name' => 'nombre',
            'is_required' => false,
            'settings' => [
                'max_length' => 100,
                'placeholder' => 'Ingresá tu nombre...',
            ],
            'order' => 2,
            'is_active' => true,
        ]);

        $freshNumber = FormField::query()->find($numberField->id);
        $freshText = FormField::query()->find($textField->id);

        expect($freshNumber->settings)->toBeArray()
            ->and($freshNumber->settings['min_value'])->toBe(18)
            ->and($freshNumber->settings['max_value'])->toBe(99)
            ->and($freshNumber->settings['step'])->toBe(1)
            ->and($freshNumber->settings['unit'])->toBe('años')
            ->and($freshText->settings)->toBeArray()
            ->and($freshText->settings['max_length'])->toBe(100)
            ->and($freshText->settings['placeholder'])->toBe('Ingresá tu nombre...');
    });
});

it('includes settings in schema snapshot when publishing', function () {
    $tenant = Tenant::create([
        'id' => 'settings-b',
        'name' => 'Settings B',
        'slug' => 'settings-b',
    ]);

    $tenant->run(function () {
        $form = Form::query()->create([
            'name' => 'Snapshot settings',
            'is_active' => true,
        ]);

        FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Number,
            'label' => 'Peso',
            'name' => 'peso',
            'is_required' => false,
            'settings' => [
                'min_value' => 0,
                'max_value' => 300,
                'step' => 0.1,
                'unit' => 'kg',
            ],
            'order' => 1,
            'is_active' => true,
        ]);

        $version = app(FormVersionService::class)->publish($form->fresh());

        expect($version->schema_snapshot)->toHaveCount(1)
            ->and($version->schema_snapshot[0]['settings'])->toBeArray()
            ->and($version->schema_snapshot[0]['settings']['min_value'])->toBe(0)
            ->and($version->schema_snapshot[0]['settings']['max_value'])->toBe(300)
            ->and($version->schema_snapshot[0]['settings']['step'])->toBe(0.1)
            ->and($version->schema_snapshot[0]['settings']['unit'])->toBe('kg');
    });
});

it('validates checkbox submissions as array, not string', function () {
    $tenant = Tenant::create([
        'id' => 'settings-c',
        'name' => 'Settings C',
        'slug' => 'settings-c',
    ]);

    $tenant->run(function () {
        $form = Form::query()->create([
            'name' => 'Checkbox test',
            'is_active' => true,
        ]);

        $field = FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Checkbox,
            'label' => 'Colores',
            'name' => 'colores',
            'is_required' => true,
            'settings' => ['inline' => false],
            'order' => 1,
            'is_active' => true,
        ]);

        $field->options()->createMany([
            ['label' => 'Rojo', 'value' => 'rojo', 'order' => 1, 'is_active' => true],
            ['label' => 'Azul', 'value' => 'azul', 'order' => 2, 'is_active' => true],
            ['label' => 'Verde', 'value' => 'verde', 'order' => 3, 'is_active' => true],
        ]);

        $version = app(FormVersionService::class)->publish($form->fresh());

        $user = User::query()->create([
            'name' => 'Op',
            'email' => 'op@sc.test',
            'password' => Hash::make('pw'),
            'is_active' => true,
        ]);

        $service = app(SubmissionService::class);

        $result = $service->createOrRetrieve($version, $user, [
            'idempotency_key' => 'chk-1',
            'latitude' => 0,
            'longitude' => 0,
            'responses' => [
                'colores' => ['rojo', 'azul'],
            ],
        ]);

        expect($result)->not->toBeNull();

        $response = $result->responses()->where('field_name', 'colores')->first();
        expect($response)->not->toBeNull()
            ->and($response->field_type)->toBe('checkbox');

        $decoded = json_decode($response->value, true);
        expect($decoded)->toBe(['rojo', 'azul']);
    });
});

it('validates file type skips string rule in submission', function () {
    $tenant = Tenant::create([
        'id' => 'settings-d',
        'name' => 'Settings D',
        'slug' => 'settings-d',
    ]);

    $tenant->run(function () {
        $form = Form::query()->create([
            'name' => 'File test',
            'is_active' => true,
        ]);

        FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::File,
            'label' => 'Comprobante',
            'name' => 'comprobante',
            'is_required' => false,
            'settings' => [
                'accepted_file_types' => ['pdf', 'jpg'],
                'max_file_size' => 2048,
                'multiple_files' => false,
            ],
            'order' => 1,
            'is_active' => true,
        ]);

        $version = app(FormVersionService::class)->publish($form->fresh());

        $rules = app(SubmissionService::class)->validateResponses($version, []);

        // This should not throw - file type should skip 'string' rule
        expect($rules)->toBeArray();
    });
});

it('stores select field settings searchable and placeholder', function () {
    $tenant = Tenant::create([
        'id' => 'settings-e',
        'name' => 'Settings E',
        'slug' => 'settings-e',
    ]);

    $tenant->run(function () {
        $form = Form::query()->create([
            'name' => 'Select settings',
            'is_active' => true,
        ]);

        $field = FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Select,
            'label' => 'Provincia',
            'name' => 'provincia',
            'is_required' => true,
            'settings' => [
                'searchable' => true,
                'placeholder' => 'Elegí tu provincia...',
            ],
            'order' => 1,
            'is_active' => true,
        ]);

        $field->options()->createMany([
            ['label' => 'Buenos Aires', 'value' => 'buenos_aires', 'order' => 1, 'is_active' => true],
            ['label' => 'Córdoba', 'value' => 'cordoba', 'order' => 2, 'is_active' => true],
        ]);

        $version = app(FormVersionService::class)->publish($form->fresh());

        expect($version->schema_snapshot[0]['settings']['searchable'])->toBeTrue()
            ->and($version->schema_snapshot[0]['settings']['placeholder'])->toBe('Elegí tu provincia...')
            ->and($version->schema_snapshot[0]['options'])->toHaveCount(2);
    });
});

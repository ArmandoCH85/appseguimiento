<?php

declare(strict_types=1);

use App\Filament\Tenant\Resources\FormResource\Pages\BuildForm;
use App\Models\Central\Tenant;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormField;
use App\Models\Tenant\User;
use Database\Seeders\TenantDatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('tenant'));
});

afterEach(fn () => dropCurrentTestTenantDatabases());

it('persists an auto generated internal name when saving builder fields', function () {
    $tenant = Tenant::create([
        'id' => 'builder-name',
        'name' => 'Builder Name',
        'slug' => 'builder-name',
    ]);

    $tenant->run(function (): void {
        app(TenantDatabaseSeeder::class)->run();

        $admin = User::query()->where('email', 'admin@tenant.test')->firstOrFail();
        auth()->guard('web')->login($admin);

        $form = Form::query()->create([
            'name' => 'Inspección visual',
            'description' => 'Formulario para pruebas del constructor',
            'is_active' => true,
        ]);

        Livewire::test(BuildForm::class, ['record' => $form->getRouteKey()])
            ->fillForm([
                'fields' => [
                    [
                        'label' => 'Demo',
                        'type' => 'text',
                        'is_required' => false,
                        'is_active' => true,
                        'validation_rules' => [],
                        'settings' => [],
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $field = FormField::query()->where('form_id', $form->getKey())->first();

        expect($field)->not->toBeNull()
            ->and($field->name)->toBe('demo');

        auth()->guard('web')->logout();
    });
});

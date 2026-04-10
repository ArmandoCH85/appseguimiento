<?php

declare(strict_types=1);

use App\Filament\Tenant\Pages\RolePermissionPage;
use App\Models\Central\Tenant;
use Database\Seeders\TenantDatabaseSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('tenant'));
});

afterEach(fn () => dropCurrentTestTenantDatabases());

it('groups tenant permissions by domain for the management ui', function () {
    $page = new class extends RolePermissionPage
    {
        public function exposePermissionOptions(): array
        {
            return $this->getPermissionOptions();
        }
    };

    $options = $page->exposePermissionOptions();

    expect($options)->toHaveKeys(['Formularios', 'Usuarios'])
        ->and($options['Formularios']['forms.view'])->toBe('Ver formularios')
        ->and($options['Formularios']['forms.publish'])->toBe('Publicar versiones')
        ->and($options['Usuarios']['users.view'])->toBe('Ver usuarios')
        ->and($options['Usuarios']['users.manage'])->toBe('Gestionar usuarios');
});

it('renders one checkbox list per permission domain instead of passing nested option arrays to a single checkbox list', function () {
    $page = new class extends RolePermissionPage
    {
        protected function getRoleOptions(): array
        {
            return ['1' => 'Admin'];
        }

        public function exposeFormSchema(Schema $schema): Schema
        {
            return $this->form($schema);
        }
    };

    $schema = $page->exposeFormSchema(Schema::make($page));
    $permissionSection = $schema->getComponents()[1];

    expect($permissionSection)->toBeInstanceOf(Section::class);

    $checkboxLists = array_filter(
        $permissionSection->getChildSchema()->getComponents(),
        fn ($component) => $component instanceof CheckboxList,
    );

    expect($checkboxLists)->toHaveCount(5)
        ->and(array_map(fn (CheckboxList $component) => $component->getName(), array_values($checkboxLists)))
            ->toEqualCanonicalizing([
                'permissions_by_domain.formularios',
                'permissions_by_domain.asignaciones',
                'permissions_by_domain.respuestas',
                'permissions_by_domain.usuarios',
                'permissions_by_domain.reportes',
            ]);
});

it('protects the admin role and syncs permissions for editable roles', function () {
    $tenant = Tenant::create([
        'id' => 'page-permissions',
        'name' => 'Page Permissions',
        'slug' => 'page-permissions',
    ]);

    $tenant->run(function (): void {
        app(TenantDatabaseSeeder::class)->run();

        $page = new class extends RolePermissionPage
        {
            public function exposeIsLockedRole(Role $role): bool
            {
                return $this->isLockedRole($role);
            }

            public function exposeSyncRolePermissions(Role $role, array $permissions): void
            {
                $this->syncRolePermissions($role, $permissions);
            }
        };

        $adminRole = Role::findByName('admin', 'web');
        $supervisorRole = Role::findByName('supervisor', 'web');

        $adminPermissionsBefore = $adminRole->permissions->pluck('name')->values()->all();

        $page->exposeSyncRolePermissions($supervisorRole, ['forms.view', 'users.manage']);
        $page->exposeSyncRolePermissions($adminRole, ['forms.view']);

        expect($page->exposeIsLockedRole($adminRole))->toBeTrue()
            ->and($page->exposeIsLockedRole($supervisorRole))->toBeFalse()
            ->and($supervisorRole->fresh()->permissions->pluck('name')->values()->all())
                ->toEqualCanonicalizing(['forms.view', 'users.manage'])
            ->and($adminRole->fresh()->permissions->pluck('name')->values()->all())
                ->toEqualCanonicalizing($adminPermissionsBefore);
    });
});

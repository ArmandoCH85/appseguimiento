<?php

declare(strict_types=1);

use App\Filament\Tenant\Pages\RolePermissionPage;
use App\Filament\Tenant\Resources\FormAssignmentResource;
use App\Filament\Tenant\Resources\FormResource;
use App\Filament\Tenant\Resources\SubmissionResource;
use App\Filament\Tenant\Resources\UserResource;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Database\Seeders\TenantDatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('tenant'));
});

afterEach(fn () => dropCurrentTestTenantDatabases());

it('shows tenant navigation items to a supervisor based on permissions', function () {
    $tenant = Tenant::create([
        'id' => 'nav-supervisor',
        'name' => 'Nav Supervisor',
        'slug' => 'nav-supervisor',
    ]);

    $tenant->run(function (): void {
        app(TenantDatabaseSeeder::class)->run();

        $supervisor = User::query()->where('email', 'supervisor@tenant.test')->firstOrFail();

        auth()->guard('web')->login($supervisor);

        expect(FormResource::shouldRegisterNavigation())->toBeTrue()
            ->and(FormAssignmentResource::shouldRegisterNavigation())->toBeTrue()
            ->and(SubmissionResource::shouldRegisterNavigation())->toBeTrue()
            ->and(UserResource::shouldRegisterNavigation())->toBeTrue()
            ->and(RolePermissionPage::shouldRegisterNavigation())->toBeFalse()
            ->and(RolePermissionPage::canAccess())->toBeFalse();

        auth()->guard('web')->logout();
    });
});

it('hides tenant navigation items from an operator without matching view permissions', function () {
    $tenant = Tenant::create([
        'id' => 'nav-operator',
        'name' => 'Nav Operator',
        'slug' => 'nav-operator',
    ]);

    $tenant->run(function (): void {
        app(TenantDatabaseSeeder::class)->run();

        $operator = User::query()->where('email', 'operador@tenant.test')->firstOrFail();

        auth()->guard('web')->login($operator);

        expect(FormResource::shouldRegisterNavigation())->toBeFalse()
            ->and(FormAssignmentResource::shouldRegisterNavigation())->toBeFalse()
            ->and(SubmissionResource::shouldRegisterNavigation())->toBeFalse()
            ->and(UserResource::shouldRegisterNavigation())->toBeFalse()
            ->and(RolePermissionPage::shouldRegisterNavigation())->toBeFalse()
            ->and(RolePermissionPage::canAccess())->toBeFalse();

        auth()->guard('web')->logout();
    });
});

it('shows the permission management page only to users with users.manage', function () {
    $tenant = Tenant::create([
        'id' => 'nav-admin',
        'name' => 'Nav Admin',
        'slug' => 'nav-admin',
    ]);

    $tenant->run(function (): void {
        app(TenantDatabaseSeeder::class)->run();

        $admin = User::query()->where('email', 'admin@tenant.test')->firstOrFail();

        auth()->guard('web')->login($admin);

        expect(RolePermissionPage::shouldRegisterNavigation())->toBeTrue()
            ->and(RolePermissionPage::canAccess())->toBeTrue();

        auth()->guard('web')->logout();
    });
});

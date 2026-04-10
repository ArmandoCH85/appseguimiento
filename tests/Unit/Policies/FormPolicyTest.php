<?php

declare(strict_types=1);

use App\Models\Central\Tenant;
use App\Models\Tenant\Form;
use App\Models\Tenant\User;
use App\Policies\Tenant\FormPolicy;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(fn () => dropCurrentTestTenantDatabases());

it('allows admins to create and edit forms but blocks operators', function () {
    $tenantId = 'policy-'.str()->lower(str()->random(8));

    $tenant = Tenant::create([
        'id' => $tenantId,
        'name' => 'Policy A',
        'slug' => $tenantId,
    ]);

    $tenant->run(function () {
        app(TenantDatabaseSeeder::class)->run();

        $admin = User::query()->where('email', 'admin@tenant.test')->firstOrFail();
        $operator = User::query()->where('email', 'operador@tenant.test')->firstOrFail();

        $form = Form::query()->create([
            'name' => 'Policy Form',
            'description' => null,
            'is_active' => true,
        ]);

        $policy = new FormPolicy();

        expect($policy->create($admin))->toBeTrue()
            ->and($policy->update($admin, $form))->toBeTrue()
            ->and($policy->create($operator))->toBeFalse()
            ->and($policy->update($operator, $form))->toBeFalse();
    });
});

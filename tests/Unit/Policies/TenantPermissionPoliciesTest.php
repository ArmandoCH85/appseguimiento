<?php

declare(strict_types=1);

use App\Models\Central\Tenant;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormAssignment;
use App\Models\Tenant\FormVersion;
use App\Models\Tenant\Submission;
use App\Models\Tenant\User;
use App\Policies\Tenant\FormAssignmentPolicy;
use App\Policies\Tenant\FormPolicy;
use App\Policies\Tenant\SubmissionPolicy;
use App\Policies\Tenant\UserPolicy;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(fn () => dropCurrentTestTenantDatabases());

it('uses permissions instead of fixed roles for form management', function () {
    $tenant = Tenant::create([
        'id' => 'policy-permissions-forms',
        'name' => 'Policy Permissions Forms',
        'slug' => 'policy-permissions-forms',
    ]);

    $tenant->run(function (): void {
        app(TenantDatabaseSeeder::class)->run();

        $admin = User::query()->where('email', 'admin@tenant.test')->firstOrFail();
        $supervisor = User::query()->where('email', 'supervisor@tenant.test')->firstOrFail();

        Role::findByName('admin', 'web')->revokePermissionTo(['forms.create', 'forms.update']);
        $supervisor->givePermissionTo(['forms.create', 'forms.update']);

        $form = Form::query()->create([
            'name' => 'Policy Form',
            'description' => null,
            'is_active' => true,
        ]);

        $policy = new FormPolicy();

        expect($policy->create($supervisor))->toBeTrue()
            ->and($policy->update($supervisor, $form))->toBeTrue()
            ->and($policy->create($admin))->toBeFalse()
            ->and($policy->update($admin, $form))->toBeFalse();
    });
});

it('uses users permissions instead of the admin role for user management', function () {
    $tenant = Tenant::create([
        'id' => 'policy-permissions-users',
        'name' => 'Policy Permissions Users',
        'slug' => 'policy-permissions-users',
    ]);

    $tenant->run(function (): void {
        app(TenantDatabaseSeeder::class)->run();

        $admin = User::query()->where('email', 'admin@tenant.test')->firstOrFail();
        $supervisor = User::query()->where('email', 'supervisor@tenant.test')->firstOrFail();

        Role::findByName('admin', 'web')->revokePermissionTo(['users.view', 'users.manage']);
        $supervisor->givePermissionTo(['users.view', 'users.manage']);

        $policy = new UserPolicy();

        expect($policy->viewAny($supervisor))->toBeTrue()
            ->and($policy->create($supervisor))->toBeTrue()
            ->and($policy->viewAny($admin))->toBeFalse()
            ->and($policy->create($admin))->toBeFalse();
    });
});

it('uses assignment and submission permissions from spatie instead of static roles', function () {
    $tenant = Tenant::create([
        'id' => 'policy-permissions-submissions',
        'name' => 'Policy Permissions Submissions',
        'slug' => 'policy-permissions-submissions',
    ]);

    $tenant->run(function (): void {
        app(TenantDatabaseSeeder::class)->run();

        $supervisor = User::query()->where('email', 'supervisor@tenant.test')->firstOrFail();
        $operator = User::query()->where('email', 'operador@tenant.test')->firstOrFail();

        $assignmentPolicy = new FormAssignmentPolicy();
        $submissionPolicy = new SubmissionPolicy();

        $form = Form::query()->create([
            'name' => 'Assigned Form',
            'description' => null,
            'is_active' => true,
        ]);

        $version = FormVersion::query()->create([
            'form_id' => $form->getKey(),
            'version_number' => 1,
            'schema_snapshot' => [],
            'published_at' => now(),
        ]);

        $assignment = FormAssignment::query()->create([
            'form_id' => $form->getKey(),
            'user_id' => $operator->getKey(),
            'assigned_at' => now(),
        ]);

        $submission = Submission::query()->create([
            'form_version_id' => $version->getKey(),
            'user_id' => $operator->getKey(),
            'status' => 'complete',
            'submitted_at' => now(),
            'idempotency_key' => 'idem-1',
            'latitude' => 1.23,
            'longitude' => 4.56,
        ]);

        expect($assignmentPolicy->viewAny($supervisor))->toBeTrue()
            ->and($assignmentPolicy->create($supervisor))->toBeFalse()
            ->and($submissionPolicy->viewAny($operator))->toBeFalse();

        $supervisor->givePermissionTo('assignments.manage');
        $operator->givePermissionTo('submissions.view');

        expect($assignmentPolicy->create($supervisor))->toBeTrue()
            ->and($assignmentPolicy->view($supervisor, $assignment))->toBeTrue()
            ->and($submissionPolicy->viewAny($operator))->toBeTrue()
            ->and($submissionPolicy->view($operator, $submission))->toBeTrue();
    });
});

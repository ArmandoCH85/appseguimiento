<?php

declare(strict_types=1);

use App\Models\Central\Tenant;
use App\Models\Tenant\Form;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(fn () => dropCurrentTestTenantDatabases());

it('logs form updates with the acting user and event type', function () {
    $tenantId = 'activity-'.str()->lower(str()->random(8));

    $tenant = Tenant::create([
        'id' => $tenantId,
        'name' => 'Activity A',
        'slug' => $tenantId,
    ]);

    $tenant->run(function () {
        $role = Role::findOrCreate('admin', 'web');

        $user = User::query()->create([
            'name' => 'Logger',
            'email' => 'logger@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        auth()->guard('web')->setUser($user);

        $form = Form::query()->create([
            'name' => 'Original',
            'description' => 'before',
            'is_active' => true,
        ]);

        $form->update([
            'name' => 'Updated',
        ]);

        $activity = Activity::query()
            ->where('subject_type', Form::class)
            ->where('subject_id', $form->getKey())
            ->where('event', 'updated')
            ->latest()
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->causer_id)->toBe($user->getKey())
            ->and($activity->event)->toBe('updated');
    });
});

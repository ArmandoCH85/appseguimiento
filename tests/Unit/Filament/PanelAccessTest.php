<?php

declare(strict_types=1);

use App\Models\Central\CentralUser;
use App\Models\Tenant\User;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

function panelWithId(string $id): Panel
{
    $panel = \Mockery::mock(Panel::class);
    $panel->shouldReceive('getId')->andReturn($id);

    return $panel;
}

it('allows super admins to access central panels', function () {
    $user = new CentralUser([
        'name' => 'Central Admin',
        'email' => 'central@example.com',
        'password' => 'password',
        'is_super_admin' => true,
    ]);

    expect($user)->toBeInstanceOf(FilamentUser::class)
        ->and($user->canAccessPanel(panelWithId('central')))->toBeTrue()
        ->and($user->canAccessPanel(panelWithId('admin')))->toBeTrue()
        ->and($user->canAccessPanel(panelWithId('tenant')))->toBeFalse();
});

it('denies non super admins from central panels', function () {
    $user = new CentralUser([
        'name' => 'Central User',
        'email' => 'user@example.com',
        'password' => 'password',
        'is_super_admin' => false,
    ]);

    expect($user->canAccessPanel(panelWithId('central')))->toBeFalse()
        ->and($user->canAccessPanel(panelWithId('admin')))->toBeFalse();
});

it('allows active tenant users to access only the tenant panel', function () {
    $user = new User([
        'name' => 'Tenant User',
        'email' => 'tenant@example.com',
        'password' => 'password',
        'is_active' => true,
    ]);

    expect($user)->toBeInstanceOf(FilamentUser::class)
        ->and($user->canAccessPanel(panelWithId('tenant')))->toBeTrue()
        ->and($user->canAccessPanel(panelWithId('central')))->toBeFalse()
        ->and($user->canAccessPanel(panelWithId('admin')))->toBeFalse();
});

it('denies inactive tenant users from the tenant panel', function () {
    $user = new User([
        'name' => 'Inactive Tenant User',
        'email' => 'inactive@example.com',
        'password' => 'password',
        'is_active' => false,
    ]);

    expect($user->canAccessPanel(panelWithId('tenant')))->toBeFalse();
});

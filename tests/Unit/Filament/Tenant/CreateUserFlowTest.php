<?php

declare(strict_types=1);

use App\Filament\Tenant\Resources\UserResource\Pages\CreateUser;
use App\Filament\Tenant\Resources\UserResource\Pages\EditUser;

it('uses a friendly spanish title for the create user page', function () {
    expect((new CreateUser())->getTitle())->toBe('Crear usuario');
});

it('uses a helpful subheading for the create user page', function () {
    expect((new CreateUser())->getSubheading())
        ->toBe('Completá los datos básicos, el acceso y el rol del nuevo usuario.');
});

it('uses a friendly spanish title for the edit user page', function () {
    expect((new EditUser())->getTitle())->toBe('Editar usuario');
});

it('uses a helpful subheading for the edit user page', function () {
    expect((new EditUser())->getSubheading())
        ->toBe('Actualizá los datos, el estado y los permisos del usuario seleccionado.');
});

it('uses a friendly spanish creation notification', function () {
    $page = new class extends CreateUser
    {
        public function exposeCreatedNotificationTitle(): ?string
        {
            return $this->getCreatedNotificationTitle();
        }
    };

    expect($page->exposeCreatedNotificationTitle())
        ->toBe('Usuario creado correctamente.');
});
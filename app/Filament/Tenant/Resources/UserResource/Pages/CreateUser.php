<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserResource\Pages;

use App\Filament\Tenant\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Crear usuario';
    }

    public function getSubheading(): ?string
    {
        return 'Completá los datos básicos, el acceso y el rol del nuevo usuario.';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Usuario creado correctamente.';
    }
}

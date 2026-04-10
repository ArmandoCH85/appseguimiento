<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserResource\Pages;

use App\Filament\Tenant\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Editar usuario';
    }

    public function getSubheading(): ?string
    {
        return 'Actualizá los datos, el estado y los permisos del usuario seleccionado.';
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FormResource\Pages;

use App\Filament\Tenant\Resources\FormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateForm extends CreateRecord
{
    protected static string $resource = FormResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('builder', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Formulario creado. Ahora agregá sus preguntas.';
    }

    public function getSubheading(): ?string
    {
        return 'Primero cargá los datos básicos. Cuando guardes, te llevamos al constructor para diseñar las preguntas.';
    }
}

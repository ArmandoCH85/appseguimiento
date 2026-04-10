<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FormResource\Pages;

use App\Filament\Tenant\Resources\FormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Crear formulario')
                ->visible(fn (): bool => auth()->user()?->hasPermissionTo('forms.create') ?? false),
        ];
    }
}

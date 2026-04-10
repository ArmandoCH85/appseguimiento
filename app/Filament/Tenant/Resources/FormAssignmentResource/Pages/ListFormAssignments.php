<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FormAssignmentResource\Pages;

use App\Filament\Tenant\Resources\FormAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFormAssignments extends ListRecords
{
    protected static string $resource = FormAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Asignar formulario'),
        ];
    }
}

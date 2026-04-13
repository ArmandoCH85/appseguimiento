<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DeviceResource\Pages;

use App\Filament\Tenant\Resources\DeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Registrar dispositivo'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DeviceResource\Pages;

use App\Filament\Tenant\Resources\DeviceResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    public function form(Schema $schema): Schema
    {
        return DeviceResource::editForm($schema);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

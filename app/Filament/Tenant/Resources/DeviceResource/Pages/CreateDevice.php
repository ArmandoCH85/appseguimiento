<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DeviceResource\Pages;

use App\Filament\Tenant\Resources\DeviceResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateDevice extends CreateRecord
{
    protected static string $resource = DeviceResource::class;

    public function form(Schema $schema): Schema
    {
        return DeviceResource::createForm($schema);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

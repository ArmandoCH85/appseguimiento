<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\GpsTrackResource\Pages;

use App\Filament\Tenant\Resources\GpsTrackResource;
use Filament\Resources\Pages\ListRecords;

class ListGpsTracks extends ListRecords
{
    protected static string $resource = GpsTrackResource::class;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()
            ->with(['device.user']);
    }
}

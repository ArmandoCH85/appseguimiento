<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\CentralUserResource\Pages;

use App\Filament\Central\Resources\CentralUserResource;
use Filament\Resources\Pages\ListRecords;

class ListCentralUsers extends ListRecords
{
    protected static string $resource = CentralUserResource::class;
}

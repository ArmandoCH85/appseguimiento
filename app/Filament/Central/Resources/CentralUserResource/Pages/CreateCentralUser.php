<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\CentralUserResource\Pages;

use App\Filament\Central\Resources\CentralUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCentralUser extends CreateRecord
{
    protected static string $resource = CentralUserResource::class;
}

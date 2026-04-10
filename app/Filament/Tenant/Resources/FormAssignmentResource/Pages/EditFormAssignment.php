<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FormAssignmentResource\Pages;

use App\Filament\Tenant\Resources\FormAssignmentResource;
use Filament\Resources\Pages\EditRecord;

class EditFormAssignment extends EditRecord
{
    protected static string $resource = FormAssignmentResource::class;
}

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SubmissionResource\Pages;

use App\Filament\Tenant\Resources\SubmissionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;
}

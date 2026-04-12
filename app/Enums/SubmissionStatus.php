<?php

declare(strict_types=1);

namespace App\Enums;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case PendingPhotos = 'pending_photos';
    case Complete = 'complete';
}

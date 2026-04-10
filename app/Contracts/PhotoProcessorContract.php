<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Tenant\Submission;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface PhotoProcessorContract
{
    public function process(Submission $submission, string $disk, string $path, string $originalName): Media;

    public function generateHash(string $absolutePath): string;
}

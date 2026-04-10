<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Contracts\PhotoProcessorContract;
use App\Enums\SubmissionStatus;
use App\Models\Tenant\Submission;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProcessPhotoAction implements PhotoProcessorContract
{
    public function process(Submission $submission, string $disk, string $path, string $originalName): Media
    {
        $absolutePath = Storage::disk($disk)->path($path);
        $hash = $this->generateHash($absolutePath);

        $media = $submission
            ->addMediaFromDisk($path, $disk)
            ->usingName(pathinfo($originalName, PATHINFO_FILENAME))
            ->usingFileName($originalName)
            ->withCustomProperties([
                'sha256' => $hash,
                'exif' => $this->extractExif($absolutePath),
            ])
            ->toMediaCollection('submissions');

        Storage::disk($disk)->delete($path);

        $submission->forceFill([
            'status' => SubmissionStatus::Complete,
        ])->save();

        return $media;
    }

    public function generateHash(string $absolutePath): string
    {
        return hash_file('sha256', $absolutePath);
    }

    protected function extractExif(string $absolutePath): array
    {
        if (! function_exists('exif_read_data')) {
            return [];
        }

        try {
            $data = @exif_read_data($absolutePath);

            return is_array($data) ? $data : [];
        } catch (\Throwable) {
            return [];
        }
    }
}

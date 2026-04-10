<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Tenant\ProcessPhotoAction;
use App\Models\Central\Tenant;
use App\Models\Tenant\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPhotoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $tenantId,
        public string $submissionId,
        public string $disk,
        public string $path,
        public string $originalName,
    ) {}

    public function handle(ProcessPhotoAction $action): void
    {
        $tenant = Tenant::query()->findOrFail($this->tenantId);

        $tenant->run(function () use ($action): void {
            $submission = Submission::query()->findOrFail($this->submissionId);

            $action->process($submission, $this->disk, $this->path, $this->originalName);
        });
    }
}

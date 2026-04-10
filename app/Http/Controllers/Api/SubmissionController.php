<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\SubmissionServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSubmissionRequest;
use App\Http\Requests\Api\UploadPhotoRequest;
use App\Http\Resources\Api\SubmissionResource;
use App\Jobs\ProcessPhotoJob;
use App\Models\Tenant\FormVersion;
use App\Models\Tenant\Submission;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionController extends Controller
{
    public function __construct(
        protected SubmissionServiceContract $submissionService,
    ) {}

    public function store(StoreSubmissionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $version = FormVersion::query()->findOrFail($request->validated('form_version_id'));

        $submission = $this->submissionService->createOrRetrieve($version, $user, $request->validated());

        /** @var JsonResource $resource */
        $resource = new SubmissionResource($submission);

        return $resource
            ->response()
            ->setStatusCode($submission->wasRecentlyCreated ? JsonResponse::HTTP_CREATED : JsonResponse::HTTP_OK);
    }

    public function uploadPhoto(UploadPhotoRequest $request, Submission $submission): JsonResponse
    {
        $file = $request->file('photo');
        $disk = 'local';
        $path = $file->store("tmp/submissions/{$submission->getKey()}", $disk);

        ProcessPhotoJob::dispatch(
            tenant()->getTenantKey(),
            $submission->getKey(),
            $disk,
            $path,
            $file->getClientOriginalName(),
        );

        return response()->json(status: JsonResponse::HTTP_ACCEPTED);
    }
}

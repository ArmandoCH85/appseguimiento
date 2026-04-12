<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\SubmissionServiceContract;
use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSubmissionRequest;
use App\Http\Requests\Api\UpdateSubmissionRequest;
use App\Http\Requests\Api\UploadPhotoRequest;
use App\Http\Resources\Api\SubmissionListResource;
use App\Http\Resources\Api\SubmissionResource;
use App\Jobs\ProcessPhotoJob;
use App\Models\Tenant\FormVersion;
use App\Models\Tenant\Submission;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

class SubmissionController extends Controller
{
    public function __construct(
        protected SubmissionServiceContract $submissionService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = request()->user();

        $query = Submission::query()
            ->with('formVersion.form')
            ->orderByDesc('created_at');

        if ($user->hasRole(TenantRole::Operator->value)) {
            $query->where('user_id', $user->getKey());
        }

        if (request()->has('status')) {
            $query->where('status', request()->query('status'));
        }

        if (request()->has('form_id')) {
            $query->whereHas('formVersion', function ($q) {
                $q->where('form_id', request()->query('form_id'));
            });
        }

        return SubmissionListResource::collection($query->paginate(15));
    }

    public function show(Submission $submission): SubmissionResource
    {
        /** @var User $user */
        $user = request()->user();

        $isAdminOrSupervisor = $user->hasRole(TenantRole::Admin->value)
            || $user->hasRole(TenantRole::Supervisor->value);

        if ($submission->user_id !== $user->getKey() && ! $isAdminOrSupervisor) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $submission->load('responses', 'formVersion');

        return new SubmissionResource($submission);
    }

    public function store(StoreSubmissionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $version = FormVersion::query()->findOrFail($request->validated('form_version_id'));

        if ($user->hasRole(TenantRole::Operator->value)) {
            $hasAssignment = $version->form->assignments()
                ->where('user_id', $user->getKey())
                ->whereNull('revoked_at')
                ->exists();

            if (! $hasAssignment) {
                return response()->json([
                    'error' => [
                        'code' => 'ASSIGNMENT_REQUIRED',
                        'message' => 'No tienes una asignación activa para este formulario.',
                    ],
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $submission = $this->submissionService->createOrRetrieve($version, $user, $request->validated());

        /** @var JsonResource $resource */
        $resource = new SubmissionResource($submission);

        return $resource
            ->response()
            ->setStatusCode($submission->wasRecentlyCreated ? JsonResponse::HTTP_CREATED : JsonResponse::HTTP_OK);
    }

    public function update(UpdateSubmissionRequest $request, Submission $submission): SubmissionResource
    {
        /** @var User $user */
        $user = $request->user();

        if ($submission->user_id !== $user->getKey()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $updated = $this->submissionService->updateSubmission($submission, $user, $request->validated());

        return new SubmissionResource($updated);
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

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PhotoResource;
use App\Models\Tenant\Submission;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class PhotoController extends Controller
{
    /**
     * List photos for a submission.
     */
    public function index(Submission $submission): AnonymousResourceCollection
    {
        $photos = $submission->getMedia('submissions');

        return PhotoResource::collection($photos);
    }

    /**
     * Delete a photo from a submission.
     */
    public function destroy(Submission $submission, string $mediaId): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $media = $submission->getMedia('submissions')->firstWhere('id', $mediaId);

        if (! $media) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Foto no encontrada.',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        if ($submission->user_id !== $user->getKey() && ! $user->hasRole('admin') && ! $user->hasRole('supervisor')) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_AUTHORIZED',
                    'message' => 'No tienes permiso para eliminar fotos de esta respuesta.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        if ($submission->status->value === 'complete') {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_STATUS',
                    'message' => 'No se pueden eliminar fotos de una respuesta completada.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $media->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}

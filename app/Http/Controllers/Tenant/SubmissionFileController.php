<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Models\Tenant\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller para servir archivos de submissions con verificación de permisos.
 * Los archivos se almacenan en disco privado y solo son accesibles
 * para usuarios con permiso submissions.view sobre ese tenant.
 */
class SubmissionFileController
{
    /**
     * Sirve un archivo de una submission específica.
     */
    public function show(Request $request, Submission $submission, string $filename): StreamedResponse|Response
    {
        // Verificar que el usuario tenga permiso para ver submissions
        if (! auth()->user()?->hasPermissionTo('submissions.view')) {
            return response('No autorizado', 403);
        }

        // Buscar el archivo en el media collection
        $media = $submission->getMedia('submissions')
            ->firstWhere('file_name', $filename);

        if (! $media) {
            return response('Archivo no encontrado', 404);
        }

        // Verificar que el archivo exista en disco
        if (! Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())) {
            return response('Archivo no encontrado en disco', 404);
        }

        // Servir el archivo
        return Storage::disk($media->disk)->response($media->getPathRelativeToRoot(), $filename, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}

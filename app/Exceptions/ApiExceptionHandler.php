<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ApiExceptionHandler
{
    public function render(Request $request, \Throwable $e): ?JsonResponse
    {
        if ($e instanceof ValidationException) {
            return null; // Let Laravel handle validation errors natively
        }

        if ($e instanceof AuthenticationException) {
            return $this->json(Response::HTTP_UNAUTHORIZED, 'UNAUTHORIZED', 'No autenticado o sesión expirada.');
        }

        if ($e instanceof AuthorizationException) {
            return $this->json(Response::HTTP_FORBIDDEN, 'NOT_AUTHORIZED', $e->getMessage());
        }

        if ($e instanceof ValidationException) {
            return $this->json(Response::HTTP_UNPROCESSABLE_ENTITY, 'VALIDATION_ERROR', 'Datos de entrada inválidos.', $e->errors());
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return $this->json(Response::HTTP_NOT_FOUND, 'NOT_FOUND', 'Recurso no encontrado.');
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return $this->json(Response::HTTP_TOO_MANY_REQUESTS, 'RATE_LIMIT_EXCEEDED', 'Demasiadas solicitudes. Intente más tarde.');
        }

        return null;
    }

    protected function json(int $status, string $code, string $message, ?array $details = null): JsonResponse
    {
        $response = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== null) {
            $response['error']['details'] = $details;
        }

        return response()->json($response, $status);
    }
}

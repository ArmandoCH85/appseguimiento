<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        /** @var User|null $user */
        $user = User::query()
            ->withCount(['assignments' => function ($q) {
                $q->whereNull('revoked_at');
            }])
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Credenciales inválidas.',
                ],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $user->hasRole(TenantRole::Operator->value)) {
            return response()->json([
                'error' => [
                    'code' => 'ROLE_NOT_ALLOWED',
                    'message' => 'Solo los operadores pueden acceder a este recurso.',
                ],
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $token = $user->createToken('mobile', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->first() ?? 'operator',
                'assignments_count' => $user->assignments_count,
            ],
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->currentAccessToken()?->delete();

        $token = $user->createToken('mobile', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $user->currentAccessToken()?->delete();

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}

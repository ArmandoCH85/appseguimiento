<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        $user->loadCount(['assignments' => function ($q) {
            $q->whereNull('revoked_at');
        }]);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }
}

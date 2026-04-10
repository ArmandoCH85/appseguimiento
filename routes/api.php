<?php

declare(strict_types=1);

use App\Enums\TenantRole;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Middleware\EnsureTenantApiAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;

Route::prefix('{tenant}')
    ->middleware([InitializeTenancyByPath::class])
    ->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login']);

        Route::middleware(['auth:sanctum', EnsureTenantApiAccess::class])->group(function () {
            Route::get('/forms', [FormController::class, 'index']);
            Route::get('/forms/{form}', [FormController::class, 'show']);
            Route::post('/submissions', [SubmissionController::class, 'store']);
            Route::post('/submissions/{submission}/photos', [SubmissionController::class, 'uploadPhoto']);

            Route::get('/me', function (Request $request) {
                return response()->json([
                    'id' => $request->user()->getKey(),
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ]);
            });

            Route::post('/auth/logout', [AuthController::class, 'logout']);

            Route::get('/admin/ping', function (Request $request) {
                abort_unless($request->user()->hasRole(TenantRole::Admin->value), 403);

                return response()->json(['ok' => true]);
            });
        });
    });

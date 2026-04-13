<?php

declare(strict_types=1);

use App\Enums\TenantRole;
use App\Http\Controllers\Api\GpsTrackController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PhotoController;
use App\Http\Middleware\EnsureOperatorRole;
use App\Http\Middleware\EnsureTenantApiAccess;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;

Route::prefix('v1/{tenant}')
    ->middleware([InitializeTenancyByPath::class])
    ->group(function () {
        // Public routes
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/auth/login', [AuthController::class, 'login']);
        });

        // Authenticated routes
        Route::middleware(['auth:sanctum', EnsureTenantApiAccess::class])->group(function () {
            // Auth
            Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:30,1');
            Route::post('/auth/logout', [AuthController::class, 'logout']);

            // Me
            Route::get('/me', MeController::class);

            // Forms
            Route::get('/forms', [FormController::class, 'index'])->middleware('throttle:60,1');
            Route::get('/forms/{form}', [FormController::class, 'show'])->middleware('throttle:60,1');

            // Submissions
            Route::get('/submissions', [SubmissionController::class, 'index'])->middleware('throttle:60,1');
            Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->middleware('throttle:60,1');
            Route::post('/submissions', [SubmissionController::class, 'store'])->middleware('throttle:60,1');
            Route::patch('/submissions/{submission}', [SubmissionController::class, 'update'])->middleware('throttle:60,1');
            Route::post('/submissions/{submission}/photos', [SubmissionController::class, 'uploadPhoto'])->middleware('throttle:60,1');

            // Photos
            Route::get('/submissions/{submission}/photos', [PhotoController::class, 'index'])->middleware('throttle:60,1');
            Route::delete('/submissions/{submission}/photos/{media}', [PhotoController::class, 'destroy'])->middleware('throttle:60,1');

            // Admin ping
            Route::get('/admin/ping', function (\Illuminate\Http\Request $request) {
                abort_unless($request->user()->hasRole(TenantRole::Admin->value), 403);

                return response()->json(['ok' => true]);
            });

            // GPS Tracking
            Route::post('/gps/track', [GpsTrackController::class, 'store'])->middleware('throttle:120,1');
            Route::get('/gps/track', [GpsTrackController::class, 'index'])->middleware('throttle:60,1');
            Route::get('/gps/track/{track}', [GpsTrackController::class, 'show'])->middleware('throttle:60,1');
        });
    });

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| Here you can register routes for the central application.
| These routes are NOT tenant-aware — they serve the super admin panel
| and any central (non-tenant) functionality.
|
| The CentralPanelProvider registers Filament's /central panel routes
| automatically via its service provider.
|
*/

Route::middleware('web')->group(function () {
    // Central web routes go here.
    // The /central Filament panel is registered by CentralPanelProvider.
});

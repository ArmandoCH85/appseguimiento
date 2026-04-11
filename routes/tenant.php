<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\SubmissionFileController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Root route redirect handled in web.php via RootRedirect middleware

    // Ruta para descargar archivos de submissions con autorización
    Route::get('/app/submissions/{submission}/files/{filename}', [SubmissionFileController::class, 'show'])
        ->name('tenant.submissions.files.show')
        ->middleware(['auth']);
});


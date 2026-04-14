<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Middleware handles redirect, this fallback should never be reached
})->middleware('root.redirect');


Route::get('/api/verify-domain', function (\Illuminate\Http\Request $request) {
    $domain = $request->query('domain');

    if ($domain === 'drroutex.pe') {
        return response('OK', 200);
    }

    $exists = \Stancl\Tenancy\Database\Models\Domain::where('domain', $domain)->exists();

    return $exists
        ? response('OK', 200)
        : response('Not found', 404);
});

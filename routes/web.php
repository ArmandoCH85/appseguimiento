<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/api/verify-domain', function (\Illuminate\Http\Request $request) {
    $domain = $request->query('domain');

    if ($domain === 'amsolutions.lat') {
        return response('OK', 200);
    }

    $exists = \Stancl\Tenancy\Database\Models\Domain::where('domain', $domain)->exists();

    return $exists
        ? response('OK', 200)
        : response('Not found', 404);
});
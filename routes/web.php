<?php

use Illuminate\Support\Facades\Route;

// CSRF cookie route for Sanctum SPA authentication
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});


// Catch-all route for Vue SPA (must NOT match /api routes)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');

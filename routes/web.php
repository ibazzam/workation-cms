<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->any('{path?}', function () {
    return response()->json([
        'code' => 'LARAVEL_API_DECOMMISSIONED',
        'message' => 'Laravel product API routes are decommissioned. Use NestJS /api/v1 endpoints.',
    ], 410);
})->where('path', '.*');

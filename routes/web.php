<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Workation API (simple JSON endpoints)
Route::prefix('api')->group(function () {
    Route::get('workations', [\App\Http\Controllers\WorkationController::class, 'index']);
    Route::get('workations/{workation}', [\App\Http\Controllers\WorkationController::class, 'show']);
    Route::post('workations', [\App\Http\Controllers\WorkationController::class, 'store']);
    Route::put('workations/{workation}', [\App\Http\Controllers\WorkationController::class, 'update']);
    Route::delete('workations/{workation}', [\App\Http\Controllers\WorkationController::class, 'destroy']);
    // Transport holds API
    Route::post('transport/holds', [\App\Http\Controllers\TransportHoldController::class, 'store']);
    Route::post('transport/holds/{hold}/confirm', [\App\Http\Controllers\TransportHoldController::class, 'confirm']);
    Route::post('transport/holds/{hold}/release', [\App\Http\Controllers\TransportHoldController::class, 'release']);
});

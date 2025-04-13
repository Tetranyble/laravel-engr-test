<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::name('v1.')->prefix('v1')->group(function () {
    Route::post('claims', [\App\Http\Controllers\ClaimController::class, 'store'])
        ->name('claims.store');
});

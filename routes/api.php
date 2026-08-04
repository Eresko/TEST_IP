<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\HoldController;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\RateLimitSlotsApi;


Route::prefix('slots')->controller(AvailabilityController::class)->group(function () {
    Route::get('/availability', 'getSlotsAvailability')->name('slots.availability');

    Route::post('/{id}/hold', 'hold')->name('slots.hold')
        ->middleware([RateLimitSlotsApi::class, IdempotencyMiddleware::class]);
});


Route::prefix('holds')->controller(HoldController::class)->group(function () {
    Route::post('/{id}/confirm', 'confirm')->name('holds.confirm')
        ->middleware([RateLimitSlotsApi::class]);

    Route::delete('/{id}', 'cancel')->name('holds.cancel')
        ->middleware([RateLimitSlotsApi::class]);
});

<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationReportController;

Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::patch('/{id}/status', 'updateStatus');
});


Route::get('/users', [UserController::class, 'index']);



<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\MockSupplierController;
use App\Http\Controllers\Api\V1\ReconcileController;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\RateLimitSlotsApi;




Route::prefix('v1')->group(function () {


    Route::post('/orders', [OrderController::class, 'create'])->middleware(IdempotencyMiddleware::class);;
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    Route::post('/payments/webhook', [PaymentController::class, 'webhook']);


    /**
     * ЗАГЛУШКИ ПОСТАВЩИКОВ (Этап 3)
     * Динамический роут для тестирования состязательных сценариев.
     */
    Route::post('/mock-supplier/{name}/issue', [MockSupplierController::class, 'issue']);
    /**
     * СВЕРКА И ВОССТАНОВЛЕНИЕ (Этап 4)
     */
    Route::get('/admin/reconcile', [ReconcileController::class, 'reconcile']);
});
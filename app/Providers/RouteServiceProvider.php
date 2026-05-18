<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->routes(function () {
            // Подключаем API маршруты
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Подключаем веб маршруты
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
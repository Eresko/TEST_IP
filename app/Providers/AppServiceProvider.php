<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Cache\Repository;
use App\Models\Notification;
use App\Observers\NotificationObserver;
use App\Services\NotificationServices\SendService;
use App\Enums\ChannelType;
use App\Services\NotificationServices\Providers\SmsProvider;
use App\Services\NotificationServices\Providers\EmailProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SendService::class);

        $this->app->afterResolving(SendService::class, function (SendService $service, $app) {
            $service->registerProvider(ChannelType::EMAIL, $app->make(EmailProvider::class));
            $service->registerProvider(ChannelType::SMS, $app->make(SmsProvider::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(SendService $sendService): void
    {
        Notification::observe(NotificationObserver::class);
    }
}

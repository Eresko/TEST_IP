<?php

namespace App\Jobs\Middleware;

use App\Models\Notification;
use App\Enums\StatusMessage;
use Illuminate\Support\Facades\Cache;

class PreventDuplicateNotification
{
    public function handle(mixed $job, callable $next): mixed
    {
        $dto = $job->getDto();
        $notificationId = $dto->getNotificationId();

        $notification = Notification::find($notificationId);
        if (!$notification || in_array($notification->status, [StatusMessage::SENT->value, StatusMessage::DISCARDED->value])) {
            return null;
        }

        $lock = Cache::lock("notification_processing:{$notificationId}", 300);

        if (!$lock->get()) {
            $job->release(10);
            return null;
        }

        try {
            return $next($job);
        } finally {
            $lock->release();
        }
    }
}

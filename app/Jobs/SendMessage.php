<?php

namespace App\Jobs;

use App\Dto\Notification\MessageDto;
use App\Services\NotificationServices\SendService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use App\Models\Notification;
use App\Jobs\Middleware\PreventDuplicateNotification;
use Illuminate\Support\Facades\Cache;
class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private MessageDto $dto
    ) {}

    /**
     * @return PreventDuplicateNotification[]
     */
    public function middleware(): array
    {
        return [new PreventDuplicateNotification()];
    }
    /**
     * @return int
     * кол-во подписок
     */
    public function tries(): int
    {
        return (int) config('services.notifications.max_tries');
    }

    /**
     * @return array
     *  ожидание
     */
    public function backoff(): array
    {
        return config('services.notifications.backoff');
    }

    /**
     * @return MessageDto
     */
    public function getDto():MessageDto
    {
        return $this->dto;
    }

    public function handle(SendService $service): void
    {

        $service->send($this->dto);
    }

    public function failed(\Throwable $exception): void
    {
        $notification = Notification::find($this->dto->getNotificationId());

        if ($notification) {
            $notification->update(['status' => StatusMessage::DISCARDED->value]);
        }
    }
}

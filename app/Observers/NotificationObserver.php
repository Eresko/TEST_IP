<?php

namespace App\Observers;

use App\Models\Notification;
use App\Services\NotificationServices\SocketService;
use App\Enums\StatusMessage;

class NotificationObserver
{
    public function __construct(
        private readonly SocketService $socketService
    ) {}

    /**
     * Срабатывает после создания или обновления записи
     */
    public function saved(Notification $notification): void
    {

        if ($notification->wasChanged('status')) {

            $currentStatus = $notification->status;

            if (in_array($currentStatus, [StatusMessage::DELIVERED, StatusMessage::DISCARDED], true)) {
                $this->socketService->sendMessage(
                    (int) $notification->author_id,
                    $currentStatus->value, 
                    (int) $notification->id
                );
            }
        }
    }
}

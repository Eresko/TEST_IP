<?php

declare(strict_types=1);

namespace App\Services\NotificationServices;

/**
 * Сервис для работы с сокетами.
 */
class SocketService
{
    /**
     * @param int $authorId
     * @param string $status
     * @param int $notificationId
     * @return void
     */
    public function sendMessage(int $authorId, string $status, int $notificationId): void
    {
        /*
         * Логика отправки в сокеты
         *
         *
         */
        \Log::info("Socket sent to {$authorId}");
    }
}

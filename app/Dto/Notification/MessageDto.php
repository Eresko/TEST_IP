<?php

namespace App\Dto\Notification;

use App\Enums\ChannelType;
/**
 *  Для отправки сообщения
 *
 * @property int                    $userId                     ID пользователя
 * @property int                    $authorId                   ID автора тоже изтаблицы потзователей
 * @property int                    $notificationId             ID в таблице сообщений
 * @property string                 $message                    Тело сообщения
 * @property ChannelType            $channel                    Канал
 *
 */
class MessageDto {

    public function __construct(
        private int $userId,
        private int $authorId,
        private int $notificationId,
        private string $message,
        private ChannelType $channel,
    )
    {
    }

    /**
     * @return int
     */
    public function getUserId(): int {
        return $this->userId;
    }

    /**
     * @return int
     */
    public function getAuthorId(): int {
        return $this->authorId;
    }

    /**
     * @return int
     */
    public function getNotificationId(): int {
        return $this->notificationId;
    }

    /**
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * @return ChannelType
     */
    public function getChannel(): ChannelType {
        return $this->channel;
    }
}
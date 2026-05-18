<?php

namespace App\Dto\Notification;

use App\Enums\ChannelType;
use App\Enums\Queue;
/**
 *  Для отправки сообщения
 *
 * @property int                    $userId                     ID пользователя
 * @property int                    $authorId                   ID автора тоже изтаблицы потзователей
 * @property string                 $message                    Тело сообщения
 * @property ChannelType            $channel                    Канал
 * @property Queue                  $queue                      Очередь срочная или нет
 *
 */
class NotificationCreateDto {

    public function __construct(
        private int $userId,
        private int $authorId,
        private string $message,
        private ChannelType $channel,
        private Queue $queue = Queue::DEFAULT,
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

    /**
     * @return Queue
     */
    public function getQueue(): Queue {
        return $this->queue;
    }
}
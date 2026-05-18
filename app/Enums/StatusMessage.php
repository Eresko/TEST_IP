<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusMessage: string
{
    case WAITING = 'waiting';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case DISCARDED = 'discarded';


    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'В ожидании',
            self::SENT => 'Отправлено',
            self::DELIVERED => 'Доставлено',
            self::DISCARDED => 'Отброшено',
        };
    }

    /**
     * Проверка, находится ли отчет в конечном состоянии
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED]);
    }
}

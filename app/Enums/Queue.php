<?php

declare(strict_types=1);

namespace App\Enums;

enum Queue: string
{
    case HIGH = 'high';
    case DEFAULT = 'default';
    public function label(): string
    {
        return match ($this) {
            self::HIGH => 'Наивысший',
            self::DEFAULT => 'По умолчанию',
        };
    }

}

<?php

namespace App\Enums;

enum ChannelType: string
{
    case SMS = 'sms';
    case EMAIL = 'email';
    
    public function label(): string
    {
        return match($this) {
            self::SMS => 'смс',
            self::EMAIL => 'Электронная почта',
        };
    }
}

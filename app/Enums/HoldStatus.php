<?php

namespace App\Enums;

enum HoldStatus: string
{
    case HELD = 'held';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}

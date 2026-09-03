<?php

namespace App\Enums;

enum OrderStatus: string
{
    case CREATED = 'created';
    case PAID = 'paid';
    case DELIVERING = 'delivering';
    case DELIVERED = 'delivered';
    case PAYMENT_FAILED = 'payment_failed';
    case OUT_OF_STOCK = 'out_of_stock';
    case DELIVERY_FAILED = 'delivery_failed';

    /**
     * Проверка корректности переходов (Конечный автомат)
     */
    public function canTransitionTo(self $target): bool
    {
        
        if ($this === $target) {
            return true;
        }

        return match ($this) {
            self::CREATED => in_array($target, [self::PAID, self::PAYMENT_FAILED]),
            self::PAID => in_array($target, [self::DELIVERING, self::OUT_OF_STOCK, self::DELIVERY_FAILED]),
            self::DELIVERING => in_array($target, [self::DELIVERED, self::OUT_OF_STOCK, self::DELIVERY_FAILED]),
            self::OUT_OF_STOCK => in_array($target, [self::DELIVERING, self::DELIVERED, self::DELIVERY_FAILED]),
            self::DELIVERY_FAILED => in_array($target, [self::DELIVERING, self::DELIVERED, self::OUT_OF_STOCK]),
            self::DELIVERED, self::PAYMENT_FAILED => false,
        };
    }
}

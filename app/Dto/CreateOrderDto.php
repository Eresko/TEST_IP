<?php

declare(strict_types=1);

namespace App\Dto;

class CreateOrderDto
{
    public function __construct(
        public readonly string $sku,
        public readonly string $idempotencyKey,
    ) {
    }
    
}

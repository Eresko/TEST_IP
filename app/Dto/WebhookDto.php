<?php

declare(strict_types=1);

namespace App\Dto;

class WebhookDto
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $paymentId,
    ) {
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return ['order_id' => $this->orderId, 'payment_id' => $this->paymentId];
    }
}

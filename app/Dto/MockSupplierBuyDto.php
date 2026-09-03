<?php

declare(strict_types=1);

namespace App\Dto;

class MockSupplierBuyDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $partnerOrderId,
        public readonly string $sku,
    ) {
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return ['name' => $this->name,'partner_order_id' => $this->partnerOrderId, 'sku' => $this->sku];
    }
}

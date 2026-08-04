<?php

namespace App\Dto\Slot;

class CreateHoldDto
{
    public function __construct(
        private readonly int $slotId,
        private readonly string $idempotencyKey
    ) {}

    public function getSlotId(): int
    {
        return $this->slotId;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }
}

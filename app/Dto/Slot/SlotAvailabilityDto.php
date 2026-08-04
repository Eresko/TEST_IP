<?php

namespace App\Dto\Slot;

class SlotAvailabilityDto
{
    public function __construct(
        private readonly ?string $sort = null,
        private readonly ?int $page = 1,
        private readonly ?int $perPage = 10,
    ) {}

    public function getSort(): ?string
    {
        return $this->sort;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
    }
}

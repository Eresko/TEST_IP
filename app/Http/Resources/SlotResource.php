<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Slot
 */
class SlotResource extends JsonResource
{
    public function toArray($request)
    {

        return [
            'slot_id' => $this->id,
            'capacity' => $this->capacity,
            'remaining' => $this->remaining,
        ];
    }
}
<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Notification
 */
class NotificationResource extends JsonResource
{
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'user' => UserResource::make($this->whenLoaded('user')),
            'author' => UserResource::make($this->whenLoaded('author')),
            'status' => $this->status,
            'channel' => $this->channel,
            'message' => $this->message,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
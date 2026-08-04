<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HoldStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $idempotencyKey,
        public string $status,
        public string $message
    ) {}

    /**
     * Канал сокетов, куда фронтенд будет подключаться по UUID
     */
    public function broadcastOn(): Channel
    {
        return new Channel("holds.{$this->idempotencyKey}");
    }

    /**
     * Имя события на фронтенде
     */
    public function broadcastAs(): string
    {
        return 'HoldStatusUpdated';
    }
}

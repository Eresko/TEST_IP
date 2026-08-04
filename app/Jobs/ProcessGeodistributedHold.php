<?php

namespace App\Jobs;

use App\Services\SlotServices\HoldService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGeodistributedHold implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Конструктор принимает только чистые данные (массив)
     */
    public function __construct(protected array $data) {}

    /**
     * Метод handle занимается только оркестрацией: вызвал сервис -> передал данные
     */
    public function handle(HoldService $holdService): void
    {
        $holdService->executeGeodistributedHold($this->data);
    }
}

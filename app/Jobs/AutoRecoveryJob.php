<?php

namespace App\Jobs;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Jobs\DeliverProductJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 *  Толкаем зависшие заказы.
 */
class AutoRecoveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Запуск процесса восстановления заказов которые зависли
     */
    public function handle(): void
    {

        $stuckOrders = Order::whereIn('status', [
            OrderStatus::PAID->value,
            OrderStatus::DELIVERING->value,
            OrderStatus::DELIVERY_FAILED->value
        ])
            ->where('updated_at', '<', now()->subMinutes(5))
            ->whereNull('issued_product_code')
            ->get();

        if ($stuckOrders->isEmpty()) {
            return;
        }

        foreach ($stuckOrders as $order) {
            Log::warning('Auto-recovery triggered for stuck order', [
                'context' => 'auto_recovery',
                'order_id' => $order->id,
                'current_status' => $order->status,
                'updated_at' => $order->updated_at->toIso8601String()
            ]);

            DeliverProductJob::dispatch($order->id)->onQueue('delivery_processing');
        }
    }
}
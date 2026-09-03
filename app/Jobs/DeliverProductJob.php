<?php

namespace App\Jobs;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Services\DeliveryService\ProductDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    

    public int $tries = 6;

    /**
     * Таймаут выполнения самой джобы воркером (в секундах).
     * Защищает поток воркера от бесконечного зависания при сетевых сбоях операционной системы.
     */
    public int $timeout = 30;

    /**
     * Создать новый экземпляр задачи.
     */
    public function __construct(
        protected string $orderId
    ) {}

    /**
     * ЭТАП 3: Нативный экспоненциальный backOff
     */
    /** @var array<int, int> */
    public array $backoff = [5, 25, 125];

    /**
     * Точка входа воркера очереди.
     * Переменная $deliveryService автоматически внедрится через Dependency Injection (DI контейнер).
     */
    public function handle(ProductDeliveryService $deliveryService): void
    {

        $order = Order::find($this->orderId);


        if (!$order) {
            Log::error("DeliverProductJob: Заказ {$this->orderId} не найден в базе данных.");
            return;
        }

        /**
         * Проверяем на достувку или сбойный заказ
         */
        if (in_array($order->status, [OrderStatus::DELIVERED->value, OrderStatus::DELIVERY_FAILED->value])) {
            Log::info("DeliverProductJob: Заказ {$this->orderId} уже имеет конечный статус: {$order->status}. Выдача отменена.");
            return;
        }

        /**
         * Проверяем что заказ оплачен и метим что в процессе доставки
         */
        if ($order->status === OrderStatus::PAID->value) {
            $order->update(['status' => OrderStatus::DELIVERING->value]);
        }

        Log::info("Запуск бизнес-логики выдачи для заказа {$order->id}. Системная попытка воркера: " . $this->attempts());

        $deliveryService->deliver($order);
    }

    /**
     * Метод обрабатывает ситуации, когда ВСЕ 6 попыток (tries) были исчерпаны,
     * а исключение так и не было обработано.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical("DeliverProductJob: Задача выдачи заказа {$this->orderId} окончательно провалена после всех ретраев.", [
            'error' => $exception->getMessage()
        ]);

        /**
         * Переводим в FAILED если все упало
         */
        $order = Order::find($this->orderId);
        if ($order && $order->status !== OrderStatus::DELIVERED->value) {
            $order->update(['status' => OrderStatus::DELIVERY_FAILED->value]);
        }
    }
}

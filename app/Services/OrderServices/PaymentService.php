<?php

namespace App\Services\OrderServices;

use App\Models\Order;
use App\Models\FinancialLedger;
use App\Models\CatalogStock;
use App\Enums\OrderStatus;
use App\Jobs\DeliverProductJob;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Cache;
use App\Dto\WebhookDto;
use Illuminate\Contracts\Cache\LockTimeoutException;
use App\Exceptions\BusinessException;
/**
 * Сервис управления заказами и проведения платежей (Этапы 1, 2, 4).
 */
class PaymentService
{
    /**
     * Обработка вебхука оплаты с абсолютной защитой от гонок (Этап 2).
     *
     * @param WebhookDto $dto
     * @return bool
     * @throws Exception
     */
    public function processPayment(WebhookDto $dto): bool
    {
        /**
         * ЭТАП 2 Атомарная блокировка в KeyDB на 10 секунд.
         */
        $lock = Cache::lock("lock:order_payment:{$dto->orderId}", 10);

        try {
            return $lock->block(5, function () use ($dto) {
                return DB::transaction(function () use ($dto) {

                    $order = Order::where('id', $dto->orderId)->lockForUpdate()->first();

                    if (!$order) {
                        // Используем кастомное бизнес-исключение
                        throw new BusinessException("Заказ не найден", 404);
                    }
                    $currentStatus = $order->status instanceof \BackedEnum ? $order->status->value : $order->status;

                    /**
                     * Если параллельный запрос дождался освобождения лока,
                     * он увидит статус PAID/DELIVERING, вернет true и завершит работу без ошибок.
                     */
                    if (in_array($currentStatus, [
                        OrderStatus::PAID->value,
                        OrderStatus::DELIVERING->value,
                        OrderStatus::DELIVERED->value
                    ])) {
                        return true;
                    }

                    if ($currentStatus !== OrderStatus::CREATED->value) {
                        throw new BusinessException("Конфликт статуса: заказ не может быть оплачен", 409);
                    }

                    $order->update([
                        'status' => OrderStatus::PAID->value,
                        'payment_idempotency_key' => $dto->paymentId,
                    ]);

                    /**
                     * ЭТАП 4: Фиксация денег в журнале двойной записи.
                     */
                    FinancialLedger::create([
                        'order_id' => $order->id,
                        'type' => 'deposit',
                        'amount_cents' => $order->price_cents,
                        'ledger_idempotency_key' => "tx_pay_{$dto->paymentId}"
                    ]);

                    /**
                     * ЭТАП 3 & 4: Асинхронная выдача.
                     */
                    DeliverProductJob::dispatch($order->id)->onQueue('delivery_processing')->afterCommit();

                    return true;
                });
            });
        } catch (LockTimeoutException $e) {
            throw new BusinessException("Запрос уже обрабатывается. Пожалуйста, подождите.", 409);
        }
    }





}
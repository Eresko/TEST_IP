<?php

namespace App\Services\OrderServices;

use App\Models\Order;
use App\Models\FinancialLedger;
use App\Models\CatalogStock;
use App\Enums\OrderStatus;
use App\Jobs\DeliverProductJob;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Сервис управления заказами и проведения платежей (Этапы 1, 2, 4).
 */
class OrderService
{
    /**
     * Создание нового заказа (Этап 1).
     *
     * @param string $sku
     * @return Order
     * @throws Exception
     */
    public function createOrder(string $sku): Order
    {
        return DB::transaction(function () use ($sku) {
            // Проверяем остатки на витрине каталога (Этап 5) с пессимистичной блокировкой для точности
            $stock = CatalogStock::where('sku', $sku)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$stock || $stock->available_count <= 0) {
                throw new Exception("Товара {$sku} нет в наличии", 422);
            }

            // Имитируем фиксацию стоимости на момент заказа
            $priceCents = 10000; // Например, фиксированная цена 100.00 руб/ед

            // Создаем заказ в статусе created
            return Order::create([
                'sku' => $sku,
                'status' => OrderStatus::CREATED->value,
                'price_cents' => $priceCents
            ]);
        });
    }

    

    /**
     * @param string $orderId
     * @return Order|null
     */
    public function getById(string $orderId):?Order {
        return Order::find($orderId);
    }
}

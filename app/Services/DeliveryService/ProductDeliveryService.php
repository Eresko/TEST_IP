<?php

namespace App\Services\DeliveryService;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Exception;

class ProductDeliveryService
{
    /**
     * Основной метод асинхронной доставки товара.
     *
     * @throws ConnectionException При сетевых таймаутах (требует ретрая той же ноды)
     * @throws Exception При жестких отказах или критических сбоях
     */
    public function deliver(Order $order): void
    {
        // 1. Инициализируем стартового поставщика в БД, если это первая попытка
        if (!$order->supplier_id) {
            $order->update(['supplier_id' => 'supplier_a']);
            $order->refresh();
        }

        $supplier = $order->supplier_id;

        try {
            /**
             * Проверка на выдачу товара
             */
            if ($this->checkSupplierStatus($order, $supplier)) {
                return;
            }
            
            $this->requestProductIssuance($order, $supplier);

        } catch (ConnectionException $e) {
            Log::warning("Сетевой таймаут с {$supplier} для заказа {$order->id}. Ожидаем повтор.");
            throw $e;

        } catch (Exception $e) {
            $this->handleSupplierFailure($order, $supplier, $e);
        }
    }

    /**
     * Проверка статуса заказа на стороне поставщика (Анти-дубль защита) [Этап 3]
     */
    private function checkSupplierStatus(Order $order, string $supplier): bool
    {
        try {
            $baseUrl = rtrim(config('app.url'), '/');

            $response = Http::timeout(3)
                ->get("{$baseUrl}/api/v1/mock-supplier/{$supplier}/status", [
                    'partner_order_id' => $order->id
                ]);

            if ($response->successful() && isset($response->json()['code'])) {
                $this->finalizeOrder($order, $supplier, $response->json()['code']);
                return true;
            }
        } catch (ConnectionException $e) {
            Log::warning("Метод проверки статуса {$supplier} недоступен по таймауту.");
        }

        return false;
    }

    /**
     * Прямой запрос на генерацию/покупку цифрового товара [Этап 1]
     */
    private function requestProductIssuance(Order $order, string $supplier): void
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $response = Http::connectTimeout(2)
            ->timeout(3)
            ->post("{$baseUrl}/api/v1/mock-supplier/{$supplier}/buy", [
                'partner_order_id' => $order->id,
                'sku' => $order->sku
            ]);

        if ($response->successful() && isset($response->json()['code'])) {
            $this->finalizeOrder($order, $supplier, $response->json()['code']);
            return;
        }

        throw new Exception("Шлюз вернул ошибку выполнения: " . $response->status());
    }

    /**
     * Логика безопасного переключения поставщиков (Fallback AB) [Этап 3]
     */
    private function handleSupplierFailure(Order $order, string $currentSupplier, Exception $exception): void
    {
        if ($currentSupplier === 'supplier_a') {
            Log::warning("Поставщик А вернул жесткий отказ для заказа {$order->id}. Мигрируем на Fallback Supplier B.");

            $order->update(['supplier_id' => 'supplier_b']);
            
            throw new Exception("Переключение на резервного поставщика.");
        }


        $order->update(['status' => OrderStatus::DELIVERY_FAILED->value]);
        Log::error("Заказ {$order->id} переведен в FAILED. Оба поставщика недоступны.");

        throw $exception;
    }

    /**
     * Фиксация успешного завершения выдачи [Этап 1]
     */
    private function finalizeOrder(Order $order, string $supplier, string $code): void
    {
        $order->update([
            'status' => OrderStatus::DELIVERED->value,
            'supplier_id' => $supplier,
            'issued_product_code' => $code
        ]);

        Log::info("Заказ {$order->id} успешно доставлен геймеру от {$supplier}.");
    }
}

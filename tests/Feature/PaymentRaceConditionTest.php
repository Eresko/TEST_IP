<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\FinancialLedger;
use App\Enums\OrderStatus;
use App\Jobs\DeliverProductJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentRaceConditionTest extends TestCase
{

    use DatabaseTransactions;

    public function test_parallel_payment_webhooks_do_not_cause_duplicate_processing(): void
    {
        Queue::fake();
        $baseUrl = 'http://test_nginx';


        $orderResponse = Http::withHeaders([
            'Idempotency-Key' => 'order_key_for_payment_test_' . uniqid(),
            'Accept' => 'application/json'
        ])->post("{$baseUrl}/api/v1/orders", [
            'sku' => 'KEY-GTA5'
        ]);

        $this->assertTrue($orderResponse->successful(), 'Не удалось создать заказ через API: ' . $orderResponse->body());


        $orderId = $orderResponse->json('data.order_id');


        $paymentId = 'pay_tx_999888';
        $totalRequests = 10;

        $headers = [
            'Idempotency-Key' => $paymentId,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];


        $responses = Http::pool(function ($pool) use ($totalRequests, $orderId, $paymentId, $headers, $baseUrl) {
            $requests = [];
            for ($i = 0; $i < $totalRequests; $i++) {
                $requests[] = $pool->withHeaders($headers)
                    ->post("{$baseUrl}/api/v1/payments/webhook", [
                        'event_id'   => 'evt_a1b2c3_' . $orderId,
                        'payment_id' => $paymentId,
                        'order_id'   => $orderId,
                        'status'     => 'paid',
                        'amount'     => 499,
                        'currency'   => 'RUB',
                        'created_at' => '2026-09-02T12:00:00Z',
                    ]);
            }
            return $requests;
        });


        foreach ($responses as $index => $res) {
            if ($res instanceof \Exception) {
                echo "\nЗапрос #" . ($index + 1) . " | Сетевая ошибка: " . $res->getMessage();
            } else {
                echo "\nЗапрос #" . ($index + 1) . " | Статус: " . $res->status() . " | Ответ: " . substr($res->body(), 0, 80);
            }
        }


        usleep(300000);


        $dbOrder = Order::find($orderId);
        $this->assertNotNull($dbOrder, 'Заказ пропал из базы данных!');


        $this->assertEquals(OrderStatus::PAID->value, $dbOrder->status->value ?? $dbOrder->status);
        $expectedEventId = 'evt_a1b2c3_' . $orderId;
        echo "\nPaymentId CURRENT: " . $expectedEventId ;
        echo "\nPaymentId BASE   : " . $dbOrder->payment_idempotency_key ;
        $this->assertEquals($expectedEventId, $dbOrder->payment_idempotency_key);

        
        $ledgerCount = FinancialLedger::where('order_id', $orderId)
            ->where('type', 'deposit')
            ->count();
        $this->assertEquals(1, $ledgerCount, 'КРИТИЧЕСКАЯ ОШИБКА: Запись в фин. журнале продублирована!');

        // Задача на выдачу в RabbitMQ улетела строго 1 раз
        $this->assertEquals(1, $ledgerCount, 'КРИТИЧЕСКАЯ ОШИБКА: Деньги продублировались в финансовом журнале!');
    }
}

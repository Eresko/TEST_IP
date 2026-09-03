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
use Illuminate\Http\Client\Factory;
class PaymentRaceConditionTest extends TestCase
{

    use DatabaseTransactions;

    public function test_parallel_payment_webhooks_do_not_cause_duplicate_processing(): void
    {
        Queue::fake();

        Http::fake([
            '*/buy' => Http::response(['success' => true, 'product_code' => 'MOCK-KEY-123'], 200),
            '*/status' => Http::response(['success' => true, 'status' => 'completed'], 200),
        ]);

        $order = Order::create([
            'sku' => 'KEY-GTA5',
            'status' => OrderStatus::CREATED->value,
            'price_cents' => 10000
        ]);

        $paymentId = 'pay_tx_999888';
        $headers = [
            'Idempotency-Key' => $paymentId,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $payload = [
            'event_id'   => 'evt_a1b2c3_' . $order->id,
            'payment_id' => $paymentId,
            'order_id'   => $order->id,
            'status'     => 'paid',
            'amount'     => 100,
            'currency'   => 'RUB',
            'created_at' => '2026-09-02T12:00:00Z',
        ];


        $response1 = $this->withHeaders($headers)->postJson('/api/v1/payments/webhook', $payload);
        $response1->assertStatus(200);

        $response2 = $this->withHeaders($headers)->postJson('/api/v1/payments/webhook', $payload);
        $response2->assertStatus(200);

        $dbOrder = Order::find($order->id);
        $this->assertNotNull($dbOrder, 'Заказ пропал из базы данных!');


        $this->assertEquals(OrderStatus::PAID->value, $dbOrder->status->value ?? $dbOrder->status);


        $expectedEventId = 'evt_a1b2c3_' . $order->id;
        $this->assertEquals($expectedEventId, $dbOrder->payment_idempotency_key);

        
        $ledgerCount = FinancialLedger::where('order_id', $order->id)
            ->where('type', 'deposit')
            ->count();
        $this->assertEquals(1, $ledgerCount, 'КРИТИЧЕСКАЯ ОШИБКА: Запись в фин. журнале продублирована!');
    }

}

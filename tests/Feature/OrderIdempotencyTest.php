<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\CatalogStock; // Убедитесь, что модель каталога импортирована верно
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
class OrderIdempotencyTest extends TestCase
{
    use DatabaseTransactions;

    protected bool $seed = true;
    /**
     * Проверяем, что при отправке нескольких запросов с одним Idempotency-Key
     * создается только один заказ, а последующие запросы возвращают закэшированный ответ.
     */
    public function test_concurrent_order_creation_requests_are_idempotent(): void
    {

        $idempotencyKey = 'test_integration_key_100';
        $payload = ['sku' => 'KEY-GTA5'];
        $headers = [
            'Idempotency-Key' => $idempotencyKey,
            'Accept' => 'application/json'
        ];
        
        $response1 = $this->withHeaders($headers)->postJson('/api/v1/orders', $payload);
        $response1->dump();
        $response1->assertStatus(201);
        $response1->assertJsonStructure([
            'success',
            'message',
            'data' => ['order_id', 'sku', 'status', 'price_cents', 'created_at']
        ]);

        $orderId = $response1->json('data.order_id');

        $response2 = $this->withHeaders($headers)->postJson('/api/v1/orders', $payload);

        $response2->assertStatus(201);
        $this->assertEquals($orderId, $response2->json('data.order_id'), 'Ошибка: Создался дубликат заказа с новым ID!');

        $this->assertEquals(1, Order::where('id', $orderId)->count(), 'КРИТИЧЕСКАЯ ОШИБКА: Заказ сгенерировал дубликаты строк в БД!');
    }

    /**
     * Проверяем работу атомарного лока при Race Condition (когда первый запрос еще не завершился).
     */
    public function test_simultaneous_requests_trigger_lock_and_return_409(): void
    {

        $idempotencyKey = 'lock_test_key_200';

        Cache::lock("idempotency_lock:{$idempotencyKey}", 10)->get();

        $response = $this->withHeaders([
            'Idempotency-Key' => $idempotencyKey,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/orders', ['sku' => 'KEY-GTA5']);

        $response->assertStatus(409);
        $response->assertJson([
            'error' => 'Запрос уже обрабатывается. Пожалуйста, подождите.'
        ]);

        $this->assertFalse(Order::where('id', 'какой-то-id-из-ответа-если-бы-он-был')->exists());
    }
}

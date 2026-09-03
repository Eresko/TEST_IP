<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
class OrderIdempotencyTest extends TestCase
{

    use RefreshDatabase;


    protected bool $seed = true;

    /**
     * Тест 1: Проверка успешной повторной отдачи ответа из кэша
     */
    public function test_concurrent_order_creation_requests_are_idempotent(): void
    {
        $idempotencyKey = 'test_integration_key_100';
        $payload = ['sku' => 'KEY-GTA5'];
        $headers = [
            'Idempotency-Key' => $idempotencyKey,
            'Accept' => 'application/json'
        ];

        $initialCount = Order::count();


        $response1 = $this->withHeaders($headers)->postJson('/api/v1/orders', $payload);
        $response1->assertStatus(201);
        $orderId = $response1->json('data.order_id');


        $response2 = $this->withHeaders($headers)->postJson('/api/v1/orders', $payload);
        $response2->assertStatus(201);

        $this->assertEquals($orderId, $response2->json('data.order_id'), 'Ошибка: Создался дубликат заказа с новым ID!');
        $this->assertEquals($initialCount + 1, Order::count(), 'КРИТИЧЕСКАЯ ОШИБКА: В СУБД создано несколько физических строк заказов!');
    }

    /**
     * Тест 2: Проверка блокировки 409 Conflict при Race Condition
     */
    public function test_simultaneous_requests_trigger_lock_and_return_409(): void
    {
        $idempotencyKey = 'lock_test_key_200';
        $initialCount = Order::count();

        // Искусственно занимаем лок в Redis перед отправкой
        Cache::lock("idempotency_lock:{$idempotencyKey}", 10)->get();

        $response = $this->withHeaders([
            'Idempotency-Key' => $idempotencyKey,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/orders', ['sku' => 'KEY-GTA5']);

        $response->assertStatus(409);
        $response->assertJson([
            'error' => 'Запрос уже обрабатывается. Пожалуйста, подождите.'
        ]);

        $this->assertEquals($initialCount, Order::count(), 'Ошибка: Запрос проигнорировал лок и создал запись в БД!');
    }
}

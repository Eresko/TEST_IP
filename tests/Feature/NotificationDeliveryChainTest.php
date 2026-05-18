<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Enums\StatusMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue as LaravelQueue;

class NotificationDeliveryChainTest extends TestCase
{
    /**
     * Тест сосздания сообщения
     */
    public function test_full_notification_delivery_chain_success(): void
    {

        Http::fake([
            'smsc.ru/*' => Http::response(['id' => 777, 'cnt' => 1], 200),
        ]);
        LaravelQueue::fake();


        $user = User::first();

        if (!$user) {
            $user = User::factory()->create(['phone' => '+79991112233']);
            $author = User::factory()->create();
        } else {
            $author = User::skip(1)->first() ?? $user;
        }

        if (!$user->phone) {
            $user->update(['phone' => '+79991112233']);
        }


        $payload = [
            'user_id' => (int) $user->id,
            'author_id' => (int) $author->id,
            'message' => 'Груз готов к отправке!',
            'channel' => 'sms',
            'queue' => 'high'
        ];

        $response = $this->postJson('/api/notifications', $payload);


        $response->assertStatus(201)
            ->assertJsonFragment([
                'message' => 'Груз готов к отправке!',
                'channel' => 'sms'
            ]);


        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'message' => 'Груз готов к отправке!',
        ]);


        LaravelQueue::assertPushed(\App\Jobs\SendMessage::class, function ($job) {
            return $job->queue === 'high';
        });
    }
}

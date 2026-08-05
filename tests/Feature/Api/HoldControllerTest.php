<?php

namespace Tests\Feature\Api;

use App\Models\Slot;
use App\Jobs\ProcessGeodistributedHold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Illuminate\Support\Str;

class HoldControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_can_create_hold_successfully_asynchronous(): void
    {

        Queue::fake();


        $slot = Slot::factory()->create([
            'capacity' => 10,
            'remaining' => 10,
        ]);
        Cache::put("slots:{$slot->id}:remaining", 10, 60);
        $idempotencyKey = Str::uuid()->toString();


        $response = $this->withHeaders([
            'Idempotency-Key' => $idempotencyKey,
            'Accept' => 'application/json',
        ])->postJson("/api/slots/{$slot->id}/hold");

        $response->assertStatus(202);


        $response->assertJsonPath('status', 'pending');

        $returnedSlotId = (int) $response->json('slot_id');

        $this->assertEquals((int) $slot->id, $returnedSlotId);
        Queue::assertPushedOn('hold_processing', ProcessGeodistributedHold::class);


        $this->assertEquals(9, Cache::get("slots:{$slot->id}:remaining"));
    }

    public function test_returns_409_when_no_places_available_in_cache(): void
    {
        $slot = Slot::factory()->create(['remaining' => 0]);
        $idempotencyKey = 'f85b306b-73b2-4809-9f7a-c1724f8d6499';

        Cache::forget("idempotency:{$idempotencyKey}");
        Cache::forget("idempotency_lock:{$idempotencyKey}");

        Cache::put("slots:{$slot->id}:remaining", 0, 60);
        $response = $this->withHeaders([
            'Idempotency-Key' => $idempotencyKey,
            'Accept' => 'application/json',
        ])->postJson("/api/slots/{$slot->id}/hold");

        $response->assertStatus(409);
    }
}

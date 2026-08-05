<?php

namespace Tests\Feature\Api;

use App\Models\Slot;
use App\Jobs\ProcessGeodistributedHold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HoldControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_hold_successfully_asynchronous(): void
    {

        Queue::fake();


        $slot = Slot::factory()->create([
            'capacity' => 10,
            'remaining' => 10,
        ]);

        $idempotencyKey = 'test-uuid-12345';


        $response = $this->withHeaders([
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson("/api/slots/{$slot->id}/hold");


        $response->assertStatus(202)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.slot_id', $slot->id);


        Queue::assertPushedOn('hold_processing', ProcessGeodistributedHold::class);


        $this->assertEquals(9, Cache::get("slots:{$slot->id}:remaining"));
    }

    public function test_returns_409_when_no_places_available_in_cache(): void
    {
        $slot = Slot::factory()->create(['remaining' => 0]);


        Cache::put("slots:{$slot->id}:remaining", 0, 60);

        $response = $this->withHeaders([
            'Idempotency-Key' => 'test-uuid-54321',
        ])->postJson("/api/slots/{$slot->id}/hold");

        
        $response->assertStatus(409);
    }
}

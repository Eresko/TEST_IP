<?php

namespace Tests\Unit\Services;

use App\Models\Slot;
use App\Models\Hold;
use App\Services\SlotServices\HoldService;
use App\Events\HoldStatusUpdated;
use App\Enums\HoldStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HoldServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_correctly_saves_hold_to_database_and_fires_event(): void
    {

        Event::fake();

        $slot = Slot::factory()->create([
            'capacity' => 5,
            'remaining' => 5
        ]);

        $data = [
            'slot_id' => $slot->id,
            'idempotency_key' => 'c1724f8d-73b2-4809-9f7a-85b306b86432'
        ];


        $service = app(HoldService::class);
        $service->executeGeodistributedHold($data);


        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'remaining' => 4
        ]);


        $this->assertDatabaseHas('holds', [
            'slot_id' => $slot->id,
            'idempotency_key' => 'c1724f8d-73b2-4809-9f7a-85b306b86432',
            'status' => HoldStatus::HELD->value
        ]);


        Event::assertDispatched(HoldStatusUpdated::class);
    }
}

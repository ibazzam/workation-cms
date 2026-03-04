<?php

namespace Tests\Feature;

use App\Models\TransportHold;
use App\Models\TransportInventory;
use App\Models\TransportSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportHoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_hold_and_idempotency()
    {
        // create schedule and inventory
        $schedule = TransportSchedule::create([
            'route_id' => 1,
            'operator_id' => 1,
            'departure_at' => now(),
        ]);

        $inventory = TransportInventory::create([
            'schedule_id' => $schedule->id,
            'seat_class' => 'standard',
            'total_seats' => 10,
            'reserved_seats' => 0,
        ]);

        $response = $this->postJson('/api/transport/holds', [
            'schedule_id' => $schedule->id,
            'seat_class' => 'standard',
            'seats' => 2,
            'idempotency_key' => 'key-1',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('transport_holds', [
            'idempotency_key' => 'key-1',
            'seats_reserved' => 2,
            'status' => 'held',
        ]);

        // repeat with same idempotency key -> should return same hold (no double reserve)
        $response2 = $this->postJson('/api/transport/holds', [
            'schedule_id' => $schedule->id,
            'seat_class' => 'standard',
            'seats' => 2,
            'idempotency_key' => 'key-1',
        ]);

        $response2->assertStatus(201);

        $this->assertDatabaseCount('transport_holds', 1);
        $this->assertDatabaseHas('transport_inventory', [
            'schedule_id' => $schedule->id,
            'seat_class' => 'standard',
            'reserved_seats' => 2,
        ]);
    }
}

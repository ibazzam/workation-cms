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
        // ensure required related records exist (operator, route, islands)
        if (\Illuminate\Support\Facades\Schema::hasTable('transport_operators')) {
            $operatorId = \Illuminate\Support\Facades\DB::table('transport_operators')->insertGetId([
                'name' => 'test-operator',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $operatorId = 1;
        }

        // create islands if present
        if (\Illuminate\Support\Facades\Schema::hasTable('islands')) {
            $originId = \Illuminate\Support\Facades\DB::table('islands')->insertGetId(['name' => 'origin', 'created_at' => now(), 'updated_at' => now()]);
            $destId = \Illuminate\Support\Facades\DB::table('islands')->insertGetId(['name' => 'dest', 'created_at' => now(), 'updated_at' => now()]);
        } else {
            $originId = 1;
            $destId = 1;
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('transport_routes')) {
            $isSqlite = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite';
            if ($isSqlite) {
                // SQLite in-memory tests may need FK toggles for seed convenience.
                try {
                    \Illuminate\Support\Facades\DB::getPdo()->exec('PRAGMA foreign_keys = OFF');
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            $routeId = \Illuminate\Support\Facades\DB::table('transport_routes')->insertGetId([
                'origin_island_id' => $originId,
                'destination_island_id' => $destId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($isSqlite) {
                try {
                    \Illuminate\Support\Facades\DB::getPdo()->exec('PRAGMA foreign_keys = ON');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        } else {
            $routeId = 1;
        }

        // create schedule and inventory
        $schedule = TransportSchedule::create([
            'route_id' => $routeId,
            'operator_id' => $operatorId,
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

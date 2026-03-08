<?php

namespace Tests\Feature;

use App\Console\Commands\ProcessTransportProviderJobs;
use App\Console\Commands\ReconcileTransportHolds;
use App\Models\TransportProviderJob;
use App\Models\TransportHold;
use App\Models\TransportInventory;
use App\Models\TransportSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportProviderJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_jobs_and_reconciliation()
    {
        // setup schedule and inventory
        if (\Illuminate\Support\Facades\Schema::hasTable('transport_operators')) {
            $operatorId = \Illuminate\Support\Facades\DB::table('transport_operators')->insertGetId(['name' => 'op', 'created_at' => now(), 'updated_at' => now()]);
        } else {
            $operatorId = 1;
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('islands')) {
            $originId = \Illuminate\Support\Facades\DB::table('islands')->insertGetId(['name' => 'o', 'created_at' => now(), 'updated_at' => now()]);
            $destId = \Illuminate\Support\Facades\DB::table('islands')->insertGetId(['name' => 'd', 'created_at' => now(), 'updated_at' => now()]);
        } else {
            $originId = 1; $destId = 1;
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('transport_routes')) {
            $isSqlite = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite';
            if ($isSqlite) {
                \Illuminate\Support\Facades\DB::getPdo()->exec('PRAGMA foreign_keys = OFF');
            }
            $routeId = \Illuminate\Support\Facades\DB::table('transport_routes')->insertGetId(['origin_island_id' => $originId, 'destination_island_id' => $destId, 'created_at' => now(), 'updated_at' => now()]);
            if ($isSqlite) {
                \Illuminate\Support\Facades\DB::getPdo()->exec('PRAGMA foreign_keys = ON');
            }
        } else {
            $routeId = 1;
        }

        $schedule = TransportSchedule::create(['route_id' => $routeId, 'operator_id' => $operatorId, 'departure_at' => now()]);

        $inventory = TransportInventory::create(['schedule_id' => $schedule->id, 'seat_class' => 'standard', 'total_seats' => 5, 'reserved_seats' => 0]);

        // create hold which should enqueue a provider job
        $hold = TransportHold::createHold($schedule->id, 'standard', 2, 'job-key-1', 60);

        $this->assertDatabaseHas('transport_provider_jobs', ['action' => 'hold_create', 'status' => 'pending']);

        // Bind a mock adapter so processing uses a predictable successful response
        $mock = $this->createMock(\App\Services\HttpTransportProviderAdapter::class);
        $mock->method('sendHoldCreate')->willReturn(['ok' => true, 'body' => []]);
        $mock->method('sendHoldConfirm')->willReturn(['ok' => true, 'body' => []]);
        $mock->method('sendHoldRelease')->willReturn(['ok' => true, 'body' => []]);
        $this->app->instance(\App\Services\HttpTransportProviderAdapter::class, $mock);

        // run processing command
        $this->artisan('transport:process-jobs --limit=10')->assertExitCode(0);

        $this->assertDatabaseHas('transport_provider_jobs', ['action' => 'hold_create', 'status' => 'completed']);

        // expire the hold and run reconciliation
        $hold->ttl_expires_at = now()->subSeconds(5);
        $hold->save();

        $this->artisan('transport:reconcile-holds --limit=10')->assertExitCode(0);

        $this->assertDatabaseHas('transport_holds', ['id' => $hold->id, 'status' => 'released']);
    }
}

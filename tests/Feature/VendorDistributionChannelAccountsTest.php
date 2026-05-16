<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VendorDistributionChannelAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_vendor_can_connect_channel_account(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-1',
        ]);

        $response = $this
            ->withSession($this->vendorSession($vendor))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/accounts/connect', [
                'channel_code' => 'booking',
                'account_reference' => 'Primary Booking Account',
                'webhook_secret' => 'booking-webhook-secret',
                'inventory_sync_url' => 'https://adapter.example.com/inventory/sync',
                'api_base' => 'https://adapter.example.com',
                'access_token' => 'token-abc-123',
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHas('status', 'BOOKING account connected successfully.');

        $account = DB::table('vendor_channel_accounts')
            ->where('vendor_user_id', $vendor->id)
            ->where('channel_code', 'booking')
            ->first();

        $this->assertNotNull($account);
        $this->assertSame('connected', (string) $account->status);
        $this->assertSame('Primary Booking Account', (string) $account->account_reference);

        $meta = json_decode((string) ($account->connection_meta ?? '{}'), true);
        $this->assertIsArray($meta);
        $this->assertSame('booking-webhook-secret', $meta['webhook_secret'] ?? null);
        $this->assertSame('https://adapter.example.com/inventory/sync', $meta['inventory_sync_url'] ?? null);
        $this->assertSame('https://adapter.example.com', $meta['api_base'] ?? null);

        $this->assertSame('token-abc-123', Crypt::decryptString((string) $account->access_token_encrypted));
    }

    public function test_vendor_can_disconnect_own_account_but_not_others(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-2',
        ]);

        $otherVendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-3',
        ]);

        $ownId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'channel_code' => 'agoda',
            'status' => 'connected',
            'connected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $otherVendor->id,
            'channel_code' => 'booking',
            'status' => 'connected',
            'connected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $disconnectOwn = $this
            ->withSession($this->vendorSession($vendor))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/accounts/' . $ownId . '/disconnect');

        $disconnectOwn
            ->assertStatus(302)
            ->assertSessionHas('status', 'AGODA account disconnected.');

        $this->assertDatabaseHas('vendor_channel_accounts', [
            'id' => $ownId,
            'status' => 'disconnected',
        ]);

        $ownAccount = DB::table('vendor_channel_accounts')->where('id', $ownId)->first();
        $this->assertNotNull($ownAccount);
        $ownMeta = json_decode((string) ($ownAccount->connection_meta ?? '{}'), true);
        $this->assertIsArray($ownMeta);
        $this->assertNotEmpty($ownMeta['disconnected_at'] ?? '');
        $this->assertSame('manual_vendor_disconnect', $ownMeta['disconnect_reason'] ?? null);

        $disconnectOther = $this
            ->withSession($this->vendorSession($vendor))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/accounts/' . $otherId . '/disconnect');

        $disconnectOther
            ->assertStatus(302)
            ->assertSessionHasErrors(['distribution']);

        $this->assertDatabaseHas('vendor_channel_accounts', [
            'id' => $otherId,
            'status' => 'connected',
        ]);
    }

    public function test_distribution_page_prefills_form_for_reconnect_target_account(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-4',
        ]);

        $accountId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'channel_code' => 'airbnb',
            'account_reference' => 'Airbnb Resync Account',
            'status' => 'disconnected',
            'connection_meta' => json_encode([
                'webhook_secret' => 'whsec-airbnb',
                'inventory_sync_url' => 'https://sync.example.com/airbnb/inventory',
                'api_base' => 'https://sync.example.com/airbnb',
            ], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession($this->vendorSession($vendor))
            ->get('/vendor?page=distribution&channel_account=' . $accountId);

        $response
            ->assertStatus(200)
            ->assertSee('Reconnect / Update OTA Account', false)
            ->assertSee('Airbnb Resync Account', false)
            ->assertSee('whsec-airbnb', false)
            ->assertSee('https://sync.example.com/airbnb/inventory', false)
            ->assertSee('https://sync.example.com/airbnb', false)
            ->assertSee('Save OTA Account', false);
    }

    public function test_connect_requires_channel_specific_endpoint_fields(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-5',
        ]);

        $bookingMissingInventory = $this
            ->withSession($this->vendorSession($vendor))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/accounts/connect', [
                'channel_code' => 'booking',
                'webhook_secret' => 'booking-secret',
                'api_base' => 'https://booking.example.com',
            ]);

        $bookingMissingInventory
            ->assertStatus(302)
            ->assertSessionHasErrors(['distribution']);

        $airbnbMissingApiBase = $this
            ->withSession($this->vendorSession($vendor))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/accounts/connect', [
                'channel_code' => 'airbnb',
                'webhook_secret' => 'airbnb-secret',
                'inventory_sync_url' => 'https://airbnb.example.com/inventory',
            ]);

        $airbnbMissingApiBase
            ->assertStatus(302)
            ->assertSessionHasErrors(['distribution']);

        $this->assertDatabaseCount('vendor_channel_accounts', 0);
    }

    public function test_vendor_can_requeue_failed_outbound_event_for_own_account(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-6',
        ]);

        $accountId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'channel_code' => 'booking',
            'status' => 'connected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eventId = (int) DB::table('vendor_channel_events')->insertGetId([
            'vendor_channel_account_id' => $accountId,
            'direction' => 'outbound',
            'event_type' => 'inventory.updated',
            'status' => 'dead_letter',
            'retry_count' => 3,
            'error_message' => 'simulated transport failure',
            'payload' => json_encode(['room_key' => 'DLX-101'], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession($this->vendorSession($vendor))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/events/' . $eventId . '/retry');

        $response
            ->assertStatus(302)
            ->assertSessionHas('status', 'Outbound event re-queued. Dispatcher will retry delivery automatically.');

        $this->assertDatabaseHas('vendor_channel_events', [
            'id' => $eventId,
            'status' => 'queued',
            'retry_count' => 3,
            'error_message' => null,
        ]);
    }

    public function test_vendor_can_dispatch_queued_outbound_event_immediately(): void
    {
        Http::fake([
            'https://adapter.example.com/inventory/sync' => Http::response(['ok' => true], 200),
        ]);

        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-7',
        ]);

        $accountId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'channel_code' => 'booking',
            'status' => 'connected',
            'connection_meta' => json_encode([
                'inventory_sync_url' => 'https://adapter.example.com/inventory/sync',
            ], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eventId = (int) DB::table('vendor_channel_events')->insertGetId([
            'vendor_channel_account_id' => $accountId,
            'direction' => 'outbound',
            'event_type' => 'inventory.updated',
            'status' => 'queued',
            'retry_count' => 0,
            'idempotency_key' => 'test-immediate-dispatch-1',
            'payload' => json_encode(['room_key' => 'DLX-102'], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession($this->vendorSession($vendor))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/events/' . $eventId . '/dispatch-now');

        $response
            ->assertStatus(302)
            ->assertSessionHas('status', 'Outbound event dispatched successfully.');

        $this->assertDatabaseHas('vendor_channel_events', [
            'id' => $eventId,
            'status' => 'processed',
        ]);
    }

    public function test_dispatch_now_is_rate_limited_per_vendor_session(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-8',
        ]);

        $accountId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'channel_code' => 'booking',
            'status' => 'connected',
            'connection_meta' => json_encode([
                'inventory_sync_url' => 'https://adapter.example.com/inventory/sync',
            ], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eventTwoId = (int) DB::table('vendor_channel_events')->insertGetId([
            'vendor_channel_account_id' => $accountId,
            'direction' => 'outbound',
            'event_type' => 'inventory.updated',
            'status' => 'queued',
            'retry_count' => 0,
            'idempotency_key' => 'test-immediate-dispatch-rate-2',
            'payload' => json_encode(['room_key' => 'DLX-104'], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this
            ->withSession(array_merge($this->vendorSession($vendor), [
                'portal_distribution_dispatch_now_last_at' => now()->timestamp,
            ]))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/events/' . $eventTwoId . '/dispatch-now')
            ->assertStatus(302)
            ->assertSessionHasErrors(['distribution']);

        $this->assertDatabaseHas('vendor_channel_events', [
            'id' => $eventTwoId,
            'status' => 'queued',
        ]);
    }

    public function test_distribution_page_shows_event_attempts_and_last_error_details(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-9',
        ]);

        $accountId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'channel_code' => 'booking',
            'status' => 'action_required',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_channel_events')->insert([
            'vendor_channel_account_id' => $accountId,
            'direction' => 'outbound',
            'event_type' => 'inventory.updated',
            'status' => 'failed',
            'retry_count' => 4,
            'error_message' => 'Transport timeout while contacting OTA endpoint',
            'payload' => json_encode(['room_key' => 'DLX-105'], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession($this->vendorSession($vendor))
            ->get('/vendor?page=distribution');

        $response
            ->assertStatus(200)
            ->assertSee('Attempts: 4')
            ->assertSee('Last error: Transport timeout while contacting OTA endpoint');
    }

    public function test_distribution_page_shows_go_live_readiness_checklist(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-10',
        ]);

        $accountId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'channel_code' => 'booking',
            'status' => 'action_required',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_channel_events')->insert([
            'vendor_channel_account_id' => $accountId,
            'direction' => 'outbound',
            'event_type' => 'inventory.updated',
            'status' => 'failed',
            'retry_count' => 1,
            'error_message' => 'Endpoint rejected payload',
            'payload' => json_encode(['room_key' => 'DLX-106'], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession($this->vendorSession($vendor))
            ->get('/vendor?page=distribution');

        $response
            ->assertStatus(200)
            ->assertSee('Go-Live Readiness', false)
            ->assertSee('NOT READY', false)
            ->assertSee('At least one OTA account connected', false)
            ->assertSee('No channel accounts in action required state', false)
            ->assertSee('need attention', false);
    }

    public function test_vendor_can_create_and_deactivate_room_mapping_from_distribution(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-DIST-11',
        ]);

        $propertyId = 501;
        $accountId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'channel_code' => 'booking',
            'status' => 'connected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roomCategoryId = (int) DB::table('vendor_property_room_categories')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'name' => 'Deluxe Lagoon Room',
            'quantity' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createResponse = $this
            ->withSession($this->vendorSession($vendor))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/room-mappings/save', [
                'vendor_channel_account_id' => $accountId,
                'external_room_id' => 'booking-dlx-lagoon',
                'external_room_name' => 'Booking Deluxe Lagoon',
                'external_rate_plan_id' => 'rate-flex',
                'external_rate_plan_name' => 'Flexible',
                'internal_room_category_id' => $roomCategoryId,
            ]);

        $createResponse
            ->assertStatus(302)
            ->assertSessionHas('status', 'Room mapping created successfully.');

        $mapping = DB::table('vendor_channel_room_mappings')
            ->where('vendor_channel_account_id', $accountId)
            ->where('external_room_id', 'booking-dlx-lagoon')
            ->first();

        $this->assertNotNull($mapping);
        $this->assertSame('active', (string) $mapping->mapping_status);
        $this->assertSame($roomCategoryId, (int) $mapping->internal_room_category_id);

        $deactivateResponse = $this
            ->withSession($this->vendorSession($vendor))
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/vendor/distribution/room-mappings/' . ((int) $mapping->id) . '/deactivate');

        $deactivateResponse
            ->assertStatus(302)
            ->assertSessionHas('status', 'Room mapping deactivated.');

        $this->assertDatabaseHas('vendor_channel_room_mappings', [
            'id' => (int) $mapping->id,
            'mapping_status' => 'inactive',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function vendorSession(User $vendor): array
    {
        return [
            'portal_vendor_authenticated' => true,
            'portal_vendor_user' => $vendor->name,
            'portal_vendor_user_id' => $vendor->id,
            'portal_vendor_role' => 'VENDOR',
        ];
    }
}

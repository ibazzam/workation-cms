<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChannelWebhookLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_created_modified_cancelled_flow_is_processed(): void
    {
        $env = $this->createChannelEnvironment('booking', 'agoda', 'BK-ROOM-A', 'AG-ROOM-A', 'booking_secret_001');

        $createdPayload = [
            'event_type' => 'reservation.created',
            'event_id' => 'bk_evt_created_001',
            'property_id' => $env['property_id'],
            'physical_rooms' => 5,
            'reservation' => [
                'id' => 'BK-1001',
                'room_type_id' => 'BK-ROOM-A',
                'number_of_rooms' => 1,
                'checkin_date' => '2026-06-10',
                'checkout_date' => '2026-06-12',
                'guest' => [
                    'name' => 'Booking Guest',
                    'email' => 'booking.guest@example.com',
                ],
                'guest_count' => 2,
                'price_total' => 3500,
                'currency' => 'USD',
                'payment_status' => 'paid',
                'updated_at' => '2026-05-16T09:00:00Z',
            ],
        ];

        $createdResponse = $this->postSignedChannelWebhook('booking', $env['source_account_id'], $createdPayload, $env['webhook_secret']);
        $this->assertSame(200, $createdResponse->status(), $createdResponse->getContent());
        $createdResponse->assertJson(['ok' => true, 'status' => 'processed']);

        $reservationId = (int) DB::table('vendor_channel_reservation_links')
            ->where('vendor_channel_account_id', $env['source_account_id'])
            ->where('external_booking_id', 'BK-1001')
            ->value('vendor_reservation_id');

        $this->assertGreaterThan(0, $reservationId);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'vendor_user_id' => $env['vendor_user_id'],
            'vendor_property_id' => $env['property_id'],
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('vendor_room_inventory_daily', [
            'vendor_user_id' => $env['vendor_user_id'],
            'vendor_property_id' => $env['property_id'],
            'room_key' => 'BK-ROOM-A',
            'inventory_date' => '2026-06-10',
            'sold_rooms' => 1,
        ]);

        $this->assertDatabaseHas('vendor_channel_events', [
            'vendor_channel_account_id' => $env['target_account_id'],
            'direction' => 'outbound',
            'event_type' => 'inventory.sync',
            'status' => 'queued',
        ]);

        $modifiedPayload = [
            'event_type' => 'reservation.modified',
            'event_id' => 'bk_evt_modified_001',
            'property_id' => $env['property_id'],
            'physical_rooms' => 5,
            'reservation' => [
                'id' => 'BK-1001',
                'room_type_id' => 'BK-ROOM-A',
                'number_of_rooms' => 2,
                'checkin_date' => '2026-06-11',
                'checkout_date' => '2026-06-13',
                'guest' => [
                    'name' => 'Booking Guest',
                    'email' => 'booking.guest@example.com',
                ],
                'guest_count' => 2,
                'price_total' => 4200,
                'currency' => 'USD',
                'payment_status' => 'paid',
                'updated_at' => '2026-05-16T10:00:00Z',
            ],
        ];

        $this->postSignedChannelWebhook('booking', $env['source_account_id'], $modifiedPayload, $env['webhook_secret'])
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'status' => 'processed']);

        $this->assertDatabaseHas('vendor_room_inventory_daily', [
            'vendor_user_id' => $env['vendor_user_id'],
            'vendor_property_id' => $env['property_id'],
            'room_key' => 'BK-ROOM-A',
            'inventory_date' => '2026-06-10',
            'sold_rooms' => 0,
        ]);
        $this->assertDatabaseHas('vendor_room_inventory_daily', [
            'vendor_user_id' => $env['vendor_user_id'],
            'vendor_property_id' => $env['property_id'],
            'room_key' => 'BK-ROOM-A',
            'inventory_date' => '2026-06-11',
            'sold_rooms' => 2,
        ]);
        $this->assertDatabaseHas('vendor_room_inventory_daily', [
            'vendor_user_id' => $env['vendor_user_id'],
            'vendor_property_id' => $env['property_id'],
            'room_key' => 'BK-ROOM-A',
            'inventory_date' => '2026-06-12',
            'sold_rooms' => 2,
        ]);

        $cancelledPayload = [
            'event_type' => 'reservation.cancelled',
            'event_id' => 'bk_evt_cancelled_001',
            'property_id' => $env['property_id'],
            'reservation' => [
                'id' => 'BK-1001',
                'room_type_id' => 'BK-ROOM-A',
                'number_of_rooms' => 2,
                'checkin_date' => '2026-06-11',
                'checkout_date' => '2026-06-13',
                'guest' => [
                    'name' => 'Booking Guest',
                    'email' => 'booking.guest@example.com',
                ],
                'guest_count' => 2,
                'price_total' => 4200,
                'currency' => 'USD',
                'payment_status' => 'unpaid',
                'updated_at' => '2026-05-16T11:00:00Z',
            ],
        ];

        $this->postSignedChannelWebhook('booking', $env['source_account_id'], $cancelledPayload, $env['webhook_secret'])
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'status' => 'processed']);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
        ]);

        $this->assertDatabaseHas('vendor_room_inventory_daily', [
            'vendor_user_id' => $env['vendor_user_id'],
            'vendor_property_id' => $env['property_id'],
            'room_key' => 'BK-ROOM-A',
            'inventory_date' => '2026-06-11',
            'sold_rooms' => 0,
        ]);
        $this->assertDatabaseHas('vendor_room_inventory_daily', [
            'vendor_user_id' => $env['vendor_user_id'],
            'vendor_property_id' => $env['property_id'],
            'room_key' => 'BK-ROOM-A',
            'inventory_date' => '2026-06-12',
            'sold_rooms' => 0,
        ]);
    }

    public function test_agoda_created_modified_cancelled_flow_is_processed(): void
    {
        $env = $this->createChannelEnvironment('agoda', 'booking', 'AG-ROOM-B', 'BK-ROOM-B', 'agoda_secret_001');

        $createdPayload = [
            'event_type' => 'reservation.created',
            'event_id' => 'ag_evt_created_001',
            'booking' => [
                'booking_id' => 'AG-9001',
                'room_id' => 'AG-ROOM-B',
                'rooms' => 1,
                'check_in' => '2026-07-01',
                'check_out' => '2026-07-03',
                'guest' => [
                    'full_name' => 'Agoda Guest',
                    'email' => 'agoda.guest@example.com',
                ],
                'occupancy' => 2,
                'amount_total' => 2200,
                'currency' => 'USD',
                'payment_status' => 'paid',
                'property_id' => $env['property_id'],
                'revision' => 'rev-1',
            ],
            'physical_rooms' => 4,
        ];

        $createdResponse = $this->postSignedChannelWebhook('agoda', $env['source_account_id'], $createdPayload, $env['webhook_secret']);
        $this->assertSame(200, $createdResponse->status(), $createdResponse->getContent());
        $createdResponse->assertJson(['ok' => true, 'status' => 'processed']);

        $reservationId = (int) DB::table('vendor_channel_reservation_links')
            ->where('vendor_channel_account_id', $env['source_account_id'])
            ->where('external_booking_id', 'AG-9001')
            ->value('vendor_reservation_id');

        $this->assertGreaterThan(0, $reservationId);

        $modifiedPayload = [
            'event_type' => 'reservation.modified',
            'event_id' => 'ag_evt_modified_001',
            'booking' => [
                'booking_id' => 'AG-9001',
                'room_id' => 'AG-ROOM-B',
                'rooms' => 2,
                'check_in' => '2026-07-02',
                'check_out' => '2026-07-04',
                'guest' => [
                    'full_name' => 'Agoda Guest',
                    'email' => 'agoda.guest@example.com',
                ],
                'occupancy' => 2,
                'amount_total' => 2600,
                'currency' => 'USD',
                'payment_status' => 'paid',
                'property_id' => $env['property_id'],
                'revision' => 'rev-2',
            ],
            'physical_rooms' => 4,
        ];

        $this->postSignedChannelWebhook('agoda', $env['source_account_id'], $modifiedPayload, $env['webhook_secret'])
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'status' => 'processed']);

        $cancelledPayload = [
            'event_type' => 'reservation.cancelled',
            'event_id' => 'ag_evt_cancelled_001',
            'booking' => [
                'booking_id' => 'AG-9001',
                'room_id' => 'AG-ROOM-B',
                'rooms' => 2,
                'check_in' => '2026-07-02',
                'check_out' => '2026-07-04',
                'guest' => [
                    'full_name' => 'Agoda Guest',
                    'email' => 'agoda.guest@example.com',
                ],
                'occupancy' => 2,
                'amount_total' => 2600,
                'currency' => 'USD',
                'payment_status' => 'unpaid',
                'property_id' => $env['property_id'],
                'revision' => 'rev-3',
            ],
        ];

        $this->postSignedChannelWebhook('agoda', $env['source_account_id'], $cancelledPayload, $env['webhook_secret'])
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'status' => 'processed']);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
        ]);

        $this->assertDatabaseHas('vendor_channel_events', [
            'vendor_channel_account_id' => $env['target_account_id'],
            'direction' => 'outbound',
            'event_type' => 'inventory.sync',
            'status' => 'queued',
        ]);
    }

    /**
     * @return array{vendor_user_id:int,property_id:int,source_account_id:int,target_account_id:int,webhook_secret:string}
     */
    private function createChannelEnvironment(string $sourceChannel, string $targetChannel, string $sourceRoomId, string $targetRoomId, string $webhookSecret): array
    {
        $vendor = User::factory()->create();

        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'name' => 'Channel Test Listing',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_accommodation_listings')
            ->where('id', $propertyId)
            ->update(['vendor_property_id' => $propertyId]);

        $sourceAccountId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'channel_code' => $sourceChannel,
            'account_reference' => strtoupper($sourceChannel) . ' Source',
            'status' => 'connected',
            'connection_meta' => json_encode([
                'webhook_secret' => $webhookSecret,
                'inventory_sync_url' => 'https://example.invalid/' . $sourceChannel . '/inventory/sync',
            ], JSON_UNESCAPED_SLASHES),
            'connected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $targetAccountId = (int) DB::table('vendor_channel_accounts')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'channel_code' => $targetChannel,
            'account_reference' => strtoupper($targetChannel) . ' Target',
            'status' => 'connected',
            'connection_meta' => json_encode([
                'webhook_secret' => 'target_' . $webhookSecret,
                'inventory_sync_url' => 'https://example.invalid/' . $targetChannel . '/inventory/sync',
            ], JSON_UNESCAPED_SLASHES),
            'connected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $internalRoomCategoryId = 9001;

        if (Schema::hasTable('vendor_property_room_categories')) {
            DB::table('vendor_property_room_categories')->insert([
                'id' => $internalRoomCategoryId,
                'vendor_user_id' => $vendor->id,
                'vendor_property_id' => $propertyId,
                'name' => 'OTA Room Category',
                'quantity' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('vendor_channel_room_mappings')->insert([
            'vendor_channel_account_id' => $sourceAccountId,
            'external_room_id' => $sourceRoomId,
            'external_room_name' => 'Source Room',
            'internal_room_category_id' => $internalRoomCategoryId,
            'mapping_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_channel_room_mappings')->insert([
            'vendor_channel_account_id' => $targetAccountId,
            'external_room_id' => $targetRoomId,
            'external_room_name' => 'Target Room',
            'internal_room_category_id' => $internalRoomCategoryId,
            'mapping_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'vendor_user_id' => (int) $vendor->id,
            'property_id' => $propertyId,
            'source_account_id' => $sourceAccountId,
            'target_account_id' => $targetAccountId,
            'webhook_secret' => $webhookSecret,
        ];
    }

    private function postSignedChannelWebhook(string $channel, int $accountId, array $payload, string $secret)
    {
        $rawPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . (string) $rawPayload, $secret);

        return $this->call(
            'POST',
            '/channel/webhooks/' . $channel . '/' . $accountId,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Channel-Signature' => 't=' . $timestamp . ',v1=' . $signature,
                'HTTP_X-Channel-Timestamp' => (string) $timestamp,
            ],
            (string) $rawPayload
        );
    }
}

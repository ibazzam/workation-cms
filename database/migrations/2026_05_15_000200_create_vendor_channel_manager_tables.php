<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_channel_accounts')) {
            Schema::create('vendor_channel_accounts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_property_id')->nullable()->constrained('vendor_properties')->nullOnDelete();
                $table->string('channel_code', 40);
                $table->string('account_reference', 160)->nullable();
                $table->string('status', 24)->default('disconnected');
                $table->text('access_token_encrypted')->nullable();
                $table->text('refresh_token_encrypted')->nullable();
                $table->timestamp('token_expires_at')->nullable();
                $table->json('connection_meta')->nullable();
                $table->timestamp('connected_at')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();

                $table->index(['vendor_user_id', 'status']);
                $table->index(['vendor_property_id', 'status']);
                $table->unique(['vendor_user_id', 'vendor_property_id', 'channel_code'], 'vendor_channel_accounts_vendor_property_channel_unique');
            });
        }

        if (!Schema::hasTable('vendor_channel_room_mappings')) {
            Schema::create('vendor_channel_room_mappings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_channel_account_id')->constrained('vendor_channel_accounts')->cascadeOnDelete();
                $table->string('external_room_id', 160);
                $table->string('external_room_name', 190)->nullable();
                $table->string('external_rate_plan_id', 160)->nullable();
                $table->string('external_rate_plan_name', 190)->nullable();
                $table->unsignedBigInteger('internal_room_category_id')->nullable();
                $table->unsignedBigInteger('internal_accommodation_room_id')->nullable();
                $table->string('mapping_status', 24)->default('active');
                $table->json('mapping_meta')->nullable();
                $table->timestamps();

                $table->index(['vendor_channel_account_id', 'mapping_status'], 'vendor_channel_room_mappings_account_status_idx');
                $table->index(['internal_room_category_id'], 'vendor_channel_room_mappings_room_category_idx');
                $table->index(['internal_accommodation_room_id'], 'vendor_channel_room_mappings_accommodation_room_idx');
                $table->unique(['vendor_channel_account_id', 'external_room_id'], 'vendor_channel_room_mappings_account_external_room_unique');
            });
        }

        if (!Schema::hasTable('vendor_room_inventory_daily')) {
            Schema::create('vendor_room_inventory_daily', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_property_id')->nullable()->constrained('vendor_properties')->nullOnDelete();
                $table->string('room_key', 120);
                $table->unsignedBigInteger('internal_room_category_id')->nullable();
                $table->unsignedBigInteger('internal_accommodation_room_id')->nullable();
                $table->date('inventory_date');
                $table->unsignedInteger('physical_rooms')->default(0);
                $table->unsignedInteger('sold_rooms')->default(0);
                $table->unsignedInteger('hold_rooms')->default(0);
                $table->unsignedInteger('safety_buffer')->default(0);
                $table->boolean('closed_out')->default(false);
                $table->unsignedBigInteger('version')->default(0);
                $table->string('last_source', 40)->default('manual');
                $table->timestamps();

                $table->index(['vendor_user_id', 'inventory_date'], 'vendor_room_inventory_daily_vendor_date_idx');
                $table->index(['vendor_property_id', 'inventory_date'], 'vendor_room_inventory_daily_property_date_idx');
                $table->index(['room_key', 'inventory_date'], 'vendor_room_inventory_daily_roomkey_date_idx');
                $table->unique(['vendor_user_id', 'vendor_property_id', 'room_key', 'inventory_date'], 'vendor_room_inventory_daily_unique');
            });
        }

        if (!Schema::hasTable('vendor_channel_events')) {
            Schema::create('vendor_channel_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_channel_account_id')->nullable()->constrained('vendor_channel_accounts')->nullOnDelete();
                $table->string('direction', 12); // inbound | outbound
                $table->string('event_type', 64);
                $table->string('external_event_id', 190)->nullable();
                $table->string('idempotency_key', 190)->nullable();
                $table->string('status', 24)->default('received');
                $table->unsignedInteger('retry_count')->default(0);
                $table->string('http_method', 10)->nullable();
                $table->string('request_path', 255)->nullable();
                $table->string('signature_hash', 190)->nullable();
                $table->json('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['vendor_channel_account_id', 'status'], 'vendor_channel_events_account_status_idx');
                $table->index(['direction', 'event_type'], 'vendor_channel_events_direction_type_idx');
                $table->unique(['idempotency_key'], 'vendor_channel_events_idempotency_key_unique');
            });
        }

        if (!Schema::hasTable('vendor_channel_reservation_links')) {
            Schema::create('vendor_channel_reservation_links', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_reservation_id')->constrained('vendor_reservations')->cascadeOnDelete();
                $table->foreignId('vendor_channel_account_id')->constrained('vendor_channel_accounts')->cascadeOnDelete();
                $table->string('external_booking_id', 190);
                $table->string('external_booking_version', 80)->nullable();
                $table->string('external_status', 40)->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->index(['vendor_channel_account_id', 'external_status'], 'vendor_channel_reservation_links_account_status_idx');
                $table->unique(['vendor_channel_account_id', 'external_booking_id'], 'vendor_channel_reservation_links_account_booking_unique');
                $table->unique(['vendor_reservation_id'], 'vendor_channel_reservation_links_reservation_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_channel_reservation_links');
        Schema::dropIfExists('vendor_channel_events');
        Schema::dropIfExists('vendor_room_inventory_daily');
        Schema::dropIfExists('vendor_channel_room_mappings');
        Schema::dropIfExists('vendor_channel_accounts');
    }
};

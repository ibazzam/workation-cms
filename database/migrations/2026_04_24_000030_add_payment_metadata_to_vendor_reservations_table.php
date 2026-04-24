<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_reservations', 'customer_segment')) {
                $table->string('customer_segment', 30)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_currency')) {
                $table->string('payment_currency', 8)->nullable()->after('customer_segment');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_gateway')) {
                $table->string('payment_gateway', 40)->nullable()->after('payment_currency');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_intent_id')) {
                $table->string('payment_intent_id', 120)->nullable()->after('payment_gateway');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_reference')) {
                $table->string('payment_reference', 120)->nullable()->after('payment_intent_id');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_amount')) {
                $table->decimal('payment_amount', 12, 2)->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_error')) {
                $table->text('payment_error')->nullable()->after('payment_amount');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_payload_json')) {
                $table->longText('payment_payload_json')->nullable()->after('payment_error');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_verified_at')) {
                $table->timestamp('payment_verified_at')->nullable()->after('payment_payload_json');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_webhook_event_id')) {
                $table->string('payment_webhook_event_id', 120)->nullable()->after('payment_verified_at');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payment_webhook_received_at')) {
                $table->timestamp('payment_webhook_received_at')->nullable()->after('payment_webhook_event_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            foreach ([
                'customer_segment',
                'payment_currency',
                'payment_gateway',
                'payment_intent_id',
                'payment_reference',
                'payment_amount',
                'payment_error',
                'payment_payload_json',
                'payment_verified_at',
                'payment_webhook_event_id',
                'payment_webhook_received_at',
            ] as $column) {
                if (Schema::hasColumn('vendor_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
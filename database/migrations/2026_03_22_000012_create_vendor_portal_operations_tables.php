<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_properties')) {
            Schema::create('vendor_properties', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 160);
                $table->string('property_type', 32)->default('property');
                $table->string('location', 190)->nullable();
                $table->text('description')->nullable();
                $table->string('status', 24)->default('active');
                $table->decimal('base_price', 12, 2)->default(0);
                $table->string('currency', 8)->default('MVR');
                $table->unsignedInteger('max_guests')->default(1);
                $table->timestamps();

                $table->index(['vendor_user_id', 'property_type']);
                $table->index(['vendor_user_id', 'status']);
            });
        }

        if (!Schema::hasTable('vendor_services')) {
            Schema::create('vendor_services', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_property_id')->nullable()->constrained('vendor_properties')->nullOnDelete();
                $table->string('name', 160);
                $table->string('category', 120);
                $table->text('description')->nullable();
                $table->unsignedInteger('duration_minutes')->default(0);
                $table->decimal('price', 12, 2)->default(0);
                $table->string('currency', 8)->default('MVR');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['vendor_user_id', 'is_active']);
                $table->index(['vendor_property_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('vendor_availability_slots')) {
            Schema::create('vendor_availability_slots', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_property_id')->nullable()->constrained('vendor_properties')->nullOnDelete();
                $table->date('slot_date');
                $table->unsignedInteger('inventory')->default(0);
                $table->unsignedInteger('reserved_count')->default(0);
                $table->boolean('is_closed')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['vendor_user_id', 'vendor_property_id', 'slot_date'], 'vendor_availability_unique_slot');
                $table->index(['vendor_user_id', 'slot_date']);
            });
        }

        if (!Schema::hasTable('vendor_reservations')) {
            Schema::create('vendor_reservations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_property_id')->nullable()->constrained('vendor_properties')->nullOnDelete();
                $table->foreignId('vendor_service_id')->nullable()->constrained('vendor_services')->nullOnDelete();
                $table->string('customer_name', 160);
                $table->string('customer_email', 190);
                $table->timestamp('start_at');
                $table->timestamp('end_at');
                $table->unsignedInteger('guests')->default(1);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->string('currency', 8)->default('MVR');
                $table->string('status', 24)->default('pending');
                $table->string('payment_status', 24)->default('unpaid');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['vendor_user_id', 'status']);
                $table->index(['vendor_user_id', 'payment_status']);
                $table->index(['vendor_user_id', 'start_at']);
            });
        }

        if (!Schema::hasTable('vendor_pricing_rules')) {
            Schema::create('vendor_pricing_rules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_property_id')->nullable()->constrained('vendor_properties')->nullOnDelete();
                $table->foreignId('vendor_service_id')->nullable()->constrained('vendor_services')->nullOnDelete();
                $table->string('name', 160);
                $table->string('rule_type', 40);
                $table->decimal('value', 12, 2)->default(0);
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['vendor_user_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('vendor_billing_details')) {
            Schema::create('vendor_billing_details', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('business_name', 190);
                $table->string('tax_id', 120)->nullable();
                $table->string('billing_email', 190);
                $table->string('payout_method', 40)->default('bank_transfer');
                $table->string('payout_reference', 190)->nullable();
                $table->string('bank_name', 190)->nullable();
                $table->string('bank_account_last4', 8)->nullable();
                $table->text('billing_address')->nullable();
                $table->string('currency', 8)->default('MVR');
                $table->string('invoice_prefix', 30)->default('INV');
                $table->timestamps();

                $table->unique('vendor_user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_billing_details');
        Schema::dropIfExists('vendor_pricing_rules');
        Schema::dropIfExists('vendor_reservations');
        Schema::dropIfExists('vendor_availability_slots');
        Schema::dropIfExists('vendor_services');
        Schema::dropIfExists('vendor_properties');
    }
};

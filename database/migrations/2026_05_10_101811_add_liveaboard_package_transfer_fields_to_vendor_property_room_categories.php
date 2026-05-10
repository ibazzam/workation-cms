<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_property_room_categories', 'package_transfer_included')) {
                $table->boolean('package_transfer_included')->default(true)->after('extra_bed_policy');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'package_embark_point')) {
                $table->string('package_embark_point', 120)->nullable()->after('package_transfer_included');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'package_disembark_point')) {
                $table->string('package_disembark_point', 120)->nullable()->after('package_embark_point');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'package_mid_trip_join_allowed')) {
                $table->boolean('package_mid_trip_join_allowed')->default(false)->after('package_disembark_point');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'package_mid_trip_join_transfer_fee')) {
                $table->decimal('package_mid_trip_join_transfer_fee', 12, 2)->nullable()->after('package_mid_trip_join_allowed');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'package_transfer_notes')) {
                $table->text('package_transfer_notes')->nullable()->after('package_mid_trip_join_transfer_fee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', function (Blueprint $table): void {
            $columns = [
                'package_transfer_notes',
                'package_mid_trip_join_transfer_fee',
                'package_mid_trip_join_allowed',
                'package_disembark_point',
                'package_embark_point',
                'package_transfer_included',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vendor_property_room_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

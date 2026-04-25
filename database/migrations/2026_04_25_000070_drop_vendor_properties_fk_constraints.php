<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop all FK constraints that reference vendor_properties, and make
 * vendor_property_id nullable in every dedicated category listing table.
 *
 * This is the prerequisite step before vendor_properties can be dropped:
 * dependent tables are decoupled from the parent, and category tables can
 * accept new rows whose vendor_property_id is temporarily NULL (set to the
 * row's own auto-increment id immediately after insert).
 */
return new class extends Migration
{
    /** Tables whose vendor_property_id FK references vendor_properties. */
    private array $nullableVendorPropertyFkTables = [
        'vendor_services',
        'vendor_availability_slots',
        'vendor_reservations',
        'vendor_pricing_rules',
        'vendor_property_room_categories',
    ];

    /** Tables that used property_id with cascadeOnDelete referencing vendor_properties. */
    private array $propertyIdFkTables = [
        'accommodation_rooms',
        'room_amenities',
        'accommodation_packages',
    ];

    /** All 10 dedicated category listing tables. */
    private array $categoryListingTables = [
        'vendor_accommodation_listings',
        'vendor_conference_room_listings',
        'vendor_marine_transport_listings',
        'vendor_land_transport_listings',
        'vendor_excursion_listings',
        'vendor_remote_workspace_listings',
        'vendor_resort_day_visit_listings',
        'vendor_restaurant_listings',
        'vendor_vehicle_rental_listings',
        'vendor_water_sports_listings',
    ];

    public function up(): void
    {
        // 1. Drop vendor_property_id FK constraints on operational tables.
        foreach ($this->nullableVendorPropertyFkTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, static function (Blueprint $bp): void {
                $bp->dropForeign(['vendor_property_id']);
            });
        }

        // 2. Drop property_id FK constraints on accommodation sub-tables.
        foreach ($this->propertyIdFkTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, static function (Blueprint $bp): void {
                $bp->dropForeign(['property_id']);
            });
        }

        // 3. Make vendor_property_id nullable in category listing tables so that
        //    new listings can be inserted without a vendor_properties row — the
        //    application sets vendor_property_id = id (self-reference) right after.
        foreach ($this->categoryListingTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (!Schema::hasColumn($table, 'vendor_property_id')) {
                continue;
            }
            Schema::table($table, static function (Blueprint $bp): void {
                $bp->unsignedBigInteger('vendor_property_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Restore vendor_property_id as NOT NULL in category listing tables.
        // (Re-adding FK constraints is omitted intentionally — the original
        //  constraints had nullOnDelete / cascadeOnDelete which would interfere
        //  with any records whose vendor_property_id no longer matches
        //  vendor_properties.id after a partial rollback.)
        foreach ($this->categoryListingTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (!Schema::hasColumn($table, 'vendor_property_id')) {
                continue;
            }
            Schema::table($table, static function (Blueprint $bp): void {
                $bp->unsignedBigInteger('vendor_property_id')->nullable(false)->change();
            });
        }
    }
};

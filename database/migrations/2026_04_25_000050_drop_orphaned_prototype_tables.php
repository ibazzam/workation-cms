<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop tables that were created as part of an early April 2026 model-based prototype
 * for conference rooms and accommodation room/amenity lookup.
 *
 * These tables are NEVER queried by any route or view — they have been superseded by:
 *   - vendor_conference_room_listings (new per-category table, from migration _000040_)
 *   - vendor_property_room_categories  (the production room/amenity storage using comma-
 *     separated text fields, NOT a separate lookup table)
 *
 * Tables dropped:
 *   conference_room_transfer_options   – child of old ConferenceRoom model
 *   package_facility                   – pivot: conference_room_packages <-> facilities
 *   conference_room_facilities         – child of old ConferenceRoom model
 *   conference_room_packages           – child of old ConferenceRoom model
 *   conference_rooms                   – old model-based prototype root table
 *   room_amenity                       – pivot: accommodation_rooms <-> room_amenities
 *   package_amenity                    – pivot: accommodation_packages <-> room_amenities
 *   room_amenities                     – lookup table, never queried in routes
 *
 * Tables NOT dropped (still used):
 *   accommodation_rooms                – web.php queries for price aggregation fallback
 *   accommodation_packages             – web.php queries for price aggregation fallback
 *   atolls / islands                   – AtollIslandApiController, SyncMaldivesGeography,
 *                                        portal_helpers capital-flag lookup
 */
return new class extends Migration
{
    /** Drop order respects foreign key constraints (children first). */
    private array $dropOrder = [
        'conference_room_transfer_options',
        'package_facility',
        'conference_room_facilities',
        'conference_room_packages',
        'conference_rooms',
        'room_amenity',
        'package_amenity',
        'room_amenities',
    ];

    public function up(): void
    {
        foreach ($this->dropOrder as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Intentionally left empty.
        // These tables were orphaned prototypes. Rolling back does not restore them;
        // re-run the original April 2026 prototype migrations if the data is needed.
    }
};

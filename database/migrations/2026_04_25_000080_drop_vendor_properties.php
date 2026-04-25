<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the vendor_properties table.
 *
 * Prerequisites (must have run first):
 *   - 2026_04_25_000040: dedicated category listing tables created + data copied
 *   - 2026_04_25_000070: all FK constraints referencing vendor_properties dropped
 *
 * All reads now go through VendorPropertyCompatibilityReader which queries
 * the dedicated category tables. All writes in vendor-operations.php now
 * target only the dedicated category tables (vendor_property_id is set to the
 * category table's own auto-increment id as a self-reference).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_properties')) {
            return;
        }

        Schema::drop('vendor_properties');
    }

    public function down(): void
    {
        // vendor_properties is intentionally not re-created on rollback.
        // Restoring it would require re-migrating all data back from the
        // dedicated category tables and reinstating FK constraints, which
        // is handled by rolling back migrations 000040 and 000070 instead.
    }
};

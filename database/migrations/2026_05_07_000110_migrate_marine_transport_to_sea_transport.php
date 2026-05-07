<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copy rows from vendor_marine_transport_listings into vendor_sea_transport_listings.
        // The sea_transport table already exists (created 2026_05_06_000105). We only copy
        // rows that do not already exist in sea_transport for the same vendor_property_id.
        if (Schema::hasTable('vendor_marine_transport_listings') && Schema::hasTable('vendor_sea_transport_listings')) {
            $marineCols = Schema::getColumnListing('vendor_marine_transport_listings');
            $seaCols    = Schema::getColumnListing('vendor_sea_transport_listings');
            $shared     = array_values(array_intersect($marineCols, $seaCols));

            // Exclude the auto-increment primary key – the destination table will assign its own.
            $shared = array_values(array_filter($shared, static fn (string $c) => $c !== 'id'));

            if (!empty($shared)) {
                $existing = DB::table('vendor_sea_transport_listings')
                    ->pluck('vendor_property_id')
                    ->map(static fn ($v) => (int) $v)
                    ->all();

                $rows = DB::table('vendor_marine_transport_listings')
                    ->select($shared)
                    ->get();

                foreach ($rows as $row) {
                    $propertyId = (int) ($row->vendor_property_id ?? 0);
                    if (in_array($propertyId, $existing, true)) {
                        // Already migrated – skip to avoid duplicates.
                        continue;
                    }

                    $insert = [];
                    foreach ($shared as $col) {
                        $insert[$col] = $row->{$col} ?? null;
                    }

                    // Force category key to match the destination table.
                    if (array_key_exists('listing_category', $insert)) {
                        $insert['listing_category'] = 'sea_transport';
                    }

                    DB::table('vendor_sea_transport_listings')->insert($insert);
                }
            }

            Schema::dropIfExists('vendor_marine_transport_listings');
        }
    }

    public function down(): void
    {
        // Restore the marine transport table (empty – data was merged upward).
        if (!Schema::hasTable('vendor_marine_transport_listings')) {
            Schema::create('vendor_marine_transport_listings', function ($table) {
                $table->id();
                $table->unsignedBigInteger('vendor_property_id')->index();
                $table->unsignedBigInteger('vendor_user_id')->index();
                $table->string('name', 200)->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->string('listing_moderation_status', 32)->default('pending')->index();
                $table->string('listing_category', 64)->default('marine_transport')->index();
                $table->string('listing_admin_notes', 500)->nullable();
                $table->timestamp('listing_submitted_for_review_at')->nullable();
                $table->timestamp('listing_approved_at')->nullable();
                $table->unsignedBigInteger('listing_approved_by_user_id')->nullable();
                $table->string('location', 300)->nullable();
                $table->string('atoll', 120)->nullable()->index();
                $table->string('island', 120)->nullable()->index();
                $table->string('city', 120)->nullable()->index();
                $table->string('location_country', 80)->nullable()->default('Maldives');
                $table->text('description')->nullable();
                $table->decimal('base_price', 12, 2)->nullable()->default(0);
                $table->string('currency', 10)->nullable()->default('MVR');
                $table->integer('max_guests')->nullable()->default(0);
                $table->json('listing_details')->nullable();
                $table->timestamps();
            });
        }
    }
};

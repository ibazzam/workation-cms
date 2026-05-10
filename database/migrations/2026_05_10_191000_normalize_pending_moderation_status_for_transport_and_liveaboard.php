<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $targets = [
            'vendor_liveaboard_listings',
            'vendor_sea_transport_listings',
        ];

        foreach ($targets as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'listing_moderation_status')) {
                continue;
            }

            $hasSubmittedAt = Schema::hasColumn($table, 'listing_submitted_for_review_at');

            $draftQuery = DB::table($table)
                ->whereRaw('LOWER(TRIM(listing_moderation_status)) = ?', ['pending']);

            if ($hasSubmittedAt) {
                $draftQuery->whereNull('listing_submitted_for_review_at');
            }

            $draftQuery->update([
                'listing_moderation_status' => 'draft',
                'updated_at' => now(),
            ]);

            if ($hasSubmittedAt) {
                DB::table($table)
                    ->whereRaw('LOWER(TRIM(listing_moderation_status)) = ?', ['pending'])
                    ->whereNotNull('listing_submitted_for_review_at')
                    ->update([
                        'listing_moderation_status' => 'pending_review',
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // One-way normalization.
    }
};

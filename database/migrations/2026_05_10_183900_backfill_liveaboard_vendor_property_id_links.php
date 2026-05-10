<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_liveaboard_listings') || !Schema::hasColumn('vendor_liveaboard_listings', 'vendor_property_id')) {
            return;
        }

        if (!Schema::hasTable('vendor_properties')) {
            return;
        }

        DB::table('vendor_liveaboard_listings')
            ->select(['id', 'vendor_user_id', 'name'])
            ->where(function ($query): void {
                $query->whereNull('vendor_property_id')
                    ->orWhere('vendor_property_id', 0);
            })
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $liveaboardId = (int) ($row->id ?? 0);
                    $vendorUserId = (int) ($row->vendor_user_id ?? 0);
                    if ($liveaboardId <= 0 || $vendorUserId <= 0) {
                        continue;
                    }

                    $resolvedVendorPropertyId = null;

                    $exactIdMatch = DB::table('vendor_properties')
                        ->where('id', $liveaboardId)
                        ->where('vendor_user_id', $vendorUserId)
                        ->whereIn('listing_category', ['liveaboard', 'live_aboard'])
                        ->value('id');
                    if (is_numeric($exactIdMatch)) {
                        $resolvedVendorPropertyId = (int) $exactIdMatch;
                    }

                    if ($resolvedVendorPropertyId === null) {
                        $name = strtolower(trim((string) ($row->name ?? '')));
                        if ($name !== '') {
                            $nameMatches = DB::table('vendor_properties')
                                ->where('vendor_user_id', $vendorUserId)
                                ->whereIn('listing_category', ['liveaboard', 'live_aboard'])
                                ->whereRaw('LOWER(TRIM(name)) = ?', [$name])
                                ->pluck('id');

                            if ($nameMatches->count() === 1) {
                                $resolvedVendorPropertyId = (int) $nameMatches->first();
                            }
                        }
                    }

                    if ($resolvedVendorPropertyId === null || $resolvedVendorPropertyId <= 0) {
                        continue;
                    }

                    DB::table('vendor_liveaboard_listings')
                        ->where('id', $liveaboardId)
                        ->where(function ($query): void {
                            $query->whereNull('vendor_property_id')
                                ->orWhere('vendor_property_id', 0);
                        })
                        ->update([
                            'vendor_property_id' => $resolvedVendorPropertyId,
                            'updated_at' => now(),
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // Data backfill only; no rollback.
    }
};

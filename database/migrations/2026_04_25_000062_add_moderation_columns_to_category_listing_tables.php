<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
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
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, static function (Blueprint $table) use ($tableName): void {
                if (!Schema::hasColumn($tableName, 'listing_moderation_status')) {
                    $table->string('listing_moderation_status', 32)->default('draft')->nullable()->after('status');
                }
                if (!Schema::hasColumn($tableName, 'listing_admin_notes')) {
                    $table->text('listing_admin_notes')->nullable()->after('listing_moderation_status');
                }
                if (!Schema::hasColumn($tableName, 'listing_submitted_for_review_at')) {
                    $table->timestamp('listing_submitted_for_review_at')->nullable()->after('listing_admin_notes');
                }
                if (!Schema::hasColumn($tableName, 'listing_approved_at')) {
                    $table->timestamp('listing_approved_at')->nullable()->after('listing_submitted_for_review_at');
                }
                if (!Schema::hasColumn($tableName, 'listing_approved_by_user_id')) {
                    $table->unsignedBigInteger('listing_approved_by_user_id')->nullable()->after('listing_approved_at');
                }
            });
        }

        // Backfill moderation status from vendor_properties where available.
        if (!Schema::hasTable('vendor_properties')
            || !Schema::hasColumn('vendor_properties', 'listing_moderation_status')
            || !Schema::hasColumn('vendor_properties', 'listing_category')) {
            return;
        }

        $categoryTableMap = [
            'accommodation' => 'vendor_accommodation_listings',
            'conference_room' => 'vendor_conference_room_listings',
            'marine_transport' => 'vendor_marine_transport_listings',
            'land_transport' => 'vendor_land_transport_listings',
            'excursion' => 'vendor_excursion_listings',
            'remote_workspace' => 'vendor_remote_workspace_listings',
            'resort_day_visit' => 'vendor_resort_day_visit_listings',
            'restaurant' => 'vendor_restaurant_listings',
            'vehicle_rental' => 'vendor_vehicle_rental_listings',
            'water_sports' => 'vendor_water_sports_listings',
        ];

        $columns = ['id', 'listing_category', 'listing_moderation_status'];
        if (Schema::hasColumn('vendor_properties', 'listing_admin_notes')) {
            $columns[] = 'listing_admin_notes';
        }
        if (Schema::hasColumn('vendor_properties', 'listing_submitted_for_review_at')) {
            $columns[] = 'listing_submitted_for_review_at';
        }
        if (Schema::hasColumn('vendor_properties', 'listing_approved_at')) {
            $columns[] = 'listing_approved_at';
        }
        if (Schema::hasColumn('vendor_properties', 'listing_approved_by_user_id')) {
            $columns[] = 'listing_approved_by_user_id';
        }

        $rows = DB::table('vendor_properties')
            ->whereNotNull('listing_moderation_status')
            ->get($columns);

        foreach ($rows as $row) {
            $category = strtolower(trim(str_replace('-', '_', (string) ($row->listing_category ?? ''))));
            $targetTable = $categoryTableMap[$category] ?? null;
            if ($targetTable === null || !Schema::hasTable($targetTable)) {
                continue;
            }
            if (!Schema::hasColumn($targetTable, 'listing_moderation_status')) {
                continue;
            }

            $updatePayload = [
                'listing_moderation_status' => (string) ($row->listing_moderation_status ?? 'draft'),
            ];
            if (Schema::hasColumn($targetTable, 'listing_admin_notes') && property_exists($row, 'listing_admin_notes')) {
                $updatePayload['listing_admin_notes'] = $row->listing_admin_notes;
            }
            if (Schema::hasColumn($targetTable, 'listing_submitted_for_review_at') && property_exists($row, 'listing_submitted_for_review_at')) {
                $updatePayload['listing_submitted_for_review_at'] = $row->listing_submitted_for_review_at;
            }
            if (Schema::hasColumn($targetTable, 'listing_approved_at') && property_exists($row, 'listing_approved_at')) {
                $updatePayload['listing_approved_at'] = $row->listing_approved_at;
            }
            if (Schema::hasColumn($targetTable, 'listing_approved_by_user_id') && property_exists($row, 'listing_approved_by_user_id')) {
                $updatePayload['listing_approved_by_user_id'] = $row->listing_approved_by_user_id;
            }

            DB::table($targetTable)
                ->where('vendor_property_id', (int) $row->id)
                ->update($updatePayload);
        }
    }

    public function down(): void
    {
        $columns = [
            'listing_moderation_status',
            'listing_admin_notes',
            'listing_submitted_for_review_at',
            'listing_approved_at',
            'listing_approved_by_user_id',
        ];

        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, static function (Blueprint $table) use ($tableName, $columns): void {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

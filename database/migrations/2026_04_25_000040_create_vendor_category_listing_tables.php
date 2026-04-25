<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables are intentionally split per category to keep category lifecycle
     * isolated and to avoid a single-table bottleneck/failure domain.
     */
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

    private array $categoryTableMap = [
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

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                continue;
            }

            Schema::create($tableName, static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('vendor_property_id')->unique();
                $table->unsignedBigInteger('vendor_user_id');
                $table->string('name', 160);
                $table->string('status', 32)->default('active');
                $table->string('location', 255)->nullable();
                $table->text('description')->nullable();
                $table->decimal('base_price', 12, 2)->default(0);
                $table->string('currency', 8)->default('MVR');
                $table->unsignedInteger('max_guests')->default(0);
                $table->json('details')->nullable();
                $table->timestamps();

                $table->index(['vendor_user_id', 'status']);
                $table->index(['vendor_user_id', 'updated_at']);
            });
        }

        if (!Schema::hasTable('vendor_properties')) {
            return;
        }

        $rows = DB::table('vendor_properties')->get([
            'id',
            'vendor_user_id',
            'name',
            'status',
            'location',
            'description',
            'base_price',
            'currency',
            'max_guests',
            'listing_category',
            'listing_details',
            'created_at',
            'updated_at',
        ]);

        foreach ($rows as $row) {
            $category = strtolower(trim(str_replace('-', '_', (string) ($row->listing_category ?? ''))));
            $tableName = $this->categoryTableMap[$category] ?? null;
            if ($tableName === null || !Schema::hasTable($tableName)) {
                continue;
            }

            DB::table($tableName)->updateOrInsert(
                ['vendor_property_id' => (int) ($row->id ?? 0)],
                [
                    'vendor_user_id' => (int) ($row->vendor_user_id ?? 0),
                    'name' => (string) ($row->name ?? ''),
                    'status' => (string) ($row->status ?? 'active'),
                    'location' => (string) ($row->location ?? ''),
                    'description' => (string) ($row->description ?? ''),
                    'base_price' => (float) ($row->base_price ?? 0),
                    'currency' => (string) ($row->currency ?? 'MVR'),
                    'max_guests' => (int) ($row->max_guests ?? 0),
                    'details' => $row->listing_details,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]
            );
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::dropIfExists($tableName);
        }
    }
};

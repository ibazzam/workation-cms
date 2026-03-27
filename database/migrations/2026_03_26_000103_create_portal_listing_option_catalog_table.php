<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portal_listing_option_catalog')) {
            Schema::create('portal_listing_option_catalog', function (Blueprint $table): void {
                $table->id();
                $table->string('option_type', 64);
                $table->string('option_value', 120);
                $table->string('option_label', 190);
                $table->string('option_group', 80)->nullable();
                $table->unsignedInteger('sort_order')->default(100);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->unsignedBigInteger('updated_by_user_id')->nullable();
                $table->timestamps();

                $table->unique(['option_type', 'option_value'], 'portal_listing_option_catalog_unique');
                $table->index(['option_type', 'is_active', 'sort_order'], 'portal_listing_option_catalog_lookup');
            });
        }

        $seedRows = [
            ['option_type' => 'transport_mode', 'option_value' => 'speedboat', 'option_label' => 'Speedboat', 'option_group' => 'marine', 'sort_order' => 10],
            ['option_type' => 'transport_mode', 'option_value' => 'ferry', 'option_label' => 'Ferry', 'option_group' => 'marine', 'sort_order' => 20],
            ['option_type' => 'transport_mode', 'option_value' => 'boat', 'option_label' => 'Boat', 'option_group' => 'marine', 'sort_order' => 30],
            ['option_type' => 'transport_mode', 'option_value' => 'safari', 'option_label' => 'Safari', 'option_group' => 'marine', 'sort_order' => 40],
            ['option_type' => 'transport_mode', 'option_value' => 'dhoni', 'option_label' => 'Dhoni', 'option_group' => 'marine', 'sort_order' => 50],
            ['option_type' => 'transport_mode', 'option_value' => 'launch', 'option_label' => 'Launch', 'option_group' => 'marine', 'sort_order' => 60],
            ['option_type' => 'transport_mode', 'option_value' => 'catamaran', 'option_label' => 'Catamaran', 'option_group' => 'marine', 'sort_order' => 70],
            ['option_type' => 'transport_mode', 'option_value' => 'yacht', 'option_label' => 'Yacht', 'option_group' => 'marine', 'sort_order' => 80],
            ['option_type' => 'transport_mode', 'option_value' => 'other vessel', 'option_label' => 'Other Vessel', 'option_group' => 'marine', 'sort_order' => 90],
            ['option_type' => 'transport_mode', 'option_value' => 'van', 'option_label' => 'Van', 'option_group' => 'land', 'sort_order' => 110],
            ['option_type' => 'transport_mode', 'option_value' => 'car', 'option_label' => 'Car', 'option_group' => 'land', 'sort_order' => 120],
            ['option_type' => 'transport_mode', 'option_value' => 'pickup', 'option_label' => 'Pickup', 'option_group' => 'land', 'sort_order' => 130],
            ['option_type' => 'transport_mode', 'option_value' => 'bus', 'option_label' => 'Bus', 'option_group' => 'land', 'sort_order' => 140],
            ['option_type' => 'transport_mode', 'option_value' => 'suv', 'option_label' => 'SUV', 'option_group' => 'land', 'sort_order' => 150],
            ['option_type' => 'transport_mode', 'option_value' => 'other land vehicle', 'option_label' => 'Other Land Vehicle', 'option_group' => 'land', 'sort_order' => 160],
            ['option_type' => 'accommodation_facility', 'option_value' => 'wifi', 'option_label' => 'Wi-Fi', 'option_group' => 'core', 'sort_order' => 10],
            ['option_type' => 'accommodation_facility', 'option_value' => 'parking', 'option_label' => 'Parking', 'option_group' => 'core', 'sort_order' => 20],
            ['option_type' => 'accommodation_facility', 'option_value' => 'pool', 'option_label' => 'Pool', 'option_group' => 'core', 'sort_order' => 30],
            ['option_type' => 'accommodation_facility', 'option_value' => 'gym', 'option_label' => 'Gym', 'option_group' => 'core', 'sort_order' => 40],
            ['option_type' => 'accommodation_facility', 'option_value' => 'air_conditioning', 'option_label' => 'Air Conditioning', 'option_group' => 'core', 'sort_order' => 50],
            ['option_type' => 'accommodation_facility', 'option_value' => 'breakfast', 'option_label' => 'Breakfast', 'option_group' => 'food', 'sort_order' => 60],
            ['option_type' => 'accommodation_facility', 'option_value' => 'kitchen', 'option_label' => 'Kitchen', 'option_group' => 'food', 'sort_order' => 70],
            ['option_type' => 'accommodation_facility', 'option_value' => 'workspace_desk', 'option_label' => 'Workspace Desk', 'option_group' => 'workspace', 'sort_order' => 80],
            ['option_type' => 'room_amenity', 'option_value' => 'air_conditioning', 'option_label' => 'Air Conditioning', 'option_group' => 'room', 'sort_order' => 10],
            ['option_type' => 'room_amenity', 'option_value' => 'ensuite_bathroom', 'option_label' => 'Ensuite Bathroom', 'option_group' => 'room', 'sort_order' => 20],
            ['option_type' => 'room_amenity', 'option_value' => 'smart_tv', 'option_label' => 'Smart TV', 'option_group' => 'room', 'sort_order' => 30],
            ['option_type' => 'room_amenity', 'option_value' => 'mini_bar', 'option_label' => 'Mini Bar', 'option_group' => 'room', 'sort_order' => 40],
            ['option_type' => 'room_amenity', 'option_value' => 'balcony', 'option_label' => 'Balcony', 'option_group' => 'room', 'sort_order' => 50],
            ['option_type' => 'room_amenity', 'option_value' => 'sea_view', 'option_label' => 'Sea View', 'option_group' => 'view', 'sort_order' => 60],
            ['option_type' => 'room_amenity', 'option_value' => 'kettle', 'option_label' => 'Kettle', 'option_group' => 'room', 'sort_order' => 70],
            ['option_type' => 'room_amenity', 'option_value' => 'safe_box', 'option_label' => 'Safe Box', 'option_group' => 'room', 'sort_order' => 80],
            ['option_type' => 'room_amenity', 'option_value' => 'work_desk', 'option_label' => 'Work Desk', 'option_group' => 'workspace', 'sort_order' => 90],
            ['option_type' => 'room_amenity', 'option_value' => 'wifi', 'option_label' => 'Wi-Fi', 'option_group' => 'connectivity', 'sort_order' => 100],
        ];

        if (Schema::hasTable('portal_listing_option_catalog')) {
            foreach ($seedRows as $row) {
                DB::table('portal_listing_option_catalog')->updateOrInsert(
                    [
                        'option_type' => $row['option_type'],
                        'option_value' => $row['option_value'],
                    ],
                    [
                        'option_label' => $row['option_label'],
                        'option_group' => $row['option_group'],
                        'sort_order' => $row['sort_order'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('portal_listing_option_catalog')) {
            Schema::dropIfExists('portal_listing_option_catalog');
        }
    }
};

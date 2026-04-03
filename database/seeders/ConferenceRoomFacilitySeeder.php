<?php

namespace Database\Seeders;

use App\Models\ConferenceRoom;
use App\Models\ConferenceRoomFacility;
use App\Models\ConferenceRoomPackage;
use App\Models\ConferenceRoomTransferOption;
use Illuminate\Database\Seeder;

class ConferenceRoomFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $room = ConferenceRoom::first() ?? ConferenceRoom::create([
            'name' => 'Sample Conference Room',
            'description' => 'A modern conference room with full facilities',
            'location' => 'Male',
            'atoll' => 'Kaaf',
            'island' => 'Male',
            'capacity' => 50,
            'base_price' => 500,
            'currency' => 'MVR',
            'hourly_rate' => 100,
            'is_active' => true,
            'is_resort_venue' => true,
            'resort_name' => 'Tropical Paradise Resort',
            'airport_name' => 'Malé International Airport (MLE)',
        ]);

        $facilities = [
            // Equipment - Free
            ['name' => 'Projector', 'category' => 'equipment', 'is_free' => true, 'pricing_type' => 'flat', 'price' => 0],
            ['name' => 'Whiteboard', 'category' => 'equipment', 'is_free' => true, 'pricing_type' => 'flat', 'price' => 0],
            ['name' => 'Basic WiFi', 'category' => 'equipment', 'is_free' => true, 'pricing_type' => 'flat', 'price' => 0],
            
            // Equipment - Paid
            ['name' => 'Video Conferencing System', 'category' => 'equipment', 'is_free' => false, 'pricing_type' => 'hourly', 'price' => 50, 'description' => 'Charged per hour'],
            ['name' => 'Additional Chairs', 'category' => 'equipment', 'is_free' => false, 'pricing_type' => 'per_unit', 'price' => 25, 'description' => 'Additional chair (10 included)', 'quantity_available' => 30],
            ['name' => 'Tables', 'category' => 'equipment', 'is_free' => false, 'pricing_type' => 'per_unit', 'price' => 75, 'description' => 'Additional table', 'quantity_available' => 10],
            
            // Refreshments - Paid
            ['name' => 'Coffee & Tea Service', 'category' => 'refreshment', 'is_free' => false, 'pricing_type' => 'per_pax', 'price' => 50, 'description' => 'Per person, includes coffee, tea, snacks'],
            ['name' => 'Breakfast Buffet', 'category' => 'catering', 'is_free' => false, 'pricing_type' => 'per_meal', 'price' => 150, 'description' => 'Per person per day - local breakfast'],
            ['name' => 'Lunch Buffet', 'category' => 'catering', 'is_free' => false, 'pricing_type' => 'per_meal', 'price' => 300, 'description' => 'Per person per day - full lunch'],
            ['name' => 'Afternoon Snacks', 'category' => 'refreshment', 'is_free' => false, 'pricing_type' => 'per_pax', 'price' => 75, 'description' => 'Per person, includes pastries and drinks'],
            ['name' => 'Dinner Package', 'category' => 'catering', 'is_free' => false, 'pricing_type' => 'per_meal', 'price' => 400, 'description' => 'Per person per day - premium dinner'],
            
            // Services - Paid
            ['name' => 'Event Coordinator', 'category' => 'service', 'is_free' => false, 'pricing_type' => 'hourly', 'price' => 200, 'description' => 'Professional event coordination'],
            ['name' => 'Audio/Visual Technician', 'category' => 'service', 'is_free' => false, 'pricing_type' => 'hourly', 'price' => 150, 'description' => 'Technical support on-site'],
        ];

        $createdFacilities = [];
        foreach ($facilities as $facility) {
            $createdFacilities[$facility['name']] = $room->facilities()->create($facility);
        }

        // Create pre-configured packages
        $basicPackage = $room->packages()->create([
            'name' => '3-Day Basic Conference Package',
            'description' => 'Perfect for small meetings. Includes hall, WiFi, projector, and daily refreshments.',
            'duration_days' => 3,
            'package_type' => 'basic',
            'base_price' => 3000,
            'currency' => 'MVR',
            'discount_percentage' => 0,
            'is_active' => true,
            'vendor_notes' => 'Includes 10 basic chairs and tables. Additional seating can be added.',
        ]);

        // Attach facilities to basic package
        $basicPackage->facilities()->sync([
            $createdFacilities['Projector']->id,
            $createdFacilities['Basic WiFi']->id,
            $createdFacilities['Whiteboard']->id,
        ]);

        // Standard package with catering
        $standardPackage = $room->packages()->create([
            'name' => '3-Day Standard Conference Package',
            'description' => 'Ideal for training sessions. Includes hall, full AV setup, daily meals & refreshments, event coordinator.',
            'duration_days' => 3,
            'package_type' => 'standard',
            'base_price' => 9500,
            'currency' => 'MVR',
            'discount_percentage' => 5,
            'is_active' => true,
            'vendor_notes' => 'Includes coffee/tea service, breakfast, lunch, afternoon snacks. Event coordinator available 9am-6pm daily.',
        ]);

        // Attach facilities to standard package
        $standardPackage->facilities()->sync([
            $createdFacilities['Projector']->id,
            $createdFacilities['Whiteboard']->id,
            $createdFacilities['Basic WiFi']->id,
            $createdFacilities['Video Conferencing System']->id,
            $createdFacilities['Coffee & Tea Service']->id,
            $createdFacilities['Breakfast Buffet']->id,
            $createdFacilities['Lunch Buffet']->id,
            $createdFacilities['Afternoon Snacks']->id,
            $createdFacilities['Event Coordinator']->id,
        ]);

        // Premium all-inclusive package
        $premiumPackage = $room->packages()->create([
            'name' => '3-Day Premium Conference Package',
            'description' => 'Everything included! Full conference setup with meals, premium catering, dedicated support team.',
            'duration_days' => 3,
            'package_type' => 'premium',
            'base_price' => 15000,
            'currency' => 'MVR',
            'discount_percentage' => 10,
            'is_active' => true,
            'vendor_notes' => 'Full VIP treatment. Includes all meals, drinks, AV support, event coordinator, and technical support.',
        ]);

        // Attach all facilities to premium package
        $premiumPackage->facilities()->sync($createdFacilities->pluck('id')->all());

        // Create transfer options for resort conference room
        $room->transferOptions()->createMany([
            [
                'transfer_type' => 'airport_pickup',
                'origin_location' => 'Malé International Airport (MLE)',
                'destination_location' => 'Tropical Paradise Resort',
                'description' => 'Scenic speedboat transfer from airport to resort conference venue',
                'price_per_person' => 250,
                'group_size_min' => 1,
                'group_size_max' => 20,
                'duration_minutes' => 45,
                'availability' => 'daily',
                'is_active' => true,
            ],
            [
                'transfer_type' => 'airport_dropoff',
                'origin_location' => 'Tropical Paradise Resort',
                'destination_location' => 'Malé International Airport (MLE)',
                'description' => 'Return speedboat transfer from resort to airport',
                'price_per_person' => 250,
                'group_size_min' => 1,
                'group_size_max' => 20,
                'duration_minutes' => 45,
                'availability' => 'daily',
                'is_active' => true,
            ],
            [
                'transfer_type' => 'airport_roundtrip',
                'origin_location' => 'Malé International Airport (MLE)',
                'destination_location' => 'Tropical Paradise Resort',
                'description' => 'Round-trip speedboat transfer (pickup & drop-off)',
                'price_per_person' => 450,
                'group_size_min' => 1,
                'group_size_max' => 20,
                'duration_minutes' => 90,
                'availability' => 'daily',
                'is_active' => true,
            ],
            [
                'transfer_type' => 'inter_island',
                'origin_location' => 'Any nearby island',
                'destination_location' => 'Tropical Paradise Resort',
                'description' => 'Transfer between nearby islands to conference venue',
                'price_per_person' => 150,
                'group_size_min' => 1,
                'group_size_max' => 30,
                'duration_minutes' => 30,
                'availability' => 'daily',
                'is_active' => true,
            ],
            [
                'transfer_type' => 'resort_shuttle',
                'origin_location' => 'Resort accommodations',
                'destination_location' => 'Conference hall',
                'description' => 'Complimentary resort shuttle service for registered guests',
                'price_per_person' => 0,
                'group_size_min' => 1,
                'group_size_max' => 50,
                'duration_minutes' => 10,
                'availability' => 'daily',
                'is_active' => true,
            ],
            [
                'transfer_type' => 'speedboat',
                'origin_location' => 'Any excursion point',
                'destination_location' => 'Tropical Paradise Resort',
                'description' => 'Premium speedboat charter for group excursions',
                'price_per_person' => 500,
                'group_size_min' => 5,
                'group_size_max' => 20,
                'duration_minutes' => 120,
                'availability' => 'daily',
                'is_active' => true,
            ],
        ]);
    }
}



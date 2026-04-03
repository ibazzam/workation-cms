<?php

namespace Database\Seeders;

use App\Models\AccommodationRoom;
use App\Models\RoomAmenity;
use App\Models\AccommodationPackage;
use App\Models\VendorProperty;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccommodationRoomSeeder extends Seeder
{
    public function run(): void
    {
        // Create or fetch sample vendor user
        $vendor = User::firstOrCreate(
            ['email' => 'resort-vendor@example.com'],
            [
                'name' => 'Paradise Resort Vendor',
                'portal_role' => 'vendor',
                'portal_enabled' => true,
            ]
        );

        // Create or fetch sample property
        $property = VendorProperty::firstOrCreate(
            ['vendor_user_id' => $vendor->id, 'name' => 'Paradise Island Resort'],
            [
                'property_type' => 'accommodation',
                'location' => 'North Male Atoll, Maldives',
                'description' => 'Luxury resort with ocean views and comprehensive amenities',
                'status' => 'active',
                'base_price' => 250,
                'currency' => 'MVR',
                'max_guests' => 4,
            ]
        );

        // Create Room Amenities (20+ options across categories)
        $amenities = [
            // Connectivity (5)
            ['name' => 'WiFi', 'amenity_category' => 'connectivity', 'pricing_type' => 'nightly', 'price' => 50, 'is_included_in_base' => true],
            ['name' => 'Fiber Internet', 'amenity_category' => 'connectivity', 'pricing_type' => 'nightly', 'price' => 80],
            ['name' => 'TV/Cable', 'amenity_category' => 'connectivity', 'pricing_type' => 'nightly', 'price' => 30, 'is_included_in_base' => true],
            ['name' => 'Smart TV Streaming', 'amenity_category' => 'connectivity', 'pricing_type' => 'nightly', 'price' => 40],
            ['name' => 'Bluetooth Speaker', 'amenity_category' => 'connectivity', 'pricing_type' => 'flat', 'price' => 500],

            // Dining (6)
            ['name' => 'Continental Breakfast', 'amenity_category' => 'dining', 'pricing_type' => 'per_pax_per_night', 'price' => 200],
            ['name' => 'Full Breakfast Buffet', 'amenity_category' => 'dining', 'pricing_type' => 'per_pax_per_night', 'price' => 350],
            ['name' => 'Dinner Package', 'amenity_category' => 'dining', 'pricing_type' => 'per_pax_per_night', 'price' => 400],
            ['name' => 'All-Inclusive Meals', 'amenity_category' => 'dining', 'pricing_type' => 'per_pax_per_night', 'price' => 600],
            ['name' => 'Room Service (10 hours)', 'amenity_category' => 'dining', 'pricing_type' => 'nightly', 'price' => 150],
            ['name' => 'Minibar Setup', 'amenity_category' => 'dining', 'pricing_type' => 'flat', 'price' => 300],

            // Wellness (6)
            ['name' => 'Spa Access', 'amenity_category' => 'wellness', 'pricing_type' => 'per_pax', 'price' => 250],
            ['name' => 'Massage Treatment (60min)', 'amenity_category' => 'wellness', 'pricing_type' => 'per_pax', 'price' => 450],
            ['name' => 'Yoga Class', 'amenity_category' => 'wellness', 'pricing_type' => 'one_time', 'price' => 150],
            ['name' => 'Meditation Session', 'amenity_category' => 'wellness', 'pricing_type' => 'one_time', 'price' => 100],
            ['name' => 'Gym Access', 'amenity_category' => 'wellness', 'pricing_type' => 'nightly', 'price' => 50, 'is_included_in_base' => true],
            ['name' => 'Sauna/Steam Room', 'amenity_category' => 'wellness', 'pricing_type' => 'nightly', 'price' => 80],

            // Transport (4)
            ['name' => 'Airport Transfer (Round-trip)', 'amenity_category' => 'transport', 'pricing_type' => 'per_pax', 'price' => 300],
            ['name' => 'Island Shuttle', 'amenity_category' => 'transport', 'pricing_type' => 'flat', 'price' => 150],
            ['name' => 'Speedboat Charter', 'amenity_category' => 'transport', 'pricing_type' => 'per_pax', 'price' => 500],
            ['name' => 'Bicycle Rental', 'amenity_category' => 'transport', 'pricing_type' => 'nightly', 'price' => 100],

            // Services (3)
            ['name' => 'Late Checkout (until 4pm)', 'amenity_category' => 'services', 'pricing_type' => 'one_time', 'price' => 200],
            ['name' => 'Early Checkin (before 10am)', 'amenity_category' => 'services', 'pricing_type' => 'one_time', 'price' => 150],
            ['name' => 'Laundry Service', 'amenity_category' => 'services', 'pricing_type' => 'nightly', 'price' => 100],

            // Parking (2)
            ['name' => 'Resort Parking', 'amenity_category' => 'parking', 'pricing_type' => 'nightly', 'price' => 50, 'is_included_in_base' => true],
            ['name' => 'Valet Parking', 'amenity_category' => 'parking', 'pricing_type' => 'nightly', 'price' => 100],

            // Recreation (2)
            ['name' => 'Water Sports Package', 'amenity_category' => 'recreation', 'pricing_type' => 'per_pax', 'price' => 350],
            ['name' => 'Snorkeling Equipment Rental', 'amenity_category' => 'recreation', 'pricing_type' => 'nightly', 'price' => 80],
        ];

        $createdAmenities = [];
        foreach ($amenities as $amenityData) {
            $createdAmenities[] = RoomAmenity::firstOrCreate(
                ['property_id' => $property->id, 'name' => $amenityData['name']],
                array_merge($amenityData, ['property_id' => $property->id, 'currency' => 'MVR'])
            );
        }

        // Create Room Types with amenities attached
        $roomTypes = [
            [
                'room_type' => 'single',
                'capacity_guests' => 1,
                'total_rooms_available' => 5,
                'base_price_per_night' => 300,
                'description' => 'Cozy single room with ocean view',
                'max_occupancy' => 1,
                'amenity_indices' => [0, 2, 7, 11, 21, 23], // WiFi, TV, Room Service, Gym, Parking, Water Sports
            ],
            [
                'room_type' => 'double',
                'capacity_guests' => 2,
                'total_rooms_available' => 12,
                'base_price_per_night' => 450,
                'description' => 'Spacious double room with king bed and balcony',
                'max_occupancy' => 2,
                'amenity_indices' => [0, 2, 5, 7, 11, 14, 21, 23], // WiFi, TV, Breakfast, Room Service, Gym, Sauna, Parking, Water Sports
            ],
            [
                'room_type' => 'triple',
                'capacity_guests' => 3,
                'total_rooms_available' => 8,
                'base_price_per_night' => 600,
                'description' => 'Triple room perfect for families',
                'max_occupancy' => 3,
                'amenity_indices' => [0, 2, 5, 7, 11, 14, 17, 21, 23],
            ],
            [
                'room_type' => 'suite',
                'capacity_guests' => 4,
                'total_rooms_available' => 4,
                'base_price_per_night' => 900,
                'description' => 'Luxury suite with separate living area',
                'max_occupancy' => 4,
                'amenity_indices' => [0, 2, 5, 7, 8, 11, 12, 14, 16, 17, 20, 21, 23],
            ],
            [
                'room_type' => 'villa',
                'capacity_guests' => 4,
                'total_rooms_available' => 3,
                'base_price_per_night' => 1500,
                'description' => 'Private beach villa with plunge pool',
                'max_occupancy' => 4,
                'amenity_indices' => [0, 2, 5, 7, 8, 9, 11, 12, 13, 14, 16, 17, 18, 20, 21, 23],
            ],
        ];

        $createdRooms = [];
        foreach ($roomTypes as $roomData) {
            $amenityIndices = $roomData['amenity_indices'];
            unset($roomData['amenity_indices']);

            $room = AccommodationRoom::firstOrCreate(
                ['property_id' => $property->id, 'room_type' => $roomData['room_type']],
                array_merge($roomData, ['property_id' => $property->id, 'currency' => 'MVR'])
            );

            // Attach amenities
            $selectedAmenities = collect($createdAmenities)->filter(function ($amenity, $key) use ($amenityIndices) {
                return in_array($key, $amenityIndices);
            })->pluck('id');

            if ($room->amenities()->count() === 0) {
                $room->amenities()->attach($selectedAmenities);
            }

            $createdRooms[] = $room;
        }

        // Create Accommodation Packages (3-tier system)
        $packages = [
            [
                'package_name' => 'Weekend Escape',
                'description' => 'Perfect for a quick getaway with essentials included',
                'duration_nights' => 2,
                'base_price' => 800,
                'discount_percentage' => 5,
                'room_index' => 1, // Double room
                'amenity_indices' => [0, 2, 5, 11, 21], // WiFi, TV, Breakfast, Gym, Parking
            ],
            [
                'package_name' => 'Relaxation Retreat',
                'description' => '3-day wellness package with spa and meals included',
                'duration_nights' => 3,
                'base_price' => 2500,
                'discount_percentage' => 10,
                'room_index' => 2, // Triple room
                'amenity_indices' => [0, 2, 5, 7, 11, 12, 14, 21, 23], // WiFi, TV, Full Breakfast, Dinner, Gym, Spa, Sauna, Parking, Water Sports
            ],
            [
                'package_name' => 'Ultimate Luxury',
                'description' => 'Premium 4-day resort experience with all amenities',
                'duration_nights' => 4,
                'base_price' => 6000,
                'discount_percentage' => 15,
                'room_index' => 4, // Villa
                'amenity_indices' => [0, 2, 5, 8, 11, 12, 13, 14, 16, 17, 18, 20, 21, 23], // Everything premium
            ],
        ];

        foreach ($packages as $packageData) {
            $roomIndex = $packageData['room_index'];
            $amenityIndices = $packageData['amenity_indices'];
            unset($packageData['room_index'], $packageData['amenity_indices']);

            $package = AccommodationPackage::firstOrCreate(
                ['property_id' => $property->id, 'package_name' => $packageData['package_name']],
                array_merge($packageData, [
                    'property_id' => $property->id,
                    'room_id' => $createdRooms[$roomIndex]->id,
                    'currency' => 'MVR',
                ])
            );

            // Attach included amenities
            $selectedAmenities = collect($createdAmenities)->filter(function ($amenity, $key) use ($amenityIndices) {
                return in_array($key, $amenityIndices);
            })->pluck('id');

            if ($package->includedAmenities()->count() === 0) {
                $package->includedAmenities()->attach($selectedAmenities);
            }
        }

        $this->command->info('✓ Accommodation system seeded: 5 room types, 24 amenities, 3 packages');
    }
}

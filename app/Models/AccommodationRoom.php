<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccommodationRoom extends Model
{
    protected $table = 'accommodation_rooms';
    protected $fillable = [
        'property_id',
        'room_type',
        'capacity_guests',
        'total_rooms_available',
        'base_price_per_night',
        'currency',
        'description',
        'max_occupancy',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_price_per_night' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(VendorProperty::class, 'property_id');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            RoomAmenity::class,
            'room_amenity',
            'room_id',
            'amenity_id'
        )->withTimestamps();
    }

    public function packages(): HasMany
    {
        return $this->hasMany(AccommodationPackage::class, 'room_id');
    }

    /**
     * Calculate nightly rate based on guests
     */
    public function calculateNightlyRate(int $guests = 1): float
    {
        $basePrice = (float) $this->base_price_per_night;
        // Add per_pax surcharge if exceeds standard occupancy (e.g., 2 guests)
        $standardOccupancy = 2;
        if ($guests > $standardOccupancy) {
            $extraGuests = $guests - $standardOccupancy;
            $perPaxSurcharge = $basePrice * 0.15; // 15% per extra guest
            return $basePrice + ($extraGuests * $perPaxSurcharge);
        }
        return $basePrice;
    }

    /**
     * Calculate stay cost (room + amenities)
     */
    public function calculateStayCost(int $nights = 1, int $guests = 1, array $amenityIds = []): array
    {
        $nightlyRate = $this->calculateNightlyRate($guests);
        $roomTotal = $nightlyRate * $nights;

        $amenityTotal = 0;
        $amenityBreakdown = [];

        if (!empty($amenityIds)) {
            $selectedAmenities = $this->amenities()->whereIn('room_amenity.amenity_id', $amenityIds)->get();
            foreach ($selectedAmenities as $amenity) {
                $amenityCost = $amenity->calculatePrice($nights, $guests);
                $amenityTotal += $amenityCost;
                $amenityBreakdown[] = [
                    'name' => $amenity->name,
                    'price' => $amenity->price,
                    'pricing_type' => $amenity->pricing_type,
                    'total' => $amenityCost,
                ];
            }
        }

        return [
            'nightly_rate' => round($nightlyRate, 2),
            'room_nights' => $nights,
            'room_subtotal' => round($roomTotal, 2),
            'amenity_breakdown' => $amenityBreakdown,
            'amenity_total' => round($amenityTotal, 2),
            'grand_total' => round($roomTotal + $amenityTotal, 2),
        ];
    }

    public function getCapacityLabel(): string
    {
        return match ($this->room_type) {
            'single' => 'Single Room (1 Guest)',
            'double' => 'Double Room (Up to 2 Guests)',
            'triple' => 'Triple Room (Up to 3 Guests)',
            'suite' => 'Suite (Up to 4+ Guests)',
            'villa' => 'Private Villa (Variable)',
            default => "{$this->room_type} ({$this->capacity_guests} Guests)",
        };
    }
}

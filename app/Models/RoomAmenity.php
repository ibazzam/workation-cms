<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoomAmenity extends Model
{
    protected $table = 'room_amenities';
    protected $fillable = [
        'property_id',
        'name',
        'description',
        'amenity_category',
        'pricing_type',
        'price',
        'currency',
        'is_included_in_base',
        'is_active',
    ];

    protected $casts = [
        'is_included_in_base' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(VendorProperty::class, 'property_id');
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(
            AccommodationRoom::class,
            'room_amenity',
            'amenity_id',
            'room_id'
        )->withTimestamps();
    }

    /**
     * Calculate amenity cost based on pricing type
     * pricing_type: nightly, per_pax, per_pax_per_night, one_time, flat
     */
    public function calculatePrice(int $nights = 1, int $guests = 1): float
    {
        if ($this->is_included_in_base) {
            return 0;
        }

        $price = (float) $this->price;

        return match ($this->pricing_type) {
            'nightly' => $price * $nights,                          // E.g., WiFi: 50 MVR/night
            'per_pax' => $price * $guests,                          // E.g., Breakfast: 150 MVR/pax (fixed)
            'per_pax_per_night' => $price * $nights * $guests,      // E.g., Meals: 200 MVR/pax/night
            'one_time' => $price,                                   // E.g., Late checkout: 300 MVR
            'flat' => $price,                                       // E.g., Airport transfer: 500 MVR
            default => 0,
        };
    }

    public function getCategoryLabel(): string
    {
        return match ($this->amenity_category) {
            'connectivity' => '📡 Connectivity',
            'dining' => '🍽️ Dining',
            'wellness' => '🧘 Wellness',
            'transport' => '🚗 Transport',
            'services' => '🔧 Services',
            'recreation' => '🎮 Recreation',
            'parking' => '🅿️ Parking',
            default => $this->amenity_category,
        };
    }

    public function getPricingLabel(): string
    {
        return match ($this->pricing_type) {
            'nightly' => 'Per Night',
            'per_pax' => 'Per Guest',
            'per_pax_per_night' => 'Per Guest Per Night',
            'one_time' => 'One-Time Charge',
            'flat' => 'Fixed',
            default => 'Variable',
        };
    }
}

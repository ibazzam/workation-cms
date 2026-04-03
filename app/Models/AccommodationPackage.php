<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccommodationPackage extends Model
{
    protected $table = 'accommodation_packages';
    protected $fillable = [
        'property_id',
        'room_id',
        'package_name',
        'description',
        'duration_nights',
        'base_price',
        'discount_percentage',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
        'discount_percentage' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(VendorProperty::class, 'property_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(AccommodationRoom::class, 'room_id');
    }

    public function includedAmenities(): BelongsToMany
    {
        return $this->belongsToMany(
            RoomAmenity::class,
            'package_amenity',
            'package_id',
            'amenity_id'
        )->withTimestamps();
    }

    /**
     * Calculate package total with included amenities
     */
    public function calculatePackagePrice(int $guests = 1): array
    {
        $basePrice = (float) $this->base_price;
        $includedAmenities = $this->includedAmenities()->where('is_active', true)->get();
        
        $amenityTotal = 0;
        $amenityList = [];

        foreach ($includedAmenities as $amenity) {
            $amenityCost = $amenity->calculatePrice($this->duration_nights, $guests);
            $amenityTotal += $amenityCost;
            $amenityList[] = [
                'name' => $amenity->name,
                'type' => $amenity->amenity_category,
                'pricing_type' => $amenity->pricing_type,
                'included_cost' => round($amenityCost, 2),
            ];
        }

        $subtotal = $basePrice + $amenityTotal;
        $discountAmount = ($subtotal * $this->discount_percentage) / 100;
        $finalPrice = $subtotal - $discountAmount;

        return [
            'package_name' => $this->package_name,
            'duration_nights' => $this->duration_nights,
            'base_room_price' => round($basePrice, 2),
            'included_amenities_cost' => round($amenityTotal, 2),
            'amenity_breakdown' => $amenityList,
            'subtotal' => round($subtotal, 2),
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => round($discountAmount, 2),
            'final_price' => round($finalPrice, 2),
            'price_per_night' => round($finalPrice / $this->duration_nights, 2),
        ];
    }

    public function getSummary(): string
    {
        $amenities = $this->includedAmenities()
            ->where('is_active', true)
            ->pluck('name')
            ->join(', ');

        return "{$this->duration_nights}-Night Package | Includes: {$amenities}";
    }

    public function getValueProposition(): array
    {
        $calc = $this->calculatePackagePrice();
        $regularNightCost = ($calc['base_room_price'] / $this->duration_nights);
        $packageNightCost = $calc['price_per_night'];
        $savingsPerNight = $regularNightCost - $packageNightCost;

        return [
            'title' => $this->package_name,
            'nights' => $this->duration_nights,
            'total_value' => $calc['final_price'],
            'price_per_night' => $packageNightCost,
            'savings_per_night' => round($savingsPerNight, 2),
            'total_savings' => round($savingsPerNight * $this->duration_nights, 2),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConferenceRoomFacility extends Model
{
    use HasFactory;

    protected $table = 'conference_room_facilities';

    protected $fillable = [
        'conference_room_id',
        'name',
        'description',
        'category',
        'is_free',
        'pricing_type',
        'price',
        'currency',
        'quantity_available',
        'is_available',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_available' => 'boolean',
        'price' => 'decimal:2',
        'quantity_available' => 'integer',
    ];

    public function conferenceRoom(): BelongsTo
    {
        return $this->belongsTo(ConferenceRoom::class, 'conference_room_id');
    }

    /**
     * Calculate price based on pricing type and parameters
     * 
     * $pricingType can be:
     * - 'hourly': charged per hour
     * - 'per_unit': charged per unit (e.g., chairs)
     * - 'per_pax': charged per person
     * - 'per_meal': charged per pax per day
     * - 'flat': single charge regardless of duration/quantity
     */
    public function calculatePrice(int $quantity = 1, int $hours = 1, int $days = 1, int $pax = 1): float
    {
        if ($this->is_free) {
            return 0;
        }

        $price = (float) $this->price;

        return match ($this->pricing_type) {
            'hourly' => $price * $hours,
            'per_unit' => $price * $quantity,
            'per_pax' => $price * $pax,
            'per_meal' => $price * $pax * $days, // e.g., breakfast/lunch/dinner per day
            'flat' => $price,
            default => 0,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConferenceRoomPackage extends Model
{
    use HasFactory;

    protected $table = 'conference_room_packages';

    protected $fillable = [
        'conference_room_id',
        'name',
        'description',
        'duration_days',
        'package_type', // 'basic', 'standard', 'premium', 'custom'
        'base_price',
        'currency',
        'discount_percentage',
        'is_active',
        'vendor_notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'duration_days' => 'integer',
    ];

    public function conferenceRoom(): BelongsTo
    {
        return $this->belongsTo(ConferenceRoom::class, 'conference_room_id');
    }

    /**
     * Facilities included in this package
     */
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(
            ConferenceRoomFacility::class,
            'package_facility',
            'package_id',
            'facility_id'
        );
    }

    /**
     * Calculate total package price with all included facilities
     */
    public function calculateTotalPrice(int $pax = 1): float
    {
        $base = (float) $this->base_price;
        
        // Add facility costs (per_pax and per_meal scale with pax and days)
        $facilityCosts = $this->facilities->sum(function (ConferenceRoomFacility $facility) {
            return $facility->calculatePrice(
                quantity: 1,
                hours: 1,
                days: $this->duration_days,
                pax: 1 // facilities will multiply by pax as needed
            );
        });

        $total = $base + $facilityCosts;

        // Apply discount if any
        if ($this->discount_percentage > 0) {
            $total = $total * (1 - ($this->discount_percentage / 100));
        }

        return round($total, 2);
    }

    /**
     * Get human-readable package summary
     */
    public function getSummary(): string
    {
        $facilitiesCount = $this->facilities()->count();
        $facilityList = $this->facilities->pluck('name')->join(', ');
        
        return "{$this->duration_days}-Day Package | Includes: {$facilityList}";
    }
}

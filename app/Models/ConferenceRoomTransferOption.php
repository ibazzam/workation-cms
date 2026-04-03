<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConferenceRoomTransferOption extends Model
{
    use HasFactory;

    protected $table = 'conference_room_transfer_options';

    protected $fillable = [
        'conference_room_id',
        'transfer_type', // 'airport_pickup', 'airport_dropoff', 'inter_island', 'speedboat', 'resort_shuttle', 'custom'
        'origin_location',
        'destination_location',
        'description',
        'price_per_person',
        'group_size_min',
        'group_size_max',
        'duration_minutes',
        'availability',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price_per_person' => 'decimal:2',
        'group_size_min' => 'integer',
        'group_size_max' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function conferenceRoom(): BelongsTo
    {
        return $this->belongsTo(ConferenceRoom::class, 'conference_room_id');
    }

    /**
     * Calculate total transfer cost for a group
     * @param int $pax Number of people
     * @param int $roundTrip 1 for one-way, 2 for round-trip
     */
    public function calculateTransferCost(int $pax = 1, int $roundTrip = 1): array
    {
        $perPersonPrice = (float) $this->price_per_person;
        $totalCost = $perPersonPrice * $pax * $roundTrip;

        return [
            'per_person' => $perPersonPrice,
            'group_size' => $pax,
            'trips' => $roundTrip,
            'total' => round($totalCost, 2),
            'subtotal_display' => "MVR {$perPersonPrice} × {$pax} person" . ($pax > 1 ? 's' : '') . ($roundTrip === 2 ? ' × 2 trips' : ''),
        ];
    }

    /**
     * Check if transfer is available for group size
     */
    public function isAvailableForGroupSize(int $pax): bool
    {
        $min = (int) ($this->group_size_min ?? 1);
        $max = (int) ($this->group_size_max ?? 999);
        
        return $pax >= $min && $pax <= $max;
    }

    /**
     * Get readable transfer type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->transfer_type) {
            'airport_pickup' => '✈️ Airport Pickup',
            'airport_dropoff' => '✈️ Airport Drop-off',
            'airport_roundtrip' => '✈️ Airport Round-trip',
            'inter_island' => '🚤 Inter-island Transfer',
            'speedboat' => '⚡ Speedboat Transfer',
            'resort_shuttle' => '🚐 Resort Shuttle',
            'custom' => '🛫 Custom Transfer',
            default => 'Transfer Service',
        };
    }
}

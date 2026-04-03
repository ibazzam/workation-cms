<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConferenceRoom extends Model
{
    use HasFactory;

    protected $table = 'conference_rooms';

    protected $fillable = [
        'name',
        'description',
        'location',
        'atoll',
        'island',
        'capacity',
        'base_price',
        'currency',
        'hourly_rate',
        'amenities',
        'is_active',
        'vendor_id',
        'is_resort_venue',
        'resort_name',
        'airport_name',
    ];

    protected $casts = [
        'amenities' => 'array',
        'is_active' => 'boolean',
        'is_resort_venue' => 'boolean',
        'capacity' => 'integer',
        'base_price' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    public function facilities(): HasMany
    {
        return $this->hasMany(ConferenceRoomFacility::class, 'conference_room_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ConferenceRoomPackage::class, 'conference_room_id');
    }

    public function transferOptions(): HasMany
    {
        return $this->hasMany(ConferenceRoomTransferOption::class, 'conference_room_id');
    }

    public function getAvailableFacilities()
    {
        return $this->facilities()->where('is_available', true)->get();
    }

    public function getActivePackages()
    {
        return $this->packages()->where('is_active', true)->get();
    }

    public function getAvailableTransfers()
    {
        return $this->transferOptions()->where('is_active', true)->get();
    }

    public function hasTransferOptions(): bool
    {
        return $this->is_resort_venue && $this->transferOptions()->where('is_active', true)->exists();
    }
}

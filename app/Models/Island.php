<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Island extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'local_name',
        'atoll_id',
        'photo_path',
        'description',
        'population',
        'nearest_airport_name',
        'distance_from_airport_km',
        'is_inhabited',
        'island_type',
        'wikipedia_title',
        'source',
    ];

    protected $casts = [
        'is_inhabited' => 'boolean',
    ];

    public function atoll(): BelongsTo
    {
        return $this->belongsTo(Atoll::class);
    }

    /** Resolve the public-facing URL key: slug if set, otherwise name-derived. */
    public function getUrlSlugAttribute(): string
    {
        if ($this->slug) {
            return $this->slug;
        }
        return \Illuminate\Support\Str::slug($this->name);
    }
}

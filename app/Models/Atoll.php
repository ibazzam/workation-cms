<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Atoll extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'code',
        'photo_path',
        'description',
        'wikipedia_title',
        'source',
    ];

    public function islands(): HasMany
    {
        return $this->hasMany(Island::class);
    }

    public function scopeOrderedByCode(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE WHEN code IS NULL OR TRIM(code) = '' THEN 1 ELSE 0 END")
            ->orderByRaw('LOWER(code)')
            ->orderByRaw('LOWER(name)');
    }
}

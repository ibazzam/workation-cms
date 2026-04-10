<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}

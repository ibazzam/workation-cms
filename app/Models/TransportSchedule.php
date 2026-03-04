<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportSchedule extends Model
{
    use HasFactory;

    protected $table = 'transport_schedules';

    protected $fillable = [
        'route_id',
        'operator_id',
        'departure_at',
        'arrival_at',
        'published_version',
    ];

    public function inventory()
    {
        return $this->hasMany(TransportInventory::class, 'schedule_id');
    }
}

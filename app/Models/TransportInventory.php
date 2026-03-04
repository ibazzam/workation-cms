<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportInventory extends Model
{
    use HasFactory;

    protected $table = 'transport_inventory';

    protected $fillable = [
        'schedule_id',
        'seat_class',
        'total_seats',
        'reserved_seats',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function schedule()
    {
        return $this->belongsTo(TransportSchedule::class, 'schedule_id');
    }
}

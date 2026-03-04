<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransportHold extends Model
{
    use HasFactory;

    protected $table = 'transport_holds';

    protected $fillable = [
        'quote_id',
        'schedule_id',
        'seat_class',
        'seats_reserved',
        'status',
        'ttl_expires_at',
        'idempotency_key',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'array',
        'ttl_expires_at' => 'datetime',
    ];

    /**
     * Create a pessimistic hold with an optional idempotency key.
     *
     * @throws \Exception
     */
    public static function createHold(int $scheduleId, ?string $seatClass, int $seats, ?string $idempotencyKey = null, int $ttlSeconds = 900, $createdBy = null): self
    {
        return DB::transaction(function () use ($scheduleId, $seatClass, $seats, $idempotencyKey, $ttlSeconds, $createdBy) {
            if ($idempotencyKey) {
                $existing = self::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $inventory = TransportInventory::where('schedule_id', $scheduleId)
                ->where('seat_class', $seatClass)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                throw new \Exception('Inventory not found');
            }

            $available = max(0, $inventory->total_seats - $inventory->reserved_seats);

            if ($available < $seats) {
                throw new \Exception('Not enough seats available');
            }

            $inventory->reserved_seats += $seats;
            $inventory->save();

            $hold = self::create([
                'schedule_id' => $scheduleId,
                'seat_class' => $seatClass,
                'seats_reserved' => $seats,
                'status' => 'held',
                'ttl_expires_at' => Carbon::now()->addSeconds($ttlSeconds),
                'idempotency_key' => $idempotencyKey,
                'created_by' => $createdBy,
            ]);

            return $hold;
        });
    }

    public function confirm(): self
    {
        return DB::transaction(function () {
            if ($this->status === 'confirmed') {
                return $this;
            }

            $this->status = 'confirmed';
            $this->save();

            return $this;
        });
    }

    public function release(): self
    {
        return DB::transaction(function () {
            if ($this->status === 'released') {
                return $this;
            }

            // reduce reserved seats
            $inventory = TransportInventory::where('schedule_id', $this->schedule_id)
                ->where('seat_class', $this->seat_class)
                ->lockForUpdate()
                ->first();

            if ($inventory) {
                $inventory->reserved_seats = max(0, $inventory->reserved_seats - $this->seats_reserved);
                $inventory->save();
            }

            $this->status = 'released';
            $this->save();

            return $this;
        });
    }
}

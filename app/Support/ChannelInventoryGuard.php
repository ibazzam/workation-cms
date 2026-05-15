<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChannelInventoryGuard
{
    /**
     * Reserve inventory atomically for an external OTA booking.
     *
     * @throws RuntimeException when sellable inventory is insufficient
     */
    public static function reserveFromExternalBooking(
        int $vendorUserId,
        int $vendorPropertyId,
        string $roomKey,
        CarbonInterface|string $fromDate,
        CarbonInterface|string $toDate,
        int $rooms = 1,
        string $source = 'channel'
    ): void {
        $rooms = max(1, $rooms);
        $dates = self::expandDateRange($fromDate, $toDate);

        DB::transaction(static function () use ($vendorUserId, $vendorPropertyId, $roomKey, $dates, $rooms, $source): void {
            foreach ($dates as $date) {
                $row = DB::table('vendor_room_inventory_daily')
                    ->where('vendor_user_id', $vendorUserId)
                    ->where('vendor_property_id', $vendorPropertyId)
                    ->where('room_key', $roomKey)
                    ->whereDate('inventory_date', $date)
                    ->lockForUpdate()
                    ->first();

                if ($row === null) {
                    throw new RuntimeException('Inventory row not initialized for room/date: ' . $roomKey . ' @ ' . $date);
                }

                $physical = max(0, (int) ($row->physical_rooms ?? 0));
                $sold = max(0, (int) ($row->sold_rooms ?? 0));
                $hold = max(0, (int) ($row->hold_rooms ?? 0));
                $buffer = max(0, (int) ($row->safety_buffer ?? 0));
                $sellable = max(0, $physical - $sold - $hold - $buffer);

                if ($sellable < $rooms) {
                    throw new RuntimeException('Insufficient inventory for room/date: ' . $roomKey . ' @ ' . $date);
                }

                $newSold = $sold + $rooms;
                $remaining = max(0, $physical - $newSold - $hold - $buffer);

                DB::table('vendor_room_inventory_daily')
                    ->where('id', (int) $row->id)
                    ->update([
                        'sold_rooms' => $newSold,
                        'closed_out' => $remaining <= 0,
                        'version' => ((int) ($row->version ?? 0)) + 1,
                        'last_source' => trim($source) !== '' ? $source : 'channel',
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    /**
     * Release inventory atomically for external cancellations/modifications.
     */
    public static function releaseFromExternalBooking(
        int $vendorUserId,
        int $vendorPropertyId,
        string $roomKey,
        CarbonInterface|string $fromDate,
        CarbonInterface|string $toDate,
        int $rooms = 1,
        string $source = 'channel'
    ): void {
        $rooms = max(1, $rooms);
        $dates = self::expandDateRange($fromDate, $toDate);

        DB::transaction(static function () use ($vendorUserId, $vendorPropertyId, $roomKey, $dates, $rooms, $source): void {
            foreach ($dates as $date) {
                $row = DB::table('vendor_room_inventory_daily')
                    ->where('vendor_user_id', $vendorUserId)
                    ->where('vendor_property_id', $vendorPropertyId)
                    ->where('room_key', $roomKey)
                    ->whereDate('inventory_date', $date)
                    ->lockForUpdate()
                    ->first();

                if ($row === null) {
                    continue;
                }

                $physical = max(0, (int) ($row->physical_rooms ?? 0));
                $sold = max(0, (int) ($row->sold_rooms ?? 0));
                $hold = max(0, (int) ($row->hold_rooms ?? 0));
                $buffer = max(0, (int) ($row->safety_buffer ?? 0));
                $newSold = max(0, $sold - $rooms);
                $remaining = max(0, $physical - $newSold - $hold - $buffer);

                DB::table('vendor_room_inventory_daily')
                    ->where('id', (int) $row->id)
                    ->update([
                        'sold_rooms' => $newSold,
                        'closed_out' => $remaining <= 0,
                        'version' => ((int) ($row->version ?? 0)) + 1,
                        'last_source' => trim($source) !== '' ? $source : 'channel',
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    /**
     * @return array<int, string> Inclusive date range in Y-m-d format
     */
    private static function expandDateRange(CarbonInterface|string $fromDate, CarbonInterface|string $toDate): array
    {
        $start = $fromDate instanceof CarbonInterface ? Carbon::instance($fromDate) : Carbon::parse((string) $fromDate);
        $end = $toDate instanceof CarbonInterface ? Carbon::instance($toDate) : Carbon::parse((string) $toDate);

        if ($end->lt($start)) {
            throw new RuntimeException('Invalid date range for inventory operation.');
        }

        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        $dates = [];

        while ($cursor->lte($last)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }
}

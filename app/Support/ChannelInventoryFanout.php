<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChannelInventoryFanout
{
    /**
     * Queue outbound inventory sync events for all other active mapped channels.
     *
     * @return array{queued:int,targets:int,error:string|null}
     */
    public static function queueFromInboundEvent(int $sourceAccountId, string $sourceChannelCode, array $normalizedPayload, int $sourceReservationId = 0): array
    {
        if (!Schema::hasTable('vendor_channel_accounts') || !Schema::hasTable('vendor_channel_room_mappings') || !Schema::hasTable('vendor_channel_events') || !Schema::hasTable('vendor_room_inventory_daily')) {
            return ['queued' => 0, 'targets' => 0, 'error' => null];
        }

        $sourceAccount = DB::table('vendor_channel_accounts')
            ->where('id', $sourceAccountId)
            ->first();

        if (!$sourceAccount) {
            return ['queued' => 0, 'targets' => 0, 'error' => 'Source channel account not found.'];
        }

        $roomKey = trim((string) ($normalizedPayload['room_key'] ?? ''));
        if ($roomKey === '') {
            return ['queued' => 0, 'targets' => 0, 'error' => 'Room key missing for fanout.'];
        }

        $mapping = DB::table('vendor_channel_room_mappings')
            ->where('vendor_channel_account_id', $sourceAccountId)
            ->where('external_room_id', $roomKey)
            ->first();

        if (!$mapping) {
            return ['queued' => 0, 'targets' => 0, 'error' => null];
        }

        $internalRoomCategoryId = (int) ($mapping->internal_room_category_id ?? 0);
        $internalAccommodationRoomId = (int) ($mapping->internal_accommodation_room_id ?? 0);
        if ($internalRoomCategoryId <= 0 && $internalAccommodationRoomId <= 0) {
            return ['queued' => 0, 'targets' => 0, 'error' => null];
        }

        $checkIn = trim((string) ($normalizedPayload['check_in_date'] ?? ''));
        $checkOut = trim((string) ($normalizedPayload['check_out_date'] ?? $checkIn));
        if ($checkIn === '' || $checkOut === '') {
            return ['queued' => 0, 'targets' => 0, 'error' => 'Invalid date range for fanout.'];
        }

        $targetsQuery = DB::table('vendor_channel_room_mappings as m')
            ->join('vendor_channel_accounts as a', 'a.id', '=', 'm.vendor_channel_account_id')
            ->where('a.vendor_user_id', (int) ($sourceAccount->vendor_user_id ?? 0))
            ->where('a.id', '!=', $sourceAccountId)
            ->whereRaw('LOWER(TRIM(COALESCE(a.status, ?))) IN (?, ?)', ['disconnected', 'active', 'connected'])
            ->whereRaw('LOWER(TRIM(COALESCE(m.mapping_status, ?))) IN (?)', ['active', 'active'])
            ->where(function ($query) use ($internalRoomCategoryId, $internalAccommodationRoomId): void {
                if ($internalRoomCategoryId > 0 && $internalAccommodationRoomId > 0) {
                    $query->where('m.internal_room_category_id', $internalRoomCategoryId)
                        ->orWhere('m.internal_accommodation_room_id', $internalAccommodationRoomId);
                    return;
                }

                if ($internalRoomCategoryId > 0) {
                    $query->where('m.internal_room_category_id', $internalRoomCategoryId);
                    return;
                }

                $query->where('m.internal_accommodation_room_id', $internalAccommodationRoomId);
            })
            ->select([
                'a.id as target_account_id',
                'a.channel_code as target_channel_code',
                'a.vendor_user_id',
                'a.vendor_property_id',
                'm.external_room_id as target_external_room_id',
            ]);

        $sourcePropertyId = (int) ($sourceAccount->vendor_property_id ?? 0);
        if ($sourcePropertyId > 0) {
            $targetsQuery->where('a.vendor_property_id', $sourcePropertyId);
        }

        $targets = $targetsQuery->get();
        if ($targets->isEmpty()) {
            return ['queued' => 0, 'targets' => 0, 'error' => null];
        }

        $availabilityRows = DB::table('vendor_room_inventory_daily')
            ->where('vendor_user_id', (int) ($sourceAccount->vendor_user_id ?? 0))
            ->where('vendor_property_id', (int) ($sourceAccount->vendor_property_id ?? 0))
            ->where('room_key', $roomKey)
            ->whereBetween('inventory_date', [$checkIn, $checkOut])
            ->orderBy('inventory_date')
            ->get([
                'inventory_date',
                'physical_rooms',
                'sold_rooms',
                'hold_rooms',
                'safety_buffer',
                'closed_out',
                'version',
            ]);

        $baseEventType = strtolower(trim((string) ($normalizedPayload['event_type'] ?? 'reservation.created')));
        $bookingId = trim((string) ($normalizedPayload['booking_id'] ?? ''));
        $version = trim((string) ($normalizedPayload['version'] ?? 'v1'));

        $queued = 0;
        foreach ($targets as $target) {
            $targetAccountId = (int) ($target->target_account_id ?? 0);
            if ($targetAccountId <= 0) {
                continue;
            }

            $targetExternalRoomId = trim((string) ($target->target_external_room_id ?? ''));
            if ($targetExternalRoomId === '') {
                continue;
            }

            $payload = [
                'trigger' => 'inbound_booking_event',
                'source_channel_code' => strtolower(trim($sourceChannelCode)),
                'source_account_id' => $sourceAccountId,
                'source_reservation_id' => $sourceReservationId > 0 ? $sourceReservationId : null,
                'source_event_type' => $baseEventType,
                'source_booking_id' => $bookingId !== '' ? $bookingId : null,
                'target_channel_code' => strtolower(trim((string) ($target->target_channel_code ?? ''))),
                'target_account_id' => $targetAccountId,
                'target_external_room_id' => $targetExternalRoomId,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'inventory' => $availabilityRows->map(static function ($row) {
                    $physical = max(0, (int) ($row->physical_rooms ?? 0));
                    $sold = max(0, (int) ($row->sold_rooms ?? 0));
                    $hold = max(0, (int) ($row->hold_rooms ?? 0));
                    $safety = max(0, (int) ($row->safety_buffer ?? 0));
                    $sellable = max(0, $physical - $sold - $hold - $safety);

                    return [
                        'date' => (string) ($row->inventory_date ?? ''),
                        'physical_rooms' => $physical,
                        'sold_rooms' => $sold,
                        'hold_rooms' => $hold,
                        'safety_buffer' => $safety,
                        'closed_out' => (bool) ($row->closed_out ?? false),
                        'sellable_rooms' => $sellable,
                        'version' => (int) ($row->version ?? 0),
                    ];
                })->values()->all(),
            ];

            $idempotency = implode(':', [
                'fanout',
                $sourceAccountId,
                $targetAccountId,
                $baseEventType !== '' ? $baseEventType : 'event',
                $bookingId !== '' ? $bookingId : 'no-booking',
                $targetExternalRoomId,
                $version !== '' ? $version : 'v1',
            ]);

            try {
                DB::table('vendor_channel_events')->insert([
                    'vendor_channel_account_id' => $targetAccountId,
                    'direction' => 'outbound',
                    'event_type' => 'inventory.sync',
                    'external_event_id' => null,
                    'idempotency_key' => $idempotency,
                    'status' => 'queued',
                    'retry_count' => 0,
                    'http_method' => 'POST',
                    'request_path' => '/channel/outbound/sync',
                    'signature_hash' => null,
                    'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $queued++;
            } catch (QueryException $exception) {
                if (!self::isDuplicateKeyException($exception)) {
                    return ['queued' => $queued, 'targets' => (int) $targets->count(), 'error' => $exception->getMessage()];
                }
            }
        }

        return ['queued' => $queued, 'targets' => (int) $targets->count(), 'error' => null];
    }

    /**
     * Queue outbound inventory sync events based on recently updated internal inventory rows.
     *
     * @return array{scanned:int,queued:int,targets:int,errors:int}
     */
    public static function queueFromRecentInternalInventory(int $minutes = 10, int $limit = 200, bool $dryRun = false): array
    {
        $summary = [
            'scanned' => 0,
            'queued' => 0,
            'targets' => 0,
            'errors' => 0,
        ];

        if (!Schema::hasTable('vendor_channel_accounts')
            || !Schema::hasTable('vendor_channel_room_mappings')
            || !Schema::hasTable('vendor_channel_events')
            || !Schema::hasTable('vendor_room_inventory_daily')) {
            return $summary;
        }

        $minutes = max(1, $minutes);
        $limit = max(1, $limit);

        $slices = DB::table('vendor_room_inventory_daily')
            ->where('updated_at', '>=', now()->subMinutes($minutes))
            ->whereRaw("LOWER(TRIM(COALESCE(last_source, 'manual'))) NOT IN (?, ?, ?)", ['channel', 'channel-init', 'channel-sync'])
            ->select([
                'vendor_user_id',
                'vendor_property_id',
                'internal_room_category_id',
                'internal_accommodation_room_id',
                DB::raw('MIN(inventory_date) as from_date'),
                DB::raw('MAX(inventory_date) as to_date'),
                DB::raw('MAX(updated_at) as max_updated_at'),
            ])
            ->groupBy([
                'vendor_user_id',
                'vendor_property_id',
                'internal_room_category_id',
                'internal_accommodation_room_id',
            ])
            ->orderByDesc('max_updated_at')
            ->limit($limit)
            ->get();

        foreach ($slices as $slice) {
            $summary['scanned']++;

            $vendorUserId = (int) ($slice->vendor_user_id ?? 0);
            $vendorPropertyId = (int) ($slice->vendor_property_id ?? 0);
            $internalRoomCategoryId = (int) ($slice->internal_room_category_id ?? 0);
            $internalAccommodationRoomId = (int) ($slice->internal_accommodation_room_id ?? 0);
            $fromDate = trim((string) ($slice->from_date ?? ''));
            $toDate = trim((string) ($slice->to_date ?? ''));
            $maxUpdatedAt = trim((string) ($slice->max_updated_at ?? ''));

            if ($vendorUserId <= 0 || $fromDate === '' || $toDate === '') {
                continue;
            }

            if ($internalRoomCategoryId <= 0 && $internalAccommodationRoomId <= 0) {
                continue;
            }

            $targetsQuery = DB::table('vendor_channel_room_mappings as m')
                ->join('vendor_channel_accounts as a', 'a.id', '=', 'm.vendor_channel_account_id')
                ->where('a.vendor_user_id', $vendorUserId)
                ->whereRaw('LOWER(TRIM(COALESCE(a.status, ?))) IN (?, ?)', ['disconnected', 'active', 'connected'])
                ->whereRaw('LOWER(TRIM(COALESCE(m.mapping_status, ?))) IN (?)', ['active', 'active'])
                ->where(function ($query) use ($internalRoomCategoryId, $internalAccommodationRoomId): void {
                    if ($internalRoomCategoryId > 0 && $internalAccommodationRoomId > 0) {
                        $query->where('m.internal_room_category_id', $internalRoomCategoryId)
                            ->orWhere('m.internal_accommodation_room_id', $internalAccommodationRoomId);
                        return;
                    }

                    if ($internalRoomCategoryId > 0) {
                        $query->where('m.internal_room_category_id', $internalRoomCategoryId);
                        return;
                    }

                    $query->where('m.internal_accommodation_room_id', $internalAccommodationRoomId);
                })
                ->select([
                    'a.id as target_account_id',
                    'a.channel_code as target_channel_code',
                    'm.external_room_id as target_external_room_id',
                ]);

            if ($vendorPropertyId > 0) {
                $targetsQuery->where('a.vendor_property_id', $vendorPropertyId);
            }

            $targets = $targetsQuery->get();
            if ($targets->isEmpty()) {
                continue;
            }

            $availabilityRows = DB::table('vendor_room_inventory_daily')
                ->where('vendor_user_id', $vendorUserId)
                ->where('vendor_property_id', $vendorPropertyId)
                ->whereBetween('inventory_date', [$fromDate, $toDate])
                ->where(function ($query) use ($internalRoomCategoryId, $internalAccommodationRoomId): void {
                    if ($internalRoomCategoryId > 0 && $internalAccommodationRoomId > 0) {
                        $query->where('internal_room_category_id', $internalRoomCategoryId)
                            ->orWhere('internal_accommodation_room_id', $internalAccommodationRoomId);
                        return;
                    }

                    if ($internalRoomCategoryId > 0) {
                        $query->where('internal_room_category_id', $internalRoomCategoryId);
                        return;
                    }

                    $query->where('internal_accommodation_room_id', $internalAccommodationRoomId);
                })
                ->orderBy('inventory_date')
                ->get([
                    'inventory_date',
                    'physical_rooms',
                    'sold_rooms',
                    'hold_rooms',
                    'safety_buffer',
                    'closed_out',
                    'version',
                ]);

            if ($availabilityRows->isEmpty()) {
                continue;
            }

            foreach ($targets as $target) {
                $targetAccountId = (int) ($target->target_account_id ?? 0);
                $targetExternalRoomId = trim((string) ($target->target_external_room_id ?? ''));
                if ($targetAccountId <= 0 || $targetExternalRoomId === '') {
                    continue;
                }

                $summary['targets']++;

                $payload = [
                    'trigger' => 'internal_inventory_update',
                    'source_channel_code' => 'internal',
                    'source_account_id' => null,
                    'source_event_type' => 'inventory.updated',
                    'target_channel_code' => strtolower(trim((string) ($target->target_channel_code ?? ''))),
                    'target_account_id' => $targetAccountId,
                    'target_external_room_id' => $targetExternalRoomId,
                    'check_in_date' => $fromDate,
                    'check_out_date' => $toDate,
                    'inventory' => $availabilityRows->map(static function ($row) {
                        $physical = max(0, (int) ($row->physical_rooms ?? 0));
                        $sold = max(0, (int) ($row->sold_rooms ?? 0));
                        $hold = max(0, (int) ($row->hold_rooms ?? 0));
                        $safety = max(0, (int) ($row->safety_buffer ?? 0));
                        $sellable = max(0, $physical - $sold - $hold - $safety);

                        return [
                            'date' => (string) ($row->inventory_date ?? ''),
                            'physical_rooms' => $physical,
                            'sold_rooms' => $sold,
                            'hold_rooms' => $hold,
                            'safety_buffer' => $safety,
                            'closed_out' => (bool) ($row->closed_out ?? false),
                            'sellable_rooms' => $sellable,
                            'version' => (int) ($row->version ?? 0),
                        ];
                    })->values()->all(),
                ];

                $idempotency = implode(':', [
                    'fanout-internal',
                    $vendorUserId,
                    $vendorPropertyId > 0 ? $vendorPropertyId : 'shared',
                    $targetAccountId,
                    $targetExternalRoomId,
                    $fromDate,
                    $toDate,
                    $maxUpdatedAt !== '' ? $maxUpdatedAt : 'now',
                ]);

                if ($dryRun) {
                    $summary['queued']++;
                    continue;
                }

                try {
                    DB::table('vendor_channel_events')->insert([
                        'vendor_channel_account_id' => $targetAccountId,
                        'direction' => 'outbound',
                        'event_type' => 'inventory.sync',
                        'external_event_id' => null,
                        'idempotency_key' => $idempotency,
                        'status' => 'queued',
                        'retry_count' => 0,
                        'http_method' => 'POST',
                        'request_path' => '/channel/outbound/sync',
                        'signature_hash' => null,
                        'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $summary['queued']++;
                } catch (QueryException $exception) {
                    if (!self::isDuplicateKeyException($exception)) {
                        $summary['errors']++;
                    }
                }
            }
        }

        return $summary;
    }

    private static function isDuplicateKeyException(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        return str_contains($message, 'duplicate') || str_contains($message, 'unique');
    }
}

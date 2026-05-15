<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChannelReservationIngestor
{
    /**
     * @return array{ok:bool,status:string,message:string,event_id?:int,reservation_id?:int}
     */
    public static function ingest(
        int $vendorChannelAccountId,
        string $channelCode,
        array $payload,
        string $idempotencyKey,
        string $signatureHash,
        string $httpMethod,
        string $requestPath
    ): array {
        $account = DB::table('vendor_channel_accounts')
            ->where('id', $vendorChannelAccountId)
            ->first();

        if (!$account) {
            return ['ok' => false, 'status' => 'not_found', 'message' => 'Channel account not found.'];
        }

        $eventType = strtolower(trim((string) ($payload['event_type'] ?? $payload['type'] ?? 'reservation.created')));
        $externalEventId = trim((string) ($payload['event_id'] ?? $payload['id'] ?? ''));

        try {
            $eventId = (int) DB::table('vendor_channel_events')->insertGetId([
                'vendor_channel_account_id' => (int) $account->id,
                'direction' => 'inbound',
                'event_type' => $eventType,
                'external_event_id' => $externalEventId !== '' ? $externalEventId : null,
                'idempotency_key' => $idempotencyKey,
                'status' => 'received',
                'retry_count' => 0,
                'http_method' => strtoupper(trim($httpMethod)) !== '' ? strtoupper(trim($httpMethod)) : 'POST',
                'request_path' => trim($requestPath) !== '' ? trim($requestPath) : null,
                'signature_hash' => $signatureHash !== '' ? $signatureHash : null,
                'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if (self::isDuplicateKeyException($exception)) {
                return ['ok' => true, 'status' => 'duplicate', 'message' => 'Duplicate event ignored by idempotency key.'];
            }
            throw $exception;
        }

        try {
            $result = DB::transaction(function () use ($account, $payload, $eventType, $channelCode) {
                $bookingId = trim((string) ($payload['booking_id'] ?? $payload['reservation_id'] ?? $payload['id'] ?? ''));
                if ($bookingId === '') {
                    throw new RuntimeException('Missing booking identifier in payload.');
                }

                $roomKey = trim((string) ($payload['room_key'] ?? $payload['external_room_id'] ?? $payload['room_id'] ?? ''));
                if ($roomKey === '') {
                    throw new RuntimeException('Missing room identifier in payload.');
                }

                $rooms = max(1, (int) ($payload['rooms'] ?? $payload['units'] ?? 1));

                [$checkInDate, $checkOutInclusiveDate, $checkOutExclusiveDate] = self::resolveDates($payload);

                $vendorUserId = (int) ($account->vendor_user_id ?? 0);
                $vendorPropertyId = (int) ($payload['vendor_property_id'] ?? $payload['property_id'] ?? $account->vendor_property_id ?? 0);
                if ($vendorUserId <= 0 || $vendorPropertyId <= 0) {
                    throw new RuntimeException('Missing vendor ownership context for this channel account.');
                }

                self::initializeInventoryRowsIfMissing($account, $roomKey, $checkInDate, $checkOutInclusiveDate, $payload);

                $existingLink = DB::table('vendor_channel_reservation_links')
                    ->where('vendor_channel_account_id', (int) $account->id)
                    ->where('external_booking_id', $bookingId)
                    ->first();

                $existingReservation = null;
                if ($existingLink) {
                    $existingReservation = DB::table('vendor_reservations')
                        ->where('id', (int) ($existingLink->vendor_reservation_id ?? 0))
                        ->first();
                }

                $normalizedEventType = self::normalizeEventType($eventType);

                if (in_array($normalizedEventType, ['created', 'confirmed'], true)) {
                    ChannelInventoryGuard::reserveFromExternalBooking(
                        $vendorUserId,
                        $vendorPropertyId,
                        $roomKey,
                        $checkInDate,
                        $checkOutInclusiveDate,
                        $rooms,
                        'channel:' . strtolower(trim((string) $channelCode))
                    );

                    $reservationId = self::upsertReservation(
                        $existingReservation,
                        $vendorUserId,
                        $vendorPropertyId,
                        $channelCode,
                        $bookingId,
                        $payload,
                        $roomKey,
                        $rooms,
                        $checkInDate,
                        $checkOutExclusiveDate,
                        'confirmed'
                    );

                    self::upsertReservationLink((int) $account->id, $reservationId, $bookingId, $payload, 'confirmed');

                    return ['reservation_id' => $reservationId, 'status' => 'processed'];
                }

                if ($normalizedEventType === 'cancelled') {
                    if ($existingReservation) {
                        $syncMeta = self::extractSyncMetaFromReservation($existingReservation);
                        $releaseRoomKey = $syncMeta['room_key'] !== '' ? $syncMeta['room_key'] : $roomKey;
                        $releaseFrom = $syncMeta['check_in'] !== '' ? $syncMeta['check_in'] : $checkInDate;
                        $releaseTo = $syncMeta['check_out_inclusive'] !== '' ? $syncMeta['check_out_inclusive'] : $checkOutInclusiveDate;
                        $releaseRooms = $syncMeta['rooms'] > 0 ? $syncMeta['rooms'] : $rooms;

                        ChannelInventoryGuard::releaseFromExternalBooking(
                            $vendorUserId,
                            $vendorPropertyId,
                            $releaseRoomKey,
                            $releaseFrom,
                            $releaseTo,
                            $releaseRooms,
                            'channel:' . strtolower(trim((string) $channelCode))
                        );

                        DB::table('vendor_reservations')
                            ->where('id', (int) $existingReservation->id)
                            ->update([
                                'status' => 'cancelled',
                                'payment_status' => 'unpaid',
                                'updated_at' => now(),
                            ]);

                        self::upsertReservationLink((int) $account->id, (int) $existingReservation->id, $bookingId, $payload, 'cancelled');

                        return ['reservation_id' => (int) $existingReservation->id, 'status' => 'processed'];
                    }

                    self::upsertReservationLink((int) $account->id, 0, $bookingId, $payload, 'cancelled');
                    return ['reservation_id' => 0, 'status' => 'processed'];
                }

                if ($normalizedEventType === 'modified') {
                    if ($existingReservation) {
                        $syncMeta = self::extractSyncMetaFromReservation($existingReservation);
                        if ($syncMeta['room_key'] !== '' && $syncMeta['check_in'] !== '' && $syncMeta['check_out_inclusive'] !== '') {
                            ChannelInventoryGuard::releaseFromExternalBooking(
                                $vendorUserId,
                                $vendorPropertyId,
                                $syncMeta['room_key'],
                                $syncMeta['check_in'],
                                $syncMeta['check_out_inclusive'],
                                max(1, $syncMeta['rooms']),
                                'channel:' . strtolower(trim((string) $channelCode))
                            );
                        }
                    }

                    ChannelInventoryGuard::reserveFromExternalBooking(
                        $vendorUserId,
                        $vendorPropertyId,
                        $roomKey,
                        $checkInDate,
                        $checkOutInclusiveDate,
                        $rooms,
                        'channel:' . strtolower(trim((string) $channelCode))
                    );

                    $reservationId = self::upsertReservation(
                        $existingReservation,
                        $vendorUserId,
                        $vendorPropertyId,
                        $channelCode,
                        $bookingId,
                        $payload,
                        $roomKey,
                        $rooms,
                        $checkInDate,
                        $checkOutExclusiveDate,
                        'confirmed'
                    );

                    self::upsertReservationLink((int) $account->id, $reservationId, $bookingId, $payload, 'modified');

                    return ['reservation_id' => $reservationId, 'status' => 'processed'];
                }

                throw new RuntimeException('Unsupported event type: ' . $eventType);
            });

            DB::table('vendor_channel_events')
                ->where('id', $eventId)
                ->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('vendor_channel_accounts')
                ->where('id', (int) $account->id)
                ->update([
                    'status' => 'active',
                    'last_sync_at' => now(),
                    'last_error' => null,
                    'updated_at' => now(),
                ]);

            $fanout = ChannelInventoryFanout::queueFromInboundEvent(
                sourceAccountId: (int) $account->id,
                sourceChannelCode: $channelCode,
                normalizedPayload: $payload,
                sourceReservationId: (int) ($result['reservation_id'] ?? 0)
            );

            return [
                'ok' => true,
                'status' => 'processed',
                'message' => $fanout['error'] === null
                    ? 'Event processed successfully.'
                    : ('Event processed with fanout warning: ' . $fanout['error']),
                'event_id' => $eventId,
                'reservation_id' => (int) ($result['reservation_id'] ?? 0),
                'fanout_queued' => (int) ($fanout['queued'] ?? 0),
                'fanout_targets' => (int) ($fanout['targets'] ?? 0),
            ];
        } catch (\Throwable $exception) {
            DB::table('vendor_channel_events')
                ->where('id', $eventId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'retry_count' => DB::raw('COALESCE(retry_count,0)+1'),
                    'updated_at' => now(),
                ]);

            DB::table('vendor_channel_accounts')
                ->where('id', (int) $account->id)
                ->update([
                    'status' => 'action_required',
                    'last_error' => $exception->getMessage(),
                    'updated_at' => now(),
                ]);

            return [
                'ok' => false,
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'event_id' => $eventId,
            ];
        }
    }

    private static function upsertReservation(
        ?object $existingReservation,
        int $vendorUserId,
        int $vendorPropertyId,
        string $channelCode,
        string $bookingId,
        array $payload,
        string $roomKey,
        int $rooms,
        string $checkInDate,
        string $checkOutExclusiveDate,
        string $status
    ): int {
        $guestName = trim((string) ($payload['guest_name'] ?? $payload['customer_name'] ?? 'OTA Guest'));
        if ($guestName === '') {
            $guestName = 'OTA Guest';
        }

        $guestEmail = trim((string) ($payload['guest_email'] ?? $payload['customer_email'] ?? ''));
        if ($guestEmail === '' || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            $guestEmail = strtolower(trim($channelCode)) . '+' . preg_replace('/[^a-zA-Z0-9\-_.]/', '', $bookingId) . '@channel.workation.invalid';
        }

        $currency = strtoupper(trim((string) ($payload['currency'] ?? 'MVR')));
        if ($currency === '') {
            $currency = 'MVR';
        }

        $totalAmount = (float) ($payload['total_amount'] ?? $payload['amount'] ?? 0);
        $guestCount = max(1, (int) ($payload['guest_count'] ?? $payload['guests'] ?? 1));

        $existingNotes = [];
        if ($existingReservation && is_string($existingReservation->notes ?? null)) {
            $decoded = json_decode((string) $existingReservation->notes, true);
            if (is_array($decoded)) {
                $existingNotes = $decoded;
            }
        }

        $existingNotes['channel_sync'] = [
            'source' => strtolower(trim((string) $channelCode)),
            'external_booking_id' => $bookingId,
            'room_key' => $roomKey,
            'rooms' => $rooms,
            'check_in' => $checkInDate,
            'check_out_inclusive' => Carbon::parse($checkOutExclusiveDate)->subDay()->toDateString(),
            'last_event_at' => now()->toIso8601String(),
        ];

        $reservationPayload = [
            'vendor_user_id' => $vendorUserId,
            'vendor_property_id' => $vendorPropertyId,
            'customer_name' => $guestName,
            'customer_email' => $guestEmail,
            'start_at' => Carbon::parse($checkInDate)->startOfDay()->toDateTimeString(),
            'end_at' => Carbon::parse($checkOutExclusiveDate)->startOfDay()->toDateTimeString(),
            'guests' => $guestCount,
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'status' => $status,
            'payment_status' => strtolower(trim((string) ($payload['payment_status'] ?? 'unpaid'))),
            'notes' => json_encode($existingNotes, JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ];

        if ($existingReservation) {
            DB::table('vendor_reservations')
                ->where('id', (int) $existingReservation->id)
                ->update($reservationPayload);

            return (int) $existingReservation->id;
        }

        $reservationPayload['created_at'] = now();
        return (int) DB::table('vendor_reservations')->insertGetId($reservationPayload);
    }

    private static function upsertReservationLink(int $accountId, int $reservationId, string $bookingId, array $payload, string $externalStatus): void
    {
        if ($reservationId <= 0) {
            return;
        }

        $version = trim((string) ($payload['version'] ?? $payload['event_version'] ?? ''));

        DB::table('vendor_channel_reservation_links')->updateOrInsert(
            [
                'vendor_channel_account_id' => $accountId,
                'external_booking_id' => $bookingId,
            ],
            [
                'vendor_reservation_id' => $reservationId,
                'external_booking_version' => $version !== '' ? $version : null,
                'external_status' => trim($externalStatus) !== '' ? trim($externalStatus) : null,
                'last_synced_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private static function normalizeEventType(string $eventType): string
    {
        $type = strtolower(trim($eventType));

        if (in_array($type, ['reservation.created', 'booking.created', 'reservation.confirmed', 'booking.confirmed', 'created', 'confirmed'], true)) {
            return str_contains($type, 'confirm') ? 'confirmed' : 'created';
        }

        if (in_array($type, ['reservation.cancelled', 'reservation.canceled', 'booking.cancelled', 'booking.canceled', 'cancelled', 'canceled'], true)) {
            return 'cancelled';
        }

        if (in_array($type, ['reservation.modified', 'reservation.updated', 'booking.modified', 'booking.updated', 'modified', 'updated'], true)) {
            return 'modified';
        }

        return $type;
    }

    /**
     * @return array{0:string,1:string,2:string} [checkInDate, checkOutInclusiveDate, checkOutExclusiveDate]
     */
    private static function resolveDates(array $payload): array
    {
        $checkInRaw = trim((string) ($payload['check_in_date'] ?? $payload['checkin'] ?? $payload['start_date'] ?? ''));
        $checkOutRaw = trim((string) ($payload['check_out_date'] ?? $payload['checkout'] ?? $payload['end_date'] ?? ''));

        if ($checkInRaw === '') {
            throw new RuntimeException('Missing check-in date in payload.');
        }

        $checkIn = Carbon::parse($checkInRaw)->startOfDay();

        if ($checkOutRaw === '') {
            $checkOutExclusive = $checkIn->copy()->addDay();
            return [$checkIn->toDateString(), $checkIn->toDateString(), $checkOutExclusive->toDateString()];
        }

        $checkOut = Carbon::parse($checkOutRaw)->startOfDay();
        if ($checkOut->lte($checkIn)) {
            $checkOutExclusive = $checkIn->copy()->addDay();
            return [$checkIn->toDateString(), $checkIn->toDateString(), $checkOutExclusive->toDateString()];
        }

        $checkOutInclusive = $checkOut->copy()->subDay();
        return [$checkIn->toDateString(), $checkOutInclusive->toDateString(), $checkOut->toDateString()];
    }

    private static function extractSyncMetaFromReservation(object $reservation): array
    {
        $notes = [];
        if (is_string($reservation->notes ?? null)) {
            $decoded = json_decode((string) $reservation->notes, true);
            if (is_array($decoded)) {
                $notes = $decoded;
            }
        }

        $sync = is_array($notes['channel_sync'] ?? null) ? $notes['channel_sync'] : [];

        return [
            'room_key' => trim((string) ($sync['room_key'] ?? '')),
            'check_in' => trim((string) ($sync['check_in'] ?? '')),
            'check_out_inclusive' => trim((string) ($sync['check_out_inclusive'] ?? '')),
            'rooms' => max(1, (int) ($sync['rooms'] ?? 1)),
        ];
    }

    private static function initializeInventoryRowsIfMissing(object $account, string $roomKey, string $fromDate, string $toDateInclusive, array $payload): void
    {
        $vendorUserId = (int) ($account->vendor_user_id ?? 0);
        $vendorPropertyId = (int) ($payload['vendor_property_id'] ?? $payload['property_id'] ?? $account->vendor_property_id ?? 0);
        if ($vendorUserId <= 0 || $vendorPropertyId <= 0) {
            return;
        }

        $physicalRooms = max(0, (int) ($payload['physical_rooms'] ?? $payload['total_rooms_available'] ?? 0));
        $safetyBuffer = max(0, (int) ($payload['safety_buffer'] ?? 0));

        $mapping = DB::table('vendor_channel_room_mappings')
            ->where('vendor_channel_account_id', (int) $account->id)
            ->where('external_room_id', $roomKey)
            ->first();

        $internalRoomCategoryId = $mapping ? (int) ($mapping->internal_room_category_id ?? 0) : 0;
        $internalAccommodationRoomId = $mapping ? (int) ($mapping->internal_accommodation_room_id ?? 0) : 0;

        if ($physicalRooms <= 0 && $internalRoomCategoryId > 0 && DB::table('vendor_property_room_categories')->where('id', $internalRoomCategoryId)->exists()) {
            $row = DB::table('vendor_property_room_categories')->where('id', $internalRoomCategoryId)->first();
            if ($row) {
                $physicalRooms = max(0, (int) ($row->quantity ?? $row->units_available ?? 0));
            }
        }

        $start = Carbon::parse($fromDate)->startOfDay();
        $end = Carbon::parse($toDateInclusive)->startOfDay();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();

            $exists = DB::table('vendor_room_inventory_daily')
                ->where('vendor_user_id', $vendorUserId)
                ->where('vendor_property_id', $vendorPropertyId)
                ->where('room_key', $roomKey)
                ->whereDate('inventory_date', $date)
                ->exists();

            if (!$exists) {
                DB::table('vendor_room_inventory_daily')->insert([
                    'vendor_user_id' => $vendorUserId,
                    'vendor_property_id' => $vendorPropertyId,
                    'room_key' => $roomKey,
                    'internal_room_category_id' => $internalRoomCategoryId > 0 ? $internalRoomCategoryId : null,
                    'internal_accommodation_room_id' => $internalAccommodationRoomId > 0 ? $internalAccommodationRoomId : null,
                    'inventory_date' => $date,
                    'physical_rooms' => $physicalRooms,
                    'sold_rooms' => 0,
                    'hold_rooms' => 0,
                    'safety_buffer' => $safetyBuffer,
                    'closed_out' => false,
                    'version' => 0,
                    'last_source' => 'channel-init',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $cursor->addDay();
        }
    }

    private static function isDuplicateKeyException(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        return str_contains($message, 'duplicate') || str_contains($message, 'unique');
    }
}

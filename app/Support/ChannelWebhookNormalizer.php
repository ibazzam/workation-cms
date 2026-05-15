<?php

namespace App\Support;

use RuntimeException;

class ChannelWebhookNormalizer
{
    /**
     * Convert provider-specific payload formats into a stable internal shape.
     *
     * @return array<string,mixed>
     */
    public static function normalize(string $channelCode, array $payload): array
    {
        $channel = strtolower(trim($channelCode));
        $base = self::flatten($payload);

        if ($channel === 'booking' || $channel === 'bookingcom' || $channel === 'booking.com') {
            return self::normalizeBookingCom($payload, $base);
        }

        if ($channel === 'agoda') {
            return self::normalizeAgoda($payload, $base);
        }

        if ($channel === 'airbnb') {
            return self::normalizeAirbnb($payload, $base);
        }

        return self::normalizeGeneric($payload, $base);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    private static function normalizeBookingCom(array $payload, array $base): array
    {
        $reservation = self::arrayOrNull($payload['reservation'] ?? null) ?? [];
        $guest = self::arrayOrNull($reservation['guest'] ?? null) ?? [];

        return self::finalize($payload, [
            'event_type' => self::stringOrNull($payload['event_type'] ?? $payload['event'] ?? null) ?? self::stringOrNull($reservation['status'] ?? null) ?? ($base['event_type'] ?? 'reservation.created'),
            'event_id' => self::stringOrNull($payload['event_id'] ?? $payload['id'] ?? null) ?? self::stringOrNull($base['event_id'] ?? null),
            'booking_id' => self::stringOrNull($reservation['id'] ?? null) ?? self::stringOrNull($payload['reservation_id'] ?? null) ?? self::stringOrNull($base['booking_id'] ?? null),
            'version' => self::stringOrNull($reservation['updated_at'] ?? null) ?? self::stringOrNull($payload['version'] ?? null),
            'room_key' => self::stringOrNull($reservation['room_type_id'] ?? null) ?? self::stringOrNull($base['room_key'] ?? null),
            'rooms' => self::intOrNull($reservation['number_of_rooms'] ?? null) ?? self::intOrNull($base['rooms'] ?? null) ?? 1,
            'check_in_date' => self::stringOrNull($reservation['checkin_date'] ?? null) ?? self::stringOrNull($base['check_in_date'] ?? null),
            'check_out_date' => self::stringOrNull($reservation['checkout_date'] ?? null) ?? self::stringOrNull($base['check_out_date'] ?? null),
            'guest_name' => self::stringOrNull($guest['name'] ?? null) ?? self::stringOrNull($base['guest_name'] ?? null),
            'guest_email' => self::stringOrNull($guest['email'] ?? null) ?? self::stringOrNull($base['guest_email'] ?? null),
            'guest_count' => self::intOrNull($reservation['guest_count'] ?? null) ?? self::intOrNull($base['guest_count'] ?? null) ?? 1,
            'total_amount' => self::floatOrNull($reservation['price_total'] ?? null) ?? self::floatOrNull($base['total_amount'] ?? null) ?? 0,
            'currency' => self::stringOrNull($reservation['currency'] ?? null) ?? self::stringOrNull($base['currency'] ?? null) ?? 'MVR',
            'payment_status' => self::stringOrNull($reservation['payment_status'] ?? null) ?? self::stringOrNull($base['payment_status'] ?? null) ?? 'unpaid',
            'vendor_property_id' => self::intOrNull($payload['property_id'] ?? null) ?? self::intOrNull($base['vendor_property_id'] ?? null),
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    private static function normalizeAgoda(array $payload, array $base): array
    {
        $booking = self::arrayOrNull($payload['booking'] ?? null) ?? [];
        $guest = self::arrayOrNull($booking['guest'] ?? null) ?? [];

        return self::finalize($payload, [
            'event_type' => self::stringOrNull($payload['event_type'] ?? $payload['type'] ?? null) ?? self::stringOrNull($booking['status'] ?? null) ?? ($base['event_type'] ?? 'reservation.created'),
            'event_id' => self::stringOrNull($payload['event_id'] ?? $payload['id'] ?? null) ?? self::stringOrNull($base['event_id'] ?? null),
            'booking_id' => self::stringOrNull($booking['booking_id'] ?? null) ?? self::stringOrNull($base['booking_id'] ?? null),
            'version' => self::stringOrNull($booking['revision'] ?? null) ?? self::stringOrNull($payload['version'] ?? null),
            'room_key' => self::stringOrNull($booking['room_id'] ?? null) ?? self::stringOrNull($base['room_key'] ?? null),
            'rooms' => self::intOrNull($booking['rooms'] ?? null) ?? self::intOrNull($base['rooms'] ?? null) ?? 1,
            'check_in_date' => self::stringOrNull($booking['check_in'] ?? null) ?? self::stringOrNull($base['check_in_date'] ?? null),
            'check_out_date' => self::stringOrNull($booking['check_out'] ?? null) ?? self::stringOrNull($base['check_out_date'] ?? null),
            'guest_name' => self::stringOrNull($guest['full_name'] ?? null) ?? self::stringOrNull($base['guest_name'] ?? null),
            'guest_email' => self::stringOrNull($guest['email'] ?? null) ?? self::stringOrNull($base['guest_email'] ?? null),
            'guest_count' => self::intOrNull($booking['occupancy'] ?? null) ?? self::intOrNull($base['guest_count'] ?? null) ?? 1,
            'total_amount' => self::floatOrNull($booking['amount_total'] ?? null) ?? self::floatOrNull($base['total_amount'] ?? null) ?? 0,
            'currency' => self::stringOrNull($booking['currency'] ?? null) ?? self::stringOrNull($base['currency'] ?? null) ?? 'MVR',
            'payment_status' => self::stringOrNull($booking['payment_status'] ?? null) ?? self::stringOrNull($base['payment_status'] ?? null) ?? 'unpaid',
            'vendor_property_id' => self::intOrNull($booking['property_id'] ?? null) ?? self::intOrNull($base['vendor_property_id'] ?? null),
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    private static function normalizeAirbnb(array $payload, array $base): array
    {
        $reservation = self::arrayOrNull($payload['reservation'] ?? null) ?? [];
        $guest = self::arrayOrNull($reservation['guest'] ?? null) ?? [];

        return self::finalize($payload, [
            'event_type' => self::stringOrNull($payload['event_type'] ?? $payload['event'] ?? null) ?? self::stringOrNull($reservation['status'] ?? null) ?? ($base['event_type'] ?? 'reservation.created'),
            'event_id' => self::stringOrNull($payload['event_id'] ?? $payload['id'] ?? null) ?? self::stringOrNull($base['event_id'] ?? null),
            'booking_id' => self::stringOrNull($reservation['confirmation_code'] ?? null) ?? self::stringOrNull($base['booking_id'] ?? null),
            'version' => self::stringOrNull($reservation['modified_at'] ?? null) ?? self::stringOrNull($payload['version'] ?? null),
            'room_key' => self::stringOrNull($reservation['listing_id'] ?? null) ?? self::stringOrNull($base['room_key'] ?? null),
            'rooms' => self::intOrNull($reservation['number_of_rooms'] ?? null) ?? self::intOrNull($base['rooms'] ?? null) ?? 1,
            'check_in_date' => self::stringOrNull($reservation['check_in'] ?? null) ?? self::stringOrNull($base['check_in_date'] ?? null),
            'check_out_date' => self::stringOrNull($reservation['check_out'] ?? null) ?? self::stringOrNull($base['check_out_date'] ?? null),
            'guest_name' => self::stringOrNull($guest['name'] ?? null) ?? self::stringOrNull($base['guest_name'] ?? null),
            'guest_email' => self::stringOrNull($guest['email'] ?? null) ?? self::stringOrNull($base['guest_email'] ?? null),
            'guest_count' => self::intOrNull($reservation['guest_count'] ?? null) ?? self::intOrNull($base['guest_count'] ?? null) ?? 1,
            'total_amount' => self::floatOrNull($reservation['payout_amount'] ?? null) ?? self::floatOrNull($base['total_amount'] ?? null) ?? 0,
            'currency' => self::stringOrNull($reservation['currency'] ?? null) ?? self::stringOrNull($base['currency'] ?? null) ?? 'MVR',
            'payment_status' => self::stringOrNull($reservation['payment_status'] ?? null) ?? self::stringOrNull($base['payment_status'] ?? null) ?? 'unpaid',
            'vendor_property_id' => self::intOrNull($reservation['property_id'] ?? null) ?? self::intOrNull($base['vendor_property_id'] ?? null),
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    private static function normalizeGeneric(array $payload, array $base): array
    {
        return self::finalize($payload, [
            'event_type' => self::stringOrNull($base['event_type'] ?? null) ?? 'reservation.created',
            'event_id' => self::stringOrNull($base['event_id'] ?? null),
            'booking_id' => self::stringOrNull($base['booking_id'] ?? null),
            'version' => self::stringOrNull($base['version'] ?? null),
            'room_key' => self::stringOrNull($base['room_key'] ?? null),
            'rooms' => self::intOrNull($base['rooms'] ?? null) ?? 1,
            'check_in_date' => self::stringOrNull($base['check_in_date'] ?? null),
            'check_out_date' => self::stringOrNull($base['check_out_date'] ?? null),
            'guest_name' => self::stringOrNull($base['guest_name'] ?? null),
            'guest_email' => self::stringOrNull($base['guest_email'] ?? null),
            'guest_count' => self::intOrNull($base['guest_count'] ?? null) ?? 1,
            'total_amount' => self::floatOrNull($base['total_amount'] ?? null) ?? 0,
            'currency' => self::stringOrNull($base['currency'] ?? null) ?? 'MVR',
            'payment_status' => self::stringOrNull($base['payment_status'] ?? null) ?? 'unpaid',
            'vendor_property_id' => self::intOrNull($base['vendor_property_id'] ?? null),
            'physical_rooms' => self::intOrNull($base['physical_rooms'] ?? null),
            'safety_buffer' => self::intOrNull($base['safety_buffer'] ?? null),
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $normalized
     * @return array<string,mixed>
     */
    private static function finalize(array $payload, array $normalized): array
    {
        $normalized['event_type'] = strtolower(trim((string) ($normalized['event_type'] ?? 'reservation.created')));
        $normalized['booking_id'] = trim((string) ($normalized['booking_id'] ?? ''));
        $normalized['room_key'] = trim((string) ($normalized['room_key'] ?? ''));
        $normalized['check_in_date'] = trim((string) ($normalized['check_in_date'] ?? ''));

        if ($normalized['check_in_date'] === '') {
            throw new RuntimeException('Webhook payload missing check-in date after normalization.');
        }

        if ($normalized['booking_id'] === '') {
            throw new RuntimeException('Webhook payload missing booking identifier after normalization.');
        }

        if ($normalized['room_key'] === '') {
            throw new RuntimeException('Webhook payload missing room identifier after normalization.');
        }

        if (trim((string) ($normalized['check_out_date'] ?? '')) === '') {
            $normalized['check_out_date'] = $normalized['check_in_date'];
        }

        $normalized['rooms'] = max(1, (int) ($normalized['rooms'] ?? 1));
        $normalized['guest_count'] = max(1, (int) ($normalized['guest_count'] ?? 1));
        $normalized['total_amount'] = (float) ($normalized['total_amount'] ?? 0);
        $normalized['currency'] = strtoupper(trim((string) ($normalized['currency'] ?? 'MVR')));
        $normalized['payment_status'] = strtolower(trim((string) ($normalized['payment_status'] ?? 'unpaid')));
        $normalized['version'] = trim((string) ($normalized['version'] ?? 'v1'));

        if (trim((string) ($normalized['event_id'] ?? '')) === '') {
            $normalized['event_id'] = trim((string) ($payload['id'] ?? ''));
        }

        return array_merge($normalized, [
            '_raw' => $payload,
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private static function flatten(array $payload): array
    {
        return [
            'event_type' => $payload['event_type'] ?? $payload['type'] ?? $payload['event'] ?? null,
            'event_id' => $payload['event_id'] ?? $payload['id'] ?? null,
            'booking_id' => $payload['booking_id'] ?? $payload['reservation_id'] ?? $payload['reservation_code'] ?? $payload['id'] ?? null,
            'version' => $payload['version'] ?? $payload['event_version'] ?? $payload['updated_at'] ?? null,
            'room_key' => $payload['room_key'] ?? $payload['external_room_id'] ?? $payload['room_id'] ?? null,
            'rooms' => $payload['rooms'] ?? $payload['units'] ?? null,
            'check_in_date' => $payload['check_in_date'] ?? $payload['checkin'] ?? $payload['check_in'] ?? $payload['start_date'] ?? null,
            'check_out_date' => $payload['check_out_date'] ?? $payload['checkout'] ?? $payload['check_out'] ?? $payload['end_date'] ?? null,
            'guest_name' => $payload['guest_name'] ?? $payload['customer_name'] ?? null,
            'guest_email' => $payload['guest_email'] ?? $payload['customer_email'] ?? null,
            'guest_count' => $payload['guest_count'] ?? $payload['guests'] ?? null,
            'total_amount' => $payload['total_amount'] ?? $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
            'payment_status' => $payload['payment_status'] ?? null,
            'vendor_property_id' => $payload['vendor_property_id'] ?? $payload['property_id'] ?? null,
            'physical_rooms' => $payload['physical_rooms'] ?? $payload['total_rooms_available'] ?? null,
            'safety_buffer' => $payload['safety_buffer'] ?? null,
        ];
    }

    /**
     * @param mixed $value
     */
    private static function stringOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return $string === '' ? null : $string;
    }

    /**
     * @param mixed $value
     */
    private static function intOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param mixed $value
     */
    private static function floatOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param mixed $value
     * @return array<string,mixed>|null
     */
    private static function arrayOrNull($value): ?array
    {
        return is_array($value) ? $value : null;
    }
}

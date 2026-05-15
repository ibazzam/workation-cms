<?php

namespace App\Support;

class ChannelWebhookVerifier
{
    /**
     * Verify an inbound webhook signature.
     *
     * Supported formats:
     * 1) Stripe-like: "t=timestamp,v1=signature"
     * 2) Raw signature with timestamp header (X-Channel-Timestamp)
     */
    public static function verify(string $payload, string $signatureHeader, string $secret, ?string $timestampHeader = null, int $maxSkewSeconds = 300): bool
    {
        $secret = trim($secret);
        if ($secret === '' || trim($signatureHeader) === '') {
            return false;
        }

        $timestamp = null;
        $signature = null;

        $parts = array_filter(array_map('trim', explode(',', $signatureHeader)));
        foreach ($parts as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = array_map('trim', explode('=', $part, 2));
                $key = strtolower($key);
                if ($key === 't' && $value !== '') {
                    $timestamp = $value;
                }
                if (in_array($key, ['v1', 'sig', 'signature'], true) && $value !== '') {
                    $signature = $value;
                }
            }
        }

        if ($signature === null) {
            $signature = trim($signatureHeader);
        }

        if ($timestamp === null && trim((string) $timestampHeader) !== '') {
            $timestamp = trim((string) $timestampHeader);
        }

        if ($timestamp === null || !ctype_digit((string) $timestamp)) {
            return false;
        }

        $timestampInt = (int) $timestamp;
        $now = time();
        if (abs($now - $timestampInt) > $maxSkewSeconds) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, strtolower(trim((string) $signature)));
    }
}

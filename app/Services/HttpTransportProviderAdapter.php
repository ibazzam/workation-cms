<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class HttpTransportProviderAdapter implements TransportProviderAdapterInterface
{
    public function sendHoldCreate(array $payload): array
    {
        // Minimal stub: in real adapter we'd call remote provider HTTP API and handle responses.
        // Return a shape indicating success or error.
        return ['ok' => true, 'provider_ref' => null];
    }

    public function sendHoldConfirm(array $payload): array
    {
        return ['ok' => true];
    }

    public function sendHoldRelease(array $payload): array
    {
        return ['ok' => true];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
/**
 * Simple HTTP-based transport provider adapter.
 *
 * This reads provider endpoint configuration from environment variables using
 * the convention TRANSPORT_PROVIDER_{NAME}_URL and TRANSPORT_PROVIDER_{NAME}_KEY
 * where {NAME} is the uppercased provider identifier.
 */
class HttpTransportProviderAdapter
{
    protected function resolveProviderConfig(?string $provider): array
    {
        if (empty($provider)) {
            return [];
        }

        $key = strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', $provider));
        $url = env("TRANSPORT_PROVIDER_{$key}_URL");
        $token = env("TRANSPORT_PROVIDER_{$key}_KEY");

        return [
            'url' => $url,
            'token' => $token,
        ];
    }

    protected function callProvider(string $provider, string $path, array $payload = []): array
    {
        $cfg = $this->resolveProviderConfig($provider);

        if (empty($cfg['url'])) {
            return ['ok' => false, 'error' => 'provider not configured'];
        }

        $endpoint = rtrim($cfg['url'], '/') . '/' . ltrim($path, '/');

        try {
            // Use a short timeout and a small number of retries for transient failures.
            $request = Http::withHeaders([
                'Accept' => 'application/json',
            ]);

            if (!empty($cfg['token'])) {
                $request = $request->withToken($cfg['token']);
            }

            // Retry up to 2 times with 150ms backoff, and set a 5s timeout per attempt.
            $resp = $request->timeout(5)->retry(2, 150)->post($endpoint, $payload);

            if ($resp->successful()) {
                Log::info('Transport provider call success', [
                    'provider' => $provider,
                    'endpoint' => $endpoint,
                    'status' => $resp->status(),
                ]);

                return ['ok' => true, 'body' => $resp->json(), 'retryable' => false];
            }

            // Classify error: 5xx => retryable, 4xx => permanent
            if ($resp->serverError()) {
                $retryable = true;
            } elseif ($resp->clientError()) {
                $retryable = false;
            } else {
                $retryable = false;
            }

            Log::warning('Transport provider call returned error', [
                'provider' => $provider,
                'endpoint' => $endpoint,
                'status' => $resp->status(),
                'retryable' => $retryable,
                'body_preview' => mb_substr((string) $resp->body(), 0, 1000),
            ]);

            return ['ok' => false, 'error' => 'provider error: ' . $resp->status(), 'body' => $resp->body(), 'retryable' => $retryable];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            Log::error('Transport provider call exception', [
                'provider' => $provider,
                'endpoint' => $endpoint ?? null,
                'error' => $msg,
                'trace' => mb_substr($e->getTraceAsString(), 0, 2000),
                'retryable' => true,
            ]);

            // Treat exceptions (network/timeouts) as retryable by default
            return ['ok' => false, 'error' => $msg, 'retryable' => true];
        }
    }

    /**
     * Send hold creation request to provider.
     * Expects payload to contain provider reference if not passed separately.
     */
    public function sendHoldCreate(array $payload): array
    {
        $provider = $payload['provider'] ?? ($payload['meta']['provider'] ?? null);
        return $this->callProvider($provider, '/holds', $payload);
    }

    public function sendHoldConfirm(array $payload): array
    {
        $provider = $payload['provider'] ?? ($payload['meta']['provider'] ?? null);
        $holdId = $payload['hold_id'] ?? null;
        if (!$holdId) {
            return ['ok' => false, 'error' => 'missing hold_id'];
        }
        return $this->callProvider($provider, "/holds/{$holdId}/confirm", $payload);
    }

    public function sendHoldRelease(array $payload): array
    {
        $provider = $payload['provider'] ?? ($payload['meta']['provider'] ?? null);
        $holdId = $payload['hold_id'] ?? null;
        if (!$holdId) {
            return ['ok' => false, 'error' => 'missing hold_id'];
        }
        return $this->callProvider($provider, "/holds/{$holdId}/release", $payload);
    }
}


<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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
            $request = Http::withHeaders([
                'Accept' => 'application/json',
            ]);

            if (!empty($cfg['token'])) {
                $request = $request->withToken($cfg['token']);
            }

            $resp = $request->post($endpoint, $payload);

            if ($resp->successful()) {
                return ['ok' => true, 'body' => $resp->json()];
            }

            return ['ok' => false, 'error' => 'provider error: ' . $resp->status(), 'body' => $resp->body()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
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

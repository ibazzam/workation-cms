<?php

use App\Support\ChannelReservationIngestor;
use App\Support\ChannelWebhookNormalizer;
use App\Support\ChannelWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::post('/channel/webhooks/{channel}/{accountId}', function (Request $request, string $channel, int $accountId) {
    if (!Schema::hasTable('vendor_channel_accounts') || !Schema::hasTable('vendor_channel_events')) {
        return response()->json([
            'ok' => false,
            'message' => 'Channel manager tables are not available.',
        ], 503);
    }

    $channelCode = strtolower(trim((string) $channel));
    if ($channelCode === '') {
        return response()->json(['ok' => false, 'message' => 'Invalid channel code.'], 422);
    }

    $account = DB::table('vendor_channel_accounts')
        ->where('id', $accountId)
        ->where('channel_code', $channelCode)
        ->first();

    if (!$account) {
        return response()->json(['ok' => false, 'message' => 'Channel account not found.'], 404);
    }

    $rawBody = (string) $request->getContent();
    $signatureHeader = trim((string) ($request->header('X-Channel-Signature', '')));
    $timestampHeader = trim((string) ($request->header('X-Channel-Timestamp', '')));

    $connectionMeta = [];
    if (is_string($account->connection_meta ?? null) && trim((string) $account->connection_meta) !== '') {
        $decoded = json_decode((string) $account->connection_meta, true);
        if (is_array($decoded)) {
            $connectionMeta = $decoded;
        }
    }

    $webhookSecret = trim((string) ($connectionMeta['webhook_secret'] ?? env('CHANNEL_WEBHOOK_SHARED_SECRET', '')));

    if (!ChannelWebhookVerifier::verify($rawBody, $signatureHeader, $webhookSecret, $timestampHeader)) {
        return response()->json(['ok' => false, 'message' => 'Invalid webhook signature.'], 401);
    }

    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        return response()->json(['ok' => false, 'message' => 'Invalid JSON payload.'], 422);
    }

    try {
        $normalizedPayload = ChannelWebhookNormalizer::normalize($channelCode, $payload);
    } catch (\RuntimeException $exception) {
        return response()->json([
            'ok' => false,
            'status' => 'invalid_payload',
            'message' => $exception->getMessage(),
        ], 422);
    }

    $eventType = strtolower(trim((string) ($normalizedPayload['event_type'] ?? 'event')));
    $bookingId = trim((string) ($normalizedPayload['booking_id'] ?? ''));
    $eventVersion = trim((string) ($normalizedPayload['version'] ?? 'v1'));

    $idempotencyKey = implode(':', [
        'channel',
        $channelCode,
        (string) $accountId,
        $eventType !== '' ? $eventType : 'event',
        $bookingId !== '' ? $bookingId : 'no-booking',
        $eventVersion !== '' ? $eventVersion : 'v1',
    ]);

    $signatureHash = hash('sha256', strtolower($signatureHeader));

    $result = ChannelReservationIngestor::ingest(
        vendorChannelAccountId: $accountId,
        channelCode: $channelCode,
        payload: $normalizedPayload,
        idempotencyKey: $idempotencyKey,
        signatureHash: $signatureHash,
        httpMethod: (string) $request->method(),
        requestPath: (string) $request->path()
    );

    $statusMap = [
        'processed' => 200,
        'duplicate' => 200,
        'not_found' => 404,
        'failed' => 422,
    ];

    return response()->json($result, $statusMap[$result['status'] ?? 'processed'] ?? 200);
})->middleware('throttle:120,1');

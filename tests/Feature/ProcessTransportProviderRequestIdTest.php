<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\TransportProviderJob;

class ProcessTransportProviderRequestIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_processor_persists_and_passes_request_id_to_adapter()
    {
        // Ensure provider config so adapter will attempt HTTP call
        putenv('TRANSPORT_PROVIDER_TESTPROV_URL=http://example.test');
        putenv('TRANSPORT_PROVIDER_TESTPROV_KEY=sekret');

        $seenRequestId = null;

        Http::fake(function ($request) use (&$seenRequestId) {
            $this->assertNotEmpty($request->header('X-Request-ID')[0]);
            $seenRequestId = $request->header('X-Request-ID')[0];
            return Http::response(['id' => 'hold123'], 200);
        });

        $job = TransportProviderJob::create([
            'provider' => 'testprov',
            'action' => 'hold_create',
            'payload' => ['provider' => 'testprov'],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $this->artisan('transport:process-jobs --limit=10')->assertExitCode(0);

        $job->refresh();

        $this->assertEquals('completed', $job->status);
        $this->assertArrayHasKey('request_id', $job->payload);
        $this->assertEquals($job->payload['request_id'], $seenRequestId);
    }
}

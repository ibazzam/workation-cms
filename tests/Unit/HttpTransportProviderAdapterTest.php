<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\HttpTransportProviderAdapter;

class HttpTransportProviderAdapterTest extends TestCase
{
    public function test_send_hold_create_calls_provider_and_returns_body()
    {
        // Configure provider env for this test run
        putenv('TRANSPORT_PROVIDER_TESTPROV_URL=http://example.test');
        putenv('TRANSPORT_PROVIDER_TESTPROV_KEY=sekret');

        Http::fake(function ($request) {
            $this->assertStringContainsString('/holds', $request->url());
            $this->assertEquals('application/json', $request->header('Accept')[0]);

            $auth = $request->header('Authorization')[0] ?? $request->header('authorization')[0] ?? null;
            $this->assertNotNull($auth);
            $this->assertStringContainsString('sekret', $auth);

            return Http::response(['id' => 'hold123'], 200);
        });

        $adapter = new HttpTransportProviderAdapter();
        $res = $adapter->sendHoldCreate(['provider' => 'testprov', 'foo' => 'bar']);

        $this->assertTrue($res['ok']);
        $this->assertEquals(['id' => 'hold123'], $res['body']);
    }
}

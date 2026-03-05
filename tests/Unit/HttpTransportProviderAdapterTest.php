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

            $this->assertNotEmpty($request->header('X-Request-ID')[0]);

            return Http::response(['id' => 'hold123'], 200);
        });

        $adapter = new HttpTransportProviderAdapter();
        $res = $adapter->sendHoldCreate(['provider' => 'testprov', 'foo' => 'bar']);

        $this->assertTrue($res['ok']);
        $this->assertEquals(['id' => 'hold123'], $res['body']);
        $this->assertArrayHasKey('request_id', $res);
        $this->assertIsString($res['request_id']);
    }

    public function test_send_hold_confirm_calls_provider_and_returns_body()
    {
        putenv('TRANSPORT_PROVIDER_TESTPROV_URL=http://example.test');
        putenv('TRANSPORT_PROVIDER_TESTPROV_KEY=sekret');


        Http::fake(function ($request) {
            $this->assertStringContainsString('/holds/hold123/confirm', $request->url());
            $this->assertNotEmpty($request->header('X-Request-ID')[0]);
            return Http::response(['confirmed' => true], 200);
        });

        $adapter = new HttpTransportProviderAdapter();
        $res = $adapter->sendHoldConfirm(['provider' => 'testprov', 'hold_id' => 'hold123']);

        $this->assertTrue($res['ok']);
        $this->assertEquals(['confirmed' => true], $res['body']);
    }

    public function test_send_hold_release_calls_provider_and_returns_body()
    {
        putenv('TRANSPORT_PROVIDER_TESTPROV_URL=http://example.test');
        putenv('TRANSPORT_PROVIDER_TESTPROV_KEY=sekret');


        Http::fake(function ($request) {
            $this->assertStringContainsString('/holds/hold123/release', $request->url());
            $this->assertNotEmpty($request->header('X-Request-ID')[0]);
            return Http::response(['released' => true], 200);
        });

        $adapter = new HttpTransportProviderAdapter();
        $res = $adapter->sendHoldRelease(['provider' => 'testprov', 'hold_id' => 'hold123']);

        $this->assertTrue($res['ok']);
        $this->assertEquals(['released' => true], $res['body']);
    }

    public function test_missing_hold_id_returns_error_for_confirm_and_release()
    {
        $adapter = new HttpTransportProviderAdapter();

        $res1 = $adapter->sendHoldConfirm(['provider' => 'testprov']);
        $this->assertFalse($res1['ok']);
        $this->assertStringContainsString('missing hold_id', $res1['error']);

        $res2 = $adapter->sendHoldRelease(['provider' => 'testprov']);
        $this->assertFalse($res2['ok']);
        $this->assertStringContainsString('missing hold_id', $res2['error']);
    }
}

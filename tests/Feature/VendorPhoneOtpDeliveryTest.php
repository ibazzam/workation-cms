<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VendorPhoneOtpDeliveryTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string|false> */
    private array $twilioEnvSnapshot = [];

    protected function tearDown(): void
    {
        $this->restoreTwilioEnv();
        parent::tearDown();
    }

    public function test_whatsapp_phone_channel_reports_guidance_when_whatsapp_from_missing(): void
    {
        $this->setTwilioEnv([
            'TWILIO_ACCOUNT_SID' => 'test-sid',
            'TWILIO_AUTH_TOKEN' => 'test-token',
            'TWILIO_PHONE_CHANNEL' => 'whatsapp',
            'TWILIO_WHATSAPP_FROM' => '',
            'TWILIO_FROM_NUMBER' => '+15005550006',
            'TWILIO_WHATSAPP_CONTENT_SID' => '',
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/email-otp/send', [
                'identifier' => '+9607770001',
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('registration')
            ->assertSessionHas('otp_delivery_guidance', 'WhatsApp delivery failed. Confirm sandbox join from your phone, TWILIO_WHATSAPP_FROM, and template ContentSid settings.');
    }

    public function test_whatsapp_delivery_uses_body_fallback_when_template_send_fails(): void
    {
        $this->setTwilioEnv([
            'TWILIO_ACCOUNT_SID' => 'test-sid',
            'TWILIO_AUTH_TOKEN' => 'test-token',
            'TWILIO_PHONE_CHANNEL' => 'whatsapp',
            'TWILIO_WHATSAPP_FROM' => 'whatsapp:+14155238886',
            'TWILIO_FROM_NUMBER' => '+15005550006',
            'TWILIO_WHATSAPP_CONTENT_SID' => 'HX_TEMPLATE',
        ]);

        Http::fake([
            'https://api.twilio.com/*' => Http::sequence()
                ->push(['message' => 'Template rejected', 'code' => 63016], 400)
                ->push(['sid' => 'SM_FALLBACK_OK'], 201),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/email-otp/send', [
                'identifier' => '+9607770002',
            ]);

        $response
            ->assertStatus(302)
            ->assertRedirect('/portal/vendor/register?mode=otp')
            ->assertSessionHas('otp_sent', true)
            ->assertSessionHas('otp_channel', 'phone');

        Http::assertSentCount(2);
    }

    public function test_auto_channel_falls_back_to_sms_when_whatsapp_delivery_fails(): void
    {
        $this->setTwilioEnv([
            'TWILIO_ACCOUNT_SID' => 'test-sid',
            'TWILIO_AUTH_TOKEN' => 'test-token',
            'TWILIO_PHONE_CHANNEL' => 'auto',
            'TWILIO_WHATSAPP_FROM' => 'whatsapp:+14155238886',
            'TWILIO_FROM_NUMBER' => '+15005550006',
            'TWILIO_WHATSAPP_CONTENT_SID' => '',
        ]);

        Http::fake(function (Request $request) {
            $to = (string) ($request['To'] ?? '');
            if (str_starts_with($to, 'whatsapp:')) {
                return Http::response(['message' => 'WhatsApp blocked', 'code' => 63003], 500);
            }

            return Http::response(['sid' => 'SM_SMS_OK'], 201);
        });

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/email-otp/send', [
                'identifier' => '+9607770003',
            ]);

        $response
            ->assertStatus(302)
            ->assertRedirect('/portal/vendor/register?mode=otp')
            ->assertSessionHas('otp_sent', true)
            ->assertSessionHas('otp_channel', 'phone');

        Http::assertSent(function (Request $request) {
            return (string) ($request['To'] ?? '') === '+9607770003';
        });
    }

    /**
     * @param array<string, string> $vars
     */
    private function setTwilioEnv(array $vars): void
    {
        $keys = [
            'TWILIO_ACCOUNT_SID',
            'TWILIO_AUTH_TOKEN',
            'TWILIO_PHONE_CHANNEL',
            'TWILIO_WHATSAPP_FROM',
            'TWILIO_FROM_NUMBER',
            'TWILIO_WHATSAPP_CONTENT_SID',
        ];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->twilioEnvSnapshot)) {
                $current = getenv($key);
                $this->twilioEnvSnapshot[$key] = $current === false ? false : (string) $current;
            }
        }

        foreach ($vars as $key => $value) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function restoreTwilioEnv(): void
    {
        foreach ($this->twilioEnvSnapshot as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $this->twilioEnvSnapshot = [];
    }
}

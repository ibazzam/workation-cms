<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VendorEmailOtpAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_login_route_redirects_to_unified_vendor_auth_page(): void
    {
        $this->get('/portal/vendor/login')
            ->assertStatus(302)
            ->assertRedirect('/portal/vendor/register?mode=email');
    }

    public function test_existing_vendor_can_login_with_email_otp(): void
    {
        Mail::fake();

        $vendor = User::factory()->create([
            'email' => 'existing.vendor@example.com',
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
        ]);

        $sendResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/email-otp/send', [
                'email' => $vendor->email,
            ]);

        $sendResponse
            ->assertStatus(302)
            ->assertRedirect('/portal/vendor/register?mode=otp')
            ->assertSessionHas('otp_email', strtolower($vendor->email))
            ->assertSessionHas('otp_sent', true)
            ->assertSessionHas('otp_test_code');

        $otpCode = (string) $sendResponse->getSession()->get('otp_test_code');

        $verifyResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/email-otp/verify', [
                'email' => $vendor->email,
                'otp' => $otpCode,
            ]);

        $verifyResponse
            ->assertStatus(302)
            ->assertRedirect('/vendor')
            ->assertSessionHas('portal_vendor_authenticated', true)
            ->assertSessionHas('portal_vendor_user_id', $vendor->id);
    }

    public function test_first_time_vendor_registers_and_logs_in_with_email_otp(): void
    {
        Mail::fake();

        $email = 'new.vendor@example.com';

        $sendResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/email-otp/send', [
                'email' => $email,
            ]);

        $sendResponse
            ->assertStatus(302)
            ->assertRedirect('/portal/vendor/register?mode=otp')
            ->assertSessionHas('otp_test_code');

        $otpCode = (string) $sendResponse->getSession()->get('otp_test_code');

        $verifyResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/email-otp/verify', [
                'email' => $email,
                'otp' => $otpCode,
            ]);

        $verifyResponse
            ->assertStatus(302)
            ->assertRedirect('/portal/vendor/register?mode=minimal')
            ->assertSessionHas('vendor_minimal_signup_payload');

        $registerResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/minimal-register', [
                'given_name' => 'Worldwide',
                'family_name' => 'Xpress LLC',
                'contact_phone' => '+9607000999',
                'agree_terms' => '1',
            ]);

        $registerResponse
            ->assertStatus(302)
            ->assertRedirect('/vendor')
            ->assertSessionHas('portal_vendor_authenticated', true);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => 'Worldwide Xpress LLC',
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
        ]);
    }
}

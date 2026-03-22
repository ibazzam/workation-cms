<?php

namespace Tests\Feature;

use Tests\TestCase;

class VendorSocialAuthUxAndPreflightTest extends TestCase
{
    public function test_vendor_register_page_shows_social_status_and_buttons(): void
    {
        $response = $this->get('/portal/vendor/register');

        $response
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee('Continue with Facebook')
            ->assertSee('Continue with Apple')
            ->assertSee('Social Login Status')
            ->assertSee('If a social login fails, retry once.');
    }

    public function test_vendor_register_page_includes_accessibility_basics(): void
    {
        $emailMode = $this->get('/portal/vendor/register?mode=email');

        $emailMode
            ->assertOk()
            ->assertSee('role="status"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('label for="otp_identifier"', false)
            ->assertSee(':focus-visible', false);

        $otpMode = $this->withSession([
            'otp_email' => 'vendor@example.com',
        ])->get('/portal/vendor/register?mode=otp');

        $otpMode
            ->assertOk()
            ->assertSee('label for="otp_code"', false)
            ->assertSee('autocomplete="one-time-code"', false);
    }

    public function test_social_redirect_preflight_allows_mismatched_redirect_host(): void
    {
        config([
            'app.url' => 'https://www.workation.mv',
            'services.facebook.client_id' => 'facebook-client-id',
            'services.facebook.client_secret' => 'facebook-client-secret',
            'services.facebook.redirect' => 'https://workation.mv/portal/vendor/oauth/facebook/callback',
        ]);

        $response = $this->get('/portal/vendor/oauth/facebook/redirect');

        $response
            ->assertStatus(302)
            ->assertRedirectContains('facebook.com');
    }

    public function test_social_redirect_preflight_blocks_non_https_redirect(): void
    {
        config([
            'app.url' => 'https://www.workation.mv',
            'services.facebook.client_id' => 'facebook-client-id',
            'services.facebook.client_secret' => 'facebook-client-secret',
            'services.facebook.redirect' => 'http://www.workation.mv/portal/vendor/oauth/facebook/callback',
        ]);

        $response = $this->get('/portal/vendor/oauth/facebook/redirect');

        $response
            ->assertStatus(302)
            ->assertRedirect('/portal/vendor/register')
            ->assertSessionHasErrors('registration')
            ->assertSessionHas('oauth_retry_guidance');
    }

    public function test_facebook_callback_provider_error_redirects_with_guidance(): void
    {
        config([
            'services.facebook.client_id' => 'facebook-client-id',
            'services.facebook.client_secret' => 'facebook-client-secret',
            'services.facebook.redirect' => 'https://www.workation.mv/portal/vendor/oauth/facebook/callback',
        ]);

        $response = $this->get('/portal/vendor/oauth/facebook/callback?error=access_denied&error_reason=user_denied');

        $response
            ->assertStatus(302)
            ->assertRedirect('/portal/vendor/register')
            ->assertSessionHasErrors('registration')
            ->assertSessionHas('oauth_retry_guidance');
    }

    public function test_facebook_redirect_uses_public_profile_scope_only(): void
    {
        config([
            'services.facebook.client_id' => 'facebook-client-id',
            'services.facebook.client_secret' => 'facebook-client-secret',
            'services.facebook.redirect' => 'https://www.workation.mv/portal/vendor/oauth/facebook/callback',
        ]);

        $response = $this->get('/portal/vendor/oauth/facebook/redirect');

        $response->assertStatus(302)->assertRedirectContains('facebook.com');

        $location = urldecode((string) $response->headers->get('Location', ''));
        $this->assertStringContainsString('scope=public_profile', $location);
        $this->assertStringNotContainsString('scope=public_profile,email', $location);
        $this->assertStringNotContainsString('scope=email', $location);
    }
}

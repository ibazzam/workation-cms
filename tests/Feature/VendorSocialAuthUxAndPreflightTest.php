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
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class VendorSocialHealthEndpointTest extends TestCase
{
    public function test_oauth_health_endpoint_returns_provider_statuses(): void
    {
        config([
            'app.url' => 'https://www.workation.mv',
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'https://www.workation.mv/portal/vendor/oauth/google/callback',
            'services.facebook.client_id' => 'facebook-client-id',
            'services.facebook.client_secret' => 'facebook-client-secret',
            'services.facebook.redirect' => 'https://www.workation.mv/portal/vendor/oauth/facebook/callback',
            'services.apple.client_id' => '',
            'services.apple.team_id' => '',
            'services.apple.key_id' => '',
            'services.apple.private_key' => '',
            'services.apple.redirect' => 'https://workation.mv/portal/vendor/oauth/apple/callback',
        ]);

        $response = $this->getJson('/portal/vendor/oauth/health');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('app_url', 'https://www.workation.mv')
            ->assertJsonPath('providers.google.configured', true)
            ->assertJsonPath('providers.google.redirect_uses_https', true)
            ->assertJsonPath('providers.google.redirect_host_matches_app', true)
            ->assertJsonPath('providers.facebook.configured', true)
            ->assertJsonPath('providers.facebook.redirect_host_matches_app', true)
            ->assertJsonPath('providers.apple.configured', false)
            ->assertJsonPath('providers.apple.redirect_host_matches_app', false);
    }
}

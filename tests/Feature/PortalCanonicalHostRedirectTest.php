<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalCanonicalHostRedirectTest extends TestCase
{
    public function test_vendor_register_redirects_to_canonical_host_in_production(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'https://www.workation.mv',
        ]);

        $response = $this->get('/portal/vendor/register', [
            'HTTP_HOST' => 'workation.mv',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('https://www.workation.mv/portal/vendor/register');
    }

    public function test_oauth_callback_redirect_preserves_query_when_host_is_not_canonical(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'https://www.workation.mv',
        ]);

        $response = $this->get('/portal/vendor/oauth/facebook/callback?code=abc123&state=xyz789', [
            'HTTP_HOST' => 'workation.mv',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('https://www.workation.mv/portal/vendor/oauth/facebook/callback?code=abc123&state=xyz789');
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class PortalLogoutRedirectTest extends TestCase
{
    public function test_admin_logout_redirects_to_admin_login(): void
    {
        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => 'Admin User',
                'portal_admin_user_id' => 1,
                'portal_admin_role' => 'ADMIN',
            ])
            ->post('/portal/admin/logout');

        $response
            ->assertStatus(302)
            ->assertRedirect('/portal/admin/login');
    }

    public function test_vendor_logout_redirects_to_vendor_register_email_mode(): void
    {
        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_vendor_authenticated' => true,
                'portal_vendor_user' => 'Vendor User',
                'portal_vendor_user_id' => 1,
                'portal_vendor_role' => 'VENDOR',
            ])
            ->post('/portal/vendor/logout');

        $response
            ->assertStatus(302)
            ->assertRedirect('/portal/vendor/register?mode=email');
    }
}

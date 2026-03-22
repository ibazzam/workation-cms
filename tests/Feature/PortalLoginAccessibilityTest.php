<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalLoginAccessibilityTest extends TestCase
{
    public function test_admin_login_page_includes_accessibility_basics(): void
    {
        $response = $this->get('/portal/admin/login');

        $response
            ->assertOk()
            ->assertSee('label for="username"', false)
            ->assertSee('label for="password"', false)
            ->assertSee('aria-describedby="portal-login-hint"', false)
            ->assertSee('input:focus-visible', false)
            ->assertSee('button:focus-visible', false)
            ->assertSee('a:focus-visible', false);
    }
}

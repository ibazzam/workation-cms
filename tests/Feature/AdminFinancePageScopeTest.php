<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinancePageScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_finance_is_limited_to_finance_and_permissions_pages(): void
    {
        $adminFinance = User::factory()->create([
            'portal_role' => 'ADMIN_FINANCE',
        ]);

        $this->actingAs($adminFinance)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_role' => 'ADMIN_FINANCE',
                'portal_admin_user' => (string) $adminFinance->name,
                'portal_admin_user_id' => (int) $adminFinance->id,
            ])
            ->get('/admin?page=media')
            ->assertOk()
            ->assertViewHas('adminPage', 'finance')
            ->assertViewHas('adminAllowedPages', static function (array $pages): bool {
                sort($pages);
                return $pages === ['finance', 'permissions'];
            });
    }

    public function test_admin_super_is_limited_to_finance_and_permissions_pages(): void
    {
        $adminSuper = User::factory()->create([
            'portal_role' => 'ADMIN_SUPER',
        ]);

        $this->actingAs($adminSuper)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_role' => 'ADMIN_SUPER',
                'portal_admin_user' => (string) $adminSuper->name,
                'portal_admin_user_id' => (int) $adminSuper->id,
            ])
            ->get('/admin?page=overview')
            ->assertOk()
            ->assertViewHas('adminPage', 'finance')
            ->assertViewHas('adminAllowedPages', static function (array $pages): bool {
                sort($pages);
                return $pages === ['finance', 'permissions'];
            });
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerEmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::dropIfExists('User');

        Schema::create('User', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('google_oauth_id')->nullable();
            $table->string('facebook_oauth_id')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });
    }

    public function test_customer_registration_issues_verification_token_and_creates_customer(): void
    {
        $registerResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/customer/register', [
                'name' => 'New Customer',
                'email' => 'new.customer@example.com',
                'password' => 'Secret1234',
                'password_confirmation' => 'Secret1234',
            ]);

        $registerResponse
            ->assertStatus(302)
            ->assertRedirect('/portal/customer/login')
            ->assertSessionHas('customer_verification_test_token')
            ->assertSessionHas('customer_verification_test_email', 'new.customer@example.com');

        $row = DB::table('User')->where('email', 'new.customer@example.com')->first();
        $this->assertNotNull($row);
        $this->assertTrue(Hash::check('Secret1234', (string) $row->password));
    }

    public function test_customer_can_verify_email_and_resend_reports_already_verified(): void
    {
        $registerResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/customer/register', [
                'name' => 'Verified Customer',
                'email' => 'verified.customer@example.com',
                'password' => 'Secret1234',
                'password_confirmation' => 'Secret1234',
            ]);

        $token = (string) $registerResponse->getSession()->get('customer_verification_test_token');
        $email = (string) $registerResponse->getSession()->get('customer_verification_test_email');

        $verifyResponse = $this->get('/portal/customer/verify-email?email=' . rawurlencode($email) . '&token=' . rawurlencode($token));

        $verifyResponse
            ->assertStatus(302)
            ->assertRedirect('/portal/customer/login')
            ->assertSessionHas('status');

        $resendResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->from('/portal/customer/login')
            ->post('/portal/customer/verify-email/resend', [
                'email' => $email,
            ]);

        $resendResponse
            ->assertStatus(302)
            ->assertRedirect('/portal/customer/login')
            ->assertSessionHas('status', 'This customer email is already verified. You can sign in now.');
    }
}

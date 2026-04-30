<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorBillingPayoutAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_vendor_can_update_multiple_payout_accounts(): void
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-2201',
        ]);

        $this->withSession([
            'portal_vendor_authenticated' => true,
            'portal_vendor_user' => $vendor->name,
            'portal_vendor_user_id' => $vendor->id,
            'portal_vendor_role' => 'VENDOR',
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/billing/update', [
                'business_name' => 'Workation Villas Pvt Ltd',
                'responsible_person_name' => 'Aishath Mariyam',
                'billing_emails' => "finance@vendor.example.com\npayments@vendor.example.com",
                'contact_number' => '+9607771234',
                'billing_street_name' => 'Orchid Magu',
                'billing_country' => 'Maldives',
                'billing_state' => 'Kaafu',
                'billing_city' => 'Male',
                'billing_address' => 'Orchid Magu, Male, Kaafu, Maldives',
                'invoice_prefix' => 'WKV',
                'primary_payout_account' => 1,
                'payout_accounts' => [
                    [
                        'account_label' => 'MVR Operations',
                        'payout_method' => 'bank_transfer',
                        'beneficiary_name' => 'Workation Villas Pvt Ltd',
                        'bank_account_number' => '1234567890',
                        'bank_name' => 'Bank of Maldives',
                        'swift_code' => 'MALAADMV',
                        'currency' => 'MVR',
                    ],
                    [
                        'account_label' => 'USD Reserve',
                        'payout_method' => 'bank_transfer',
                        'beneficiary_name' => 'Workation Villas USD',
                        'bank_account_number' => '9988776655',
                        'bank_name' => 'MIB',
                        'swift_code' => 'MIBAADMV',
                        'currency' => 'USD',
                    ],
                ],
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Billing details updated.');

        $billingRow = DB::table('vendor_billing_details')->where('vendor_user_id', $vendor->id)->first();
        $this->assertNotNull($billingRow);
        $this->assertSame('Workation Villas Pvt Ltd', (string) $billingRow->business_name);
        $this->assertSame('Aishath Mariyam', (string) $billingRow->responsible_person_name);
        $this->assertSame('+9607771234', (string) $billingRow->contact_number);
        $this->assertSame('finance@vendor.example.com', (string) $billingRow->billing_email);
        $this->assertSame('Workation Villas USD', (string) $billingRow->beneficiary_name);
        $this->assertSame('MIB', (string) $billingRow->bank_name);
        $this->assertSame('6655', (string) $billingRow->bank_account_last4);
        $this->assertSame('USD', (string) $billingRow->currency);

        $decodedEmails = json_decode((string) ($billingRow->billing_emails_json ?? '[]'), true);
        $this->assertSame([
            'finance@vendor.example.com',
            'payments@vendor.example.com',
        ], $decodedEmails);

        $accounts = DB::table('vendor_payout_accounts')
            ->where('vendor_user_id', $vendor->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $accounts);
        $this->assertSame('MVR Operations', (string) $accounts[0]->account_label);
        $this->assertFalse((bool) $accounts[0]->is_primary);
        $this->assertSame('USD Reserve', (string) $accounts[1]->account_label);
        $this->assertTrue((bool) $accounts[1]->is_primary);
    }
}
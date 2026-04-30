<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_billing_details')) {
            Schema::table('vendor_billing_details', function (Blueprint $table): void {
                if (!Schema::hasColumn('vendor_billing_details', 'responsible_person_name')) {
                    $table->string('responsible_person_name', 190)->nullable()->after('business_name');
                }
                if (!Schema::hasColumn('vendor_billing_details', 'billing_emails_json')) {
                    $table->text('billing_emails_json')->nullable()->after('billing_email');
                }
                if (!Schema::hasColumn('vendor_billing_details', 'contact_number')) {
                    $table->string('contact_number', 40)->nullable()->after('billing_emails_json');
                }
            });
        }

        if (!Schema::hasTable('vendor_payout_accounts')) {
            Schema::create('vendor_payout_accounts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('account_label', 80)->nullable();
                $table->string('payout_method', 40)->default('bank_transfer');
                $table->string('beneficiary_name', 190);
                $table->string('bank_account_number', 60);
                $table->string('bank_account_last4', 8)->nullable();
                $table->string('bank_name', 190);
                $table->string('swift_code', 20)->nullable();
                $table->string('currency', 8)->default('MVR');
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->index(['vendor_user_id', 'is_primary']);
            });
        }

        if (Schema::hasTable('vendor_billing_details')) {
            $billingRows = DB::table('vendor_billing_details')->get();

            foreach ($billingRows as $billingRow) {
                $billingEmails = trim((string) ($billingRow->billing_email ?? ''));
                if ($billingEmails !== '' && Schema::hasColumn('vendor_billing_details', 'billing_emails_json')) {
                    DB::table('vendor_billing_details')
                        ->where('id', $billingRow->id)
                        ->whereNull('billing_emails_json')
                        ->update(['billing_emails_json' => json_encode([$billingEmails])]);
                }

                if (!Schema::hasTable('vendor_payout_accounts')) {
                    continue;
                }

                $alreadyExists = DB::table('vendor_payout_accounts')
                    ->where('vendor_user_id', (int) ($billingRow->vendor_user_id ?? 0))
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                $beneficiaryName = trim((string) ($billingRow->beneficiary_name ?? ''));
                $accountNumber = trim((string) ($billingRow->bank_account_number ?? ''));
                $bankName = trim((string) ($billingRow->bank_name ?? ''));

                if ($beneficiaryName === '' && $accountNumber === '' && $bankName === '') {
                    continue;
                }

                DB::table('vendor_payout_accounts')->insert([
                    'vendor_user_id' => (int) ($billingRow->vendor_user_id ?? 0),
                    'account_label' => 'Primary account',
                    'payout_method' => (string) ($billingRow->payout_method ?? 'bank_transfer'),
                    'beneficiary_name' => $beneficiaryName !== '' ? $beneficiaryName : (string) ($billingRow->business_name ?? 'Vendor Account'),
                    'bank_account_number' => $accountNumber,
                    'bank_account_last4' => trim((string) ($billingRow->bank_account_last4 ?? '')) !== ''
                        ? (string) $billingRow->bank_account_last4
                        : ($accountNumber !== '' ? substr($accountNumber, -4) : null),
                    'bank_name' => $bankName,
                    'swift_code' => strtoupper(trim((string) ($billingRow->swift_code ?? ''))),
                    'currency' => strtoupper(trim((string) ($billingRow->currency ?? 'MVR'))),
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payout_accounts');

        if (!Schema::hasTable('vendor_billing_details')) {
            return;
        }

        Schema::table('vendor_billing_details', function (Blueprint $table): void {
            foreach (['contact_number', 'billing_emails_json', 'responsible_person_name'] as $column) {
                if (Schema::hasColumn('vendor_billing_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
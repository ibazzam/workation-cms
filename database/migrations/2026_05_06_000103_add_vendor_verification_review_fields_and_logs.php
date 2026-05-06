<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'vendor_verification_rejection_reason')) {
                $table->text('vendor_verification_rejection_reason')
                    ->nullable()
                    ->after('vendor_verification_notes');
            }

            if (!Schema::hasColumn('users', 'vendor_verification_missing_documents')) {
                $table->text('vendor_verification_missing_documents')
                    ->nullable()
                    ->after('vendor_verification_rejection_reason');
            }

            if (!Schema::hasColumn('users', 'vendor_verification_last_reviewed_at')) {
                $table->timestamp('vendor_verification_last_reviewed_at')
                    ->nullable()
                    ->after('vendor_verification_missing_documents');
            }

            if (!Schema::hasColumn('users', 'vendor_verification_last_reviewed_by_user_id')) {
                $table->unsignedBigInteger('vendor_verification_last_reviewed_by_user_id')
                    ->nullable()
                    ->after('vendor_verification_last_reviewed_at');
            }
        });

        if (!Schema::hasTable('portal_vendor_verification_reviews')) {
            Schema::create('portal_vendor_verification_reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_user_id');
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
                $table->string('reviewer_role', 32)->nullable();
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32);
                $table->boolean('crosscheck_business_profile')->default(false);
                $table->boolean('crosscheck_service_profile')->default(false);
                $table->boolean('crosscheck_id_proof')->default(false);
                $table->boolean('sole_proprietor_name_override')->default(false);
                $table->text('missing_documents')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamp('vendor_notified_at')->nullable();
                $table->timestamps();

                $table->index(['vendor_user_id', 'created_at'], 'portal_vendor_verification_reviews_vendor_created_idx');
                $table->index(['to_status', 'created_at'], 'portal_vendor_verification_reviews_status_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('portal_vendor_verification_reviews')) {
            Schema::drop('portal_vendor_verification_reviews');
        }

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'vendor_verification_last_reviewed_by_user_id',
                'vendor_verification_last_reviewed_at',
                'vendor_verification_missing_documents',
                'vendor_verification_rejection_reason',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

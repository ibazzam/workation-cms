<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'vendor_verification_status')) {
                $table->string('vendor_verification_status', 32)
                    ->default('pending')
                    ->after('portal_vendor_id');
            }

            if (!Schema::hasColumn('users', 'vendor_verification_notes')) {
                $table->text('vendor_verification_notes')
                    ->nullable()
                    ->after('vendor_verification_status');
            }

            if (!Schema::hasColumn('users', 'vendor_verified_at')) {
                $table->timestamp('vendor_verified_at')
                    ->nullable()
                    ->after('vendor_verification_notes');
            }

            if (!Schema::hasColumn('users', 'vendor_verified_by_user_id')) {
                $table->unsignedBigInteger('vendor_verified_by_user_id')
                    ->nullable()
                    ->after('vendor_verified_at');
            }

            if (!Schema::hasColumn('users', 'vendor_approved_service_categories')) {
                $table->text('vendor_approved_service_categories')
                    ->nullable()
                    ->after('vendor_verified_by_user_id');
            }

            if (!Schema::hasColumn('users', 'vendor_company_name')) {
                $table->string('vendor_company_name', 190)
                    ->nullable()
                    ->after('vendor_approved_service_categories');
            }

            if (!Schema::hasColumn('users', 'vendor_business_registration_number')) {
                $table->string('vendor_business_registration_number', 120)
                    ->nullable()
                    ->after('vendor_company_name');
            }

            if (!Schema::hasColumn('users', 'vendor_business_license_number')) {
                $table->string('vendor_business_license_number', 120)
                    ->nullable()
                    ->after('vendor_business_registration_number');
            }

            if (!Schema::hasColumn('users', 'vendor_legal_documents_submitted_at')) {
                $table->timestamp('vendor_legal_documents_submitted_at')
                    ->nullable()
                    ->after('vendor_business_license_number');
            }

            if (!Schema::hasColumn('users', 'vendor_contact_person_name')) {
                $table->string('vendor_contact_person_name', 190)
                    ->nullable()
                    ->after('vendor_legal_documents_submitted_at');
            }

            if (!Schema::hasColumn('users', 'vendor_contact_person_phone')) {
                $table->string('vendor_contact_person_phone', 60)
                    ->nullable()
                    ->after('vendor_contact_person_name');
            }

            if (!Schema::hasColumn('users', 'vendor_contact_person_email')) {
                $table->string('vendor_contact_person_email', 190)
                    ->nullable()
                    ->after('vendor_contact_person_phone');
            }

            if (!Schema::hasColumn('users', 'vendor_contact_person_id_number')) {
                $table->string('vendor_contact_person_id_number', 120)
                    ->nullable()
                    ->after('vendor_contact_person_email');
            }

            if (!Schema::hasColumn('users', 'vendor_contact_verified_at')) {
                $table->timestamp('vendor_contact_verified_at')
                    ->nullable()
                    ->after('vendor_contact_person_id_number');
            }

            if (!Schema::hasColumn('users', 'vendor_contact_verified_by_user_id')) {
                $table->unsignedBigInteger('vendor_contact_verified_by_user_id')
                    ->nullable()
                    ->after('vendor_contact_verified_at');
            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'vendor_contact_verified_by_user_id',
                'vendor_contact_verified_at',
                'vendor_contact_person_id_number',
                'vendor_contact_person_email',
                'vendor_contact_person_phone',
                'vendor_contact_person_name',
                'vendor_legal_documents_submitted_at',
                'vendor_business_license_number',
                'vendor_business_registration_number',
                'vendor_company_name',
                'vendor_approved_service_categories',
                'vendor_verified_by_user_id',
                'vendor_verified_at',
                'vendor_verification_notes',
                'vendor_verification_status',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

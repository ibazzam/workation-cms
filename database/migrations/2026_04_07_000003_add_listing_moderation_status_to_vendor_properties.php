<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_properties')) {
            return;
        }

        Schema::table('vendor_properties', static function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_properties', 'listing_moderation_status')) {
                $table->string('listing_moderation_status', 40)->default('draft')->after('listing_category');
            }
            if (!Schema::hasColumn('vendor_properties', 'listing_admin_notes')) {
                $table->text('listing_admin_notes')->nullable()->after('listing_moderation_status');
            }
            if (!Schema::hasColumn('vendor_properties', 'listing_approved_at')) {
                $table->timestamp('listing_approved_at')->nullable()->after('listing_admin_notes');
            }
            if (!Schema::hasColumn('vendor_properties', 'listing_approved_by_user_id')) {
                $table->unsignedBigInteger('listing_approved_by_user_id')->nullable()->after('listing_approved_at');
            }
            if (!Schema::hasColumn('vendor_properties', 'listing_submitted_for_review_at')) {
                $table->timestamp('listing_submitted_for_review_at')->nullable()->after('listing_approved_by_user_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_properties')) {
            return;
        }

        Schema::table('vendor_properties', static function (Blueprint $table): void {
            foreach ([
                'listing_moderation_status',
                'listing_admin_notes',
                'listing_approved_at',
                'listing_approved_by_user_id',
                'listing_submitted_for_review_at',
            ] as $column) {
                if (Schema::hasColumn('vendor_properties', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (!Schema::hasColumn('users', 'portal_service_categories')) {
                    $table->json('portal_service_categories')->nullable()->after('portal_vendor_id');
                }

                if (!Schema::hasColumn('users', 'vendor_onboarding_step')) {
                    $table->unsignedTinyInteger('vendor_onboarding_step')->default(1)->after('portal_service_categories');
                }
            });
        }

        if (Schema::hasTable('vendor_properties') && !Schema::hasColumn('vendor_properties', 'listing_category')) {
            Schema::table('vendor_properties', function (Blueprint $table): void {
                $table->string('listing_category', 40)->nullable()->after('property_type');
                $table->index(['vendor_user_id', 'listing_category']);
            });
        }

        if (Schema::hasTable('vendor_services') && !Schema::hasColumn('vendor_services', 'listing_category')) {
            Schema::table('vendor_services', function (Blueprint $table): void {
                $table->string('listing_category', 40)->nullable()->after('category');
                $table->index(['vendor_user_id', 'listing_category']);
            });
        }

        if (!Schema::hasTable('vendor_property_room_categories')) {
            Schema::create('vendor_property_room_categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_property_id')->nullable()->constrained('vendor_properties')->nullOnDelete();
                $table->string('name', 160);
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedInteger('max_occupancy')->default(1);
                $table->string('bed_type', 80)->nullable();
                $table->text('amenities')->nullable();
                $table->decimal('base_price', 12, 2)->default(0);
                $table->string('currency', 8)->default('MVR');
                $table->timestamps();

                $table->index(['vendor_user_id', 'vendor_property_id']);
            });
        }

        if (!Schema::hasTable('vendor_listing_media')) {
            Schema::create('vendor_listing_media', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('entity_type', 40);
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('file_path', 1024);
                $table->string('mime_type', 120)->nullable();
                $table->string('alt_text', 190)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->index(['vendor_user_id', 'entity_type']);
                $table->index(['entity_type', 'entity_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_listing_media')) {
            Schema::drop('vendor_listing_media');
        }

        if (Schema::hasTable('vendor_property_room_categories')) {
            Schema::drop('vendor_property_room_categories');
        }

        if (Schema::hasTable('vendor_services') && Schema::hasColumn('vendor_services', 'listing_category')) {
            Schema::table('vendor_services', function (Blueprint $table): void {
                $table->dropIndex(['vendor_user_id', 'listing_category']);
                $table->dropColumn('listing_category');
            });
        }

        if (Schema::hasTable('vendor_properties') && Schema::hasColumn('vendor_properties', 'listing_category')) {
            Schema::table('vendor_properties', function (Blueprint $table): void {
                $table->dropIndex(['vendor_user_id', 'listing_category']);
                $table->dropColumn('listing_category');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (Schema::hasColumn('users', 'vendor_onboarding_step')) {
                    $table->dropColumn('vendor_onboarding_step');
                }
                if (Schema::hasColumn('users', 'portal_service_categories')) {
                    $table->dropColumn('portal_service_categories');
                }
            });
        }
    }
};

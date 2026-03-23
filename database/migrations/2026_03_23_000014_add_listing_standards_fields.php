<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_properties')) {
            Schema::table('vendor_properties', function (Blueprint $table): void {
                if (!Schema::hasColumn('vendor_properties', 'listing_details')) {
                    $table->json('listing_details')->nullable()->after('max_guests');
                }
            });
        }

        if (Schema::hasTable('vendor_services')) {
            Schema::table('vendor_services', function (Blueprint $table): void {
                if (!Schema::hasColumn('vendor_services', 'service_details')) {
                    $table->json('service_details')->nullable()->after('is_active');
                }
            });
        }

        if (Schema::hasTable('vendor_listing_media')) {
            Schema::table('vendor_listing_media', function (Blueprint $table): void {
                if (!Schema::hasColumn('vendor_listing_media', 'width_px')) {
                    $table->unsignedInteger('width_px')->nullable()->after('mime_type');
                }
                if (!Schema::hasColumn('vendor_listing_media', 'height_px')) {
                    $table->unsignedInteger('height_px')->nullable()->after('width_px');
                }
                if (!Schema::hasColumn('vendor_listing_media', 'file_size_kb')) {
                    $table->unsignedInteger('file_size_kb')->nullable()->after('height_px');
                }
                if (!Schema::hasColumn('vendor_listing_media', 'quality_grade')) {
                    $table->string('quality_grade', 16)->nullable()->after('file_size_kb');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_listing_media')) {
            Schema::table('vendor_listing_media', function (Blueprint $table): void {
                if (Schema::hasColumn('vendor_listing_media', 'quality_grade')) {
                    $table->dropColumn('quality_grade');
                }
                if (Schema::hasColumn('vendor_listing_media', 'file_size_kb')) {
                    $table->dropColumn('file_size_kb');
                }
                if (Schema::hasColumn('vendor_listing_media', 'height_px')) {
                    $table->dropColumn('height_px');
                }
                if (Schema::hasColumn('vendor_listing_media', 'width_px')) {
                    $table->dropColumn('width_px');
                }
            });
        }

        if (Schema::hasTable('vendor_services') && Schema::hasColumn('vendor_services', 'service_details')) {
            Schema::table('vendor_services', function (Blueprint $table): void {
                $table->dropColumn('service_details');
            });
        }

        if (Schema::hasTable('vendor_properties') && Schema::hasColumn('vendor_properties', 'listing_details')) {
            Schema::table('vendor_properties', function (Blueprint $table): void {
                $table->dropColumn('listing_details');
            });
        }
    }
};

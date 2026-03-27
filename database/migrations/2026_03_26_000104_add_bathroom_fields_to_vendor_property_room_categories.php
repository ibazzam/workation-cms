<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_property_room_categories', 'bathroom_type')) {
                $table->string('bathroom_type', 40)->nullable()->after('bed_type');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'bathroom_count')) {
                $table->unsignedInteger('bathroom_count')->nullable()->after('bathroom_type');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'bathroom_amenities')) {
                $table->text('bathroom_amenities')->nullable()->after('amenities');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_amenities')) {
                $table->dropColumn('bathroom_amenities');
            }
            if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_count')) {
                $table->dropColumn('bathroom_count');
            }
            if (Schema::hasColumn('vendor_property_room_categories', 'bathroom_type')) {
                $table->dropColumn('bathroom_type');
            }
        });
    }
};

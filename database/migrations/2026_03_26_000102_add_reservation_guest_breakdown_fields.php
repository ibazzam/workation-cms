<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_reservations', 'vendor_room_category_id')) {
                $table->unsignedBigInteger('vendor_room_category_id')->nullable()->after('vendor_property_id');
                $table->index(['vendor_room_category_id']);
            }
            if (!Schema::hasColumn('vendor_reservations', 'adult_guests')) {
                $table->unsignedInteger('adult_guests')->default(1)->after('guests');
            }
            if (!Schema::hasColumn('vendor_reservations', 'child_guests')) {
                $table->unsignedInteger('child_guests')->default(0)->after('adult_guests');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            if (Schema::hasColumn('vendor_reservations', 'child_guests')) {
                $table->dropColumn('child_guests');
            }
            if (Schema::hasColumn('vendor_reservations', 'adult_guests')) {
                $table->dropColumn('adult_guests');
            }
            if (Schema::hasColumn('vendor_reservations', 'vendor_room_category_id')) {
                $table->dropIndex(['vendor_room_category_id']);
                $table->dropColumn('vendor_room_category_id');
            }
        });
    }
};

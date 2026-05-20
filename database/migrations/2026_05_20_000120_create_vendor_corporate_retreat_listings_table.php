<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_corporate_retreat_listings')) {
            Schema::create('vendor_corporate_retreat_listings', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('vendor_property_id')->nullable()->unique();
                $table->unsignedBigInteger('vendor_user_id');
                $table->string('name', 160);
                $table->string('status', 32)->default('active');
                $table->string('location', 255)->nullable();
                $table->text('description')->nullable();
                $table->decimal('base_price', 12, 2)->default(0);
                $table->string('currency', 8)->default('MVR');
                $table->unsignedInteger('max_guests')->default(0);
                $table->json('details')->nullable();
                $table->json('listing_details')->nullable();
                $table->string('listing_moderation_status', 32)->default('draft');
                $table->text('listing_admin_notes')->nullable();
                $table->timestamp('listing_submitted_for_review_at')->nullable();
                $table->timestamp('listing_approved_at')->nullable();
                $table->unsignedBigInteger('listing_approved_by_user_id')->nullable();
                $table->timestamps();

                $table->index(['vendor_user_id', 'status']);
                $table->index(['vendor_user_id', 'updated_at']);
            });
        }

        if (!Schema::hasTable('vendor_excursion_listings') || !Schema::hasTable('vendor_corporate_retreat_listings')) {
            return;
        }

        $sourceRows = DB::table('vendor_excursion_listings')->get();
        foreach ($sourceRows as $row) {
            $detailsRaw = null;
            if (Schema::hasColumn('vendor_excursion_listings', 'listing_details')) {
                $detailsRaw = $row->listing_details;
            } elseif (Schema::hasColumn('vendor_excursion_listings', 'details')) {
                $detailsRaw = $row->details;
            }

            $details = [];
            if (is_string($detailsRaw) && trim($detailsRaw) !== '') {
                $decoded = json_decode($detailsRaw, true);
                if (is_array($decoded)) {
                    $details = $decoded;
                }
            }

            $isRetreat = false;
            if (Schema::hasColumn('vendor_excursion_listings', 'is_corporate_retreat')) {
                $isRetreat = $isRetreat || ((int) ($row->is_corporate_retreat ?? 0) === 1);
            }
            if (Schema::hasColumn('vendor_excursion_listings', 'is_retreat_package')) {
                $isRetreat = $isRetreat || ((int) ($row->is_retreat_package ?? 0) === 1);
            }
            $isRetreat = $isRetreat
                || in_array(strtolower(trim((string) ($details['is_corporate_retreat'] ?? '0'))), ['1', 'true', 'yes', 'on'], true)
                || in_array(strtolower(trim((string) ($details['is_retreat_package'] ?? '0'))), ['1', 'true', 'yes', 'on'], true);

            if (!$isRetreat) {
                continue;
            }

            $payload = [
                'vendor_property_id' => (int) ($row->vendor_property_id ?? 0),
                'vendor_user_id' => (int) ($row->vendor_user_id ?? 0),
                'name' => (string) ($row->name ?? ''),
                'status' => (string) ($row->status ?? 'active'),
                'location' => (string) ($row->location ?? ''),
                'description' => (string) ($row->description ?? ''),
                'base_price' => (float) ($row->base_price ?? 0),
                'currency' => (string) ($row->currency ?? 'MVR'),
                'max_guests' => (int) ($row->max_guests ?? 0),
                'details' => $detailsRaw,
                'listing_details' => $detailsRaw,
                'listing_moderation_status' => (string) ($row->listing_moderation_status ?? 'draft'),
                'listing_admin_notes' => (string) ($row->listing_admin_notes ?? ''),
                'listing_submitted_for_review_at' => $row->listing_submitted_for_review_at ?? null,
                'listing_approved_at' => $row->listing_approved_at ?? null,
                'listing_approved_by_user_id' => isset($row->listing_approved_by_user_id) ? (int) $row->listing_approved_by_user_id : null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            DB::table('vendor_corporate_retreat_listings')->updateOrInsert(
                ['vendor_property_id' => (int) ($row->vendor_property_id ?? 0)],
                $payload
            );

            DB::table('vendor_excursion_listings')
                ->where('id', (int) ($row->id ?? 0))
                ->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_excursion_listings') && Schema::hasTable('vendor_corporate_retreat_listings')) {
            $rows = DB::table('vendor_corporate_retreat_listings')->get();
            foreach ($rows as $row) {
                DB::table('vendor_excursion_listings')->updateOrInsert(
                    ['vendor_property_id' => (int) ($row->vendor_property_id ?? 0)],
                    [
                        'vendor_user_id' => (int) ($row->vendor_user_id ?? 0),
                        'name' => (string) ($row->name ?? ''),
                        'status' => (string) ($row->status ?? 'active'),
                        'location' => (string) ($row->location ?? ''),
                        'description' => (string) ($row->description ?? ''),
                        'base_price' => (float) ($row->base_price ?? 0),
                        'currency' => (string) ($row->currency ?? 'MVR'),
                        'max_guests' => (int) ($row->max_guests ?? 0),
                        'details' => $row->listing_details ?? $row->details ?? null,
                        'listing_details' => $row->listing_details ?? $row->details ?? null,
                        'listing_moderation_status' => (string) ($row->listing_moderation_status ?? 'draft'),
                        'listing_admin_notes' => (string) ($row->listing_admin_notes ?? ''),
                        'listing_submitted_for_review_at' => $row->listing_submitted_for_review_at ?? null,
                        'listing_approved_at' => $row->listing_approved_at ?? null,
                        'listing_approved_by_user_id' => isset($row->listing_approved_by_user_id) ? (int) $row->listing_approved_by_user_id : null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]
                );
            }
        }

        Schema::dropIfExists('vendor_corporate_retreat_listings');
    }
};

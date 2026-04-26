<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_accommodation_transfer_rates')) {
            Schema::create('vendor_accommodation_transfer_rates', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('vendor_property_id');
                $table->string('transfer_mode', 80);
                $table->string('resident_type', 20);
                $table->string('passenger_type', 20);
                $table->decimal('rate', 12, 2)->default(0);
                $table->decimal('base_charge', 12, 2)->nullable();
                $table->timestamps();

                $table->unique(
                    ['vendor_property_id', 'transfer_mode', 'resident_type', 'passenger_type'],
                    'vendor_acc_transfer_rates_unique_row'
                );
                $table->index(['vendor_property_id', 'transfer_mode'], 'vendor_acc_transfer_rates_lookup');
            });
        }

        if (!Schema::hasTable('vendor_accommodation_features')) {
            Schema::create('vendor_accommodation_features', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('vendor_property_id');
                $table->string('feature_type', 32);
                $table->string('feature_key', 120);
                $table->timestamps();

                $table->unique(
                    ['vendor_property_id', 'feature_type', 'feature_key'],
                    'vendor_acc_features_unique_row'
                );
                $table->index(['feature_type', 'feature_key'], 'vendor_acc_features_search');
            });
        }

        if (!Schema::hasTable('vendor_accommodation_policies')) {
            Schema::create('vendor_accommodation_policies', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('vendor_property_id')->unique();
                $table->string('check_in_time', 10)->nullable();
                $table->string('check_out_time', 10)->nullable();
                $table->unsignedInteger('check_in_grace_minutes')->nullable();
                $table->string('early_check_in_allowed', 50)->nullable();
                $table->string('late_check_out_allowed', 50)->nullable();
                $table->unsignedInteger('minimum_nights')->nullable();
                $table->text('house_rules')->nullable();
                $table->text('child_policy')->nullable();
                $table->text('cancellation_policy')->nullable();
                $table->decimal('early_check_in_fee', 12, 2)->nullable();
                $table->decimal('late_check_out_fee', 12, 2)->nullable();
                $table->string('property_type', 80)->nullable();
                $table->unsignedTinyInteger('star_rating')->nullable();
                $table->timestamps();

                $table->index('vendor_property_id', 'vendor_acc_policies_lookup');
            });
        }

        if (!Schema::hasTable('vendor_accommodation_listings') || !Schema::hasColumn('vendor_accommodation_listings', 'details')) {
            return;
        }

        DB::table('vendor_accommodation_listings')
            ->select(['id', 'vendor_property_id', 'details'])
            ->orderBy('id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    $vendorPropertyId = (int) ($row->vendor_property_id ?? 0);
                    if ($vendorPropertyId <= 0) {
                        continue;
                    }

                    $detailsRaw = (string) ($row->details ?? '');
                    $details = json_decode($detailsRaw, true);
                    if (!is_array($details)) {
                        $details = [];
                    }

                    $transferOptions = is_array($details['transfer_options'] ?? null) ? $details['transfer_options'] : [];
                    $transferRates = is_array($details['transfer_rates'] ?? null) ? $details['transfer_rates'] : [];
                    $transferMatrix = is_array($details['transfer_rate_matrix'] ?? null) ? $details['transfer_rate_matrix'] : [];
                    $baseLocal = isset($details['transfer_base_local']) && is_numeric($details['transfer_base_local'])
                        ? (float) $details['transfer_base_local']
                        : 0.0;
                    $baseForeign = isset($details['transfer_base_foreign']) && is_numeric($details['transfer_base_foreign'])
                        ? (float) $details['transfer_base_foreign']
                        : 0.0;

                    DB::table('vendor_accommodation_transfer_rates')
                        ->where('vendor_property_id', $vendorPropertyId)
                        ->delete();

                    foreach ($transferOptions as $optionRaw) {
                        $transferMode = strtolower(trim((string) $optionRaw));
                        if ($transferMode === '') {
                            continue;
                        }

                        $modeMatrix = is_array($transferMatrix[$transferMode] ?? null)
                            ? $transferMatrix[$transferMode]
                            : [];

                        $localAdult = isset($modeMatrix['local_adult_charge']) && is_numeric($modeMatrix['local_adult_charge'])
                            ? (float) $modeMatrix['local_adult_charge']
                            : 0.0;
                        $localChild = isset($modeMatrix['local_child_charge']) && is_numeric($modeMatrix['local_child_charge'])
                            ? (float) $modeMatrix['local_child_charge']
                            : 0.0;
                        $foreignAdult = isset($modeMatrix['foreign_adult_charge']) && is_numeric($modeMatrix['foreign_adult_charge'])
                            ? (float) $modeMatrix['foreign_adult_charge']
                            : (isset($transferRates[$transferMode]) && is_numeric($transferRates[$transferMode]) ? (float) $transferRates[$transferMode] : 0.0);
                        $foreignChild = isset($modeMatrix['foreign_child_charge']) && is_numeric($modeMatrix['foreign_child_charge'])
                            ? (float) $modeMatrix['foreign_child_charge']
                            : 0.0;

                        $rowsToInsert = [
                            ['resident_type' => 'local', 'passenger_type' => 'adult', 'rate' => max(0, $localAdult), 'base_charge' => max(0, $baseLocal)],
                            ['resident_type' => 'local', 'passenger_type' => 'child', 'rate' => max(0, $localChild), 'base_charge' => max(0, $baseLocal)],
                            ['resident_type' => 'foreigner', 'passenger_type' => 'adult', 'rate' => max(0, $foreignAdult), 'base_charge' => max(0, $baseForeign)],
                            ['resident_type' => 'foreigner', 'passenger_type' => 'child', 'rate' => max(0, $foreignChild), 'base_charge' => max(0, $baseForeign)],
                        ];

                        foreach ($rowsToInsert as $item) {
                            DB::table('vendor_accommodation_transfer_rates')->insert([
                                'vendor_property_id' => $vendorPropertyId,
                                'transfer_mode' => $transferMode,
                                'resident_type' => $item['resident_type'],
                                'passenger_type' => $item['passenger_type'],
                                'rate' => round((float) $item['rate'], 2),
                                'base_charge' => round((float) $item['base_charge'], 2),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    $amenities = is_array($details['property_amenities'] ?? null) ? $details['property_amenities'] : [];
                    $facilities = is_array($details['property_features'] ?? null) ? $details['property_features'] : [];

                    DB::table('vendor_accommodation_features')
                        ->where('vendor_property_id', $vendorPropertyId)
                        ->delete();

                    foreach ($amenities as $amenityRaw) {
                        $amenity = trim((string) $amenityRaw);
                        if ($amenity === '') {
                            continue;
                        }

                        DB::table('vendor_accommodation_features')->insert([
                            'vendor_property_id' => $vendorPropertyId,
                            'feature_type' => 'amenity',
                            'feature_key' => $amenity,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    foreach ($facilities as $facilityRaw) {
                        $facility = trim((string) $facilityRaw);
                        if ($facility === '') {
                            continue;
                        }

                        DB::table('vendor_accommodation_features')->insert([
                            'vendor_property_id' => $vendorPropertyId,
                            'feature_type' => 'facility',
                            'feature_key' => $facility,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('vendor_accommodation_policies')->updateOrInsert(
                        ['vendor_property_id' => $vendorPropertyId],
                        [
                            'check_in_time' => trim((string) ($details['check_in_time'] ?? '')) ?: null,
                            'check_out_time' => trim((string) ($details['check_out_time'] ?? '')) ?: null,
                            'check_in_grace_minutes' => isset($details['check_in_grace_minutes']) && is_numeric($details['check_in_grace_minutes']) ? (int) $details['check_in_grace_minutes'] : null,
                            'early_check_in_allowed' => trim((string) ($details['early_check_in_allowed'] ?? '')) ?: null,
                            'late_check_out_allowed' => trim((string) ($details['late_check_out_allowed'] ?? '')) ?: null,
                            'minimum_nights' => isset($details['minimum_nights']) && is_numeric($details['minimum_nights']) ? (int) $details['minimum_nights'] : null,
                            'house_rules' => trim((string) ($details['house_rules'] ?? '')) ?: null,
                            'child_policy' => trim((string) ($details['child_policy'] ?? '')) ?: null,
                            'cancellation_policy' => trim((string) ($details['cancellation_policy'] ?? '')) ?: null,
                            'early_check_in_fee' => isset($details['early_check_in_fee']) && is_numeric($details['early_check_in_fee']) ? round((float) $details['early_check_in_fee'], 2) : null,
                            'late_check_out_fee' => isset($details['late_check_out_fee']) && is_numeric($details['late_check_out_fee']) ? round((float) $details['late_check_out_fee'], 2) : null,
                            'property_type' => trim((string) ($details['property_type'] ?? '')) ?: null,
                            'star_rating' => isset($details['star_rating']) && is_numeric($details['star_rating']) ? (int) $details['star_rating'] : null,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_accommodation_policies');
        Schema::dropIfExists('vendor_accommodation_features');
        Schema::dropIfExists('vendor_accommodation_transfer_rates');
    }
};

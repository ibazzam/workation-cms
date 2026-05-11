<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Liveaboard columns
$lbCols = Schema::getColumnListing('vendor_liveaboard_listings');
echo "=== LIVEABOARD COLUMNS ===\n";
echo implode(', ', $lbCols) . "\n\n";

// Sample liveaboard row
$lb = DB::table('vendor_liveaboard_listings')->first();
echo "=== SAMPLE LIVEABOARD ROW ===\n";
print_r($lb);
echo "\n";

// Liveaboard moderation status counts
$lbMod = DB::table('vendor_liveaboard_listings')
    ->selectRaw('listing_moderation_status, count(*) as c')
    ->groupBy('listing_moderation_status')
    ->get();
echo "=== LIVEABOARD MODERATION STATUSES ===\n";
print_r($lbMod);
echo "\n";

// Sea transport columns
$stCols = Schema::getColumnListing('vendor_sea_transport_listings');
echo "=== SEA TRANSPORT COLUMNS ===\n";
echo implode(', ', $stCols) . "\n\n";

// Sample sea transport row
$st = DB::table('vendor_sea_transport_listings')->first();
echo "=== SAMPLE SEA TRANSPORT ROW ===\n";
print_r($st);
echo "\n";

// Media entity types in vendor_listing_media
if (Schema::hasTable('vendor_listing_media')) {
    $mediaTypes = DB::table('vendor_listing_media')
        ->selectRaw('entity_type, count(*) as c')
        ->groupBy('entity_type')
        ->get();
    echo "=== MEDIA ENTITY TYPES (ALL) ===\n";
    print_r($mediaTypes);

    // Sea transport media specifically
    $stMediaIds = DB::table('vendor_sea_transport_listings')
        ->pluck('vendor_property_id')
        ->filter(fn($id) => $id > 0)
        ->unique()
        ->values();
    $stMedia = DB::table('vendor_listing_media')
        ->whereIn('entity_id', $stMediaIds->isNotEmpty() ? $stMediaIds->all() : [0])
        ->selectRaw('entity_type, entity_id, count(*) as c')
        ->groupBy('entity_type', 'entity_id')
        ->limit(20)
        ->get();
    echo "\n=== SEA TRANSPORT MEDIA BY entity_id ===\n";
    print_r($stMedia);
}

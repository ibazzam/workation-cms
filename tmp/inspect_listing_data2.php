<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Check vendor_properties for liveaboard/sea_transport listings
$vpCols = Schema::getColumnListing('vendor_properties');
echo "=== VENDOR_PROPERTIES COLUMNS ===\n";
echo implode(', ', $vpCols) . "\n\n";

$liveaboards = DB::table('vendor_properties')
    ->where(function($q) {
        $q->where('listing_category', 'liveaboard')
          ->orWhere('category', 'liveaboard');
    })
    ->limit(5)
    ->get();
echo "=== LIVEABOARD IN vendor_properties (" . count($liveaboards) . ") ===\n";
print_r($liveaboards);

$seaTransport = DB::table('vendor_properties')
    ->where(function($q) {
        $q->where('listing_category', 'sea_transport')
          ->orWhere('category', 'sea_transport');
    })
    ->limit(5)
    ->get();
echo "\n=== SEA TRANSPORT IN vendor_properties (" . count($seaTransport) . ") ===\n";
print_r($seaTransport);

// Check vendor_services if it exists
if (Schema::hasTable('vendor_services')) {
    $svsCols = Schema::getColumnListing('vendor_services');
    echo "\n=== VENDOR_SERVICES COLUMNS ===\n";
    echo implode(', ', $svsCols) . "\n\n";

    $svsLiveaboard = DB::table('vendor_services')
        ->where(function($q) {
            $q->where('listing_category', 'liveaboard')
              ->orWhere('category', 'liveaboard');
        })
        ->limit(5)
        ->get();
    echo "=== LIVEABOARD IN vendor_services (" . count($svsLiveaboard) . ") ===\n";
    print_r($svsLiveaboard);

    $svsSea = DB::table('vendor_services')
        ->where(function($q) {
            $q->where('listing_category', 'sea_transport')
              ->orWhere('category', 'sea_transport');
        })
        ->limit(5)
        ->get();
    echo "\n=== SEA_TRANSPORT IN vendor_services (" . count($svsSea) . ") ===\n";
    print_r($svsSea);
}

// Check media table
if (Schema::hasTable('vendor_listing_media')) {
    $mediaAll = DB::table('vendor_listing_media')->count();
    echo "\n=== TOTAL MEDIA ROWS: $mediaAll ===\n";
    if ($mediaAll > 0) {
        $mediaByType = DB::table('vendor_listing_media')
            ->selectRaw('entity_type, count(*) as c')
            ->groupBy('entity_type')
            ->get();
        print_r($mediaByType);
        $sample = DB::table('vendor_listing_media')->first();
        print_r($sample);
    }
}

// Check all listing statuses in vendor_properties
$statuses = DB::table('vendor_properties')
    ->selectRaw('listing_category, listing_moderation_status, count(*) as c')
    ->groupBy('listing_category', 'listing_moderation_status')
    ->orderBy('listing_category')
    ->get();
echo "\n=== vendor_properties listing_category x moderation_status ===\n";
print_r($statuses);

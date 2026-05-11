<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// List ALL tables
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
echo "=== ALL TABLES ===\n";
foreach ($tables as $t) {
    echo $t->name . "\n";
}

// Check vendor_listing_media if it exists  
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

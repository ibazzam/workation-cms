<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Check each dedicated listing table counts and sample
$tables = [
    'vendor_accommodation_listings',
    'vendor_sea_transport_listings',
    'vendor_liveaboard_listings',
    'vendor_conference_room_listings',
    'vendor_excursion_listings',
    'vendor_remote_workspace_listings',
    'vendor_resort_day_visit_listings',
    'vendor_restaurant_listings',
    'vendor_vehicle_rental_listings',
    'vendor_water_sports_listings',
    'vendor_land_transport_listings',
];

foreach ($tables as $table) {
    $count = DB::table($table)->count();
    echo "$table: $count rows\n";
    if ($count > 0) {
        $row = DB::table($table)->first();
        $modStatus = $row->listing_moderation_status ?? 'N/A';
        $vpId = $row->vendor_property_id ?? 'N/A';
        $name = $row->name ?? 'N/A';
        echo "  First row: id={$row->id}, vendor_property_id=$vpId, name=$name, moderation=$modStatus\n";
        
        // Show all moderation status counts
        $statuses = DB::table($table)
            ->selectRaw('listing_moderation_status, count(*) as c')
            ->groupBy('listing_moderation_status')
            ->get();
        foreach ($statuses as $s) {
            echo "  Status '{$s->listing_moderation_status}': {$s->c}\n";
        }
    }
}

// Check vendor_services
$svsCount = DB::table('vendor_services')->count();
echo "\nvendor_services: $svsCount rows\n";
if ($svsCount > 0) {
    $sample = DB::table('vendor_services')->first();
    print_r($sample);
}

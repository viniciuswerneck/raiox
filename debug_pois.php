<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\City;
use App\Models\LocationReport;

$city = City::where('slug', 'jarinu-sp')->first();
$reports = LocationReport::where('cidade', $city->name)->where('uf', $city->uf)->get();

$amenities = [];
$shops = [];
$others = [];

foreach($reports as $r) {
    if(is_array($r->pois_json)) {
        foreach($r->pois_json as $p) {
            $tags = $p['tags'] ?? [];
            if(isset($tags['amenity'])) $amenities[$tags['amenity']] = ($amenities[$tags['amenity']] ?? 0) + 1;
            if(isset($tags['shop'])) $shops[$tags['shop']] = ($shops[$tags['shop']] ?? 0) + 1;
            $others[] = array_keys($tags);
        }
    }
}

echo "AMENITIES:\n";
print_r($amenities);
echo "SHOPS:\n";
print_r($shops);

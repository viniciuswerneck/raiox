<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$reports = \App\Models\LocationReport::all();
foreach ($reports as $report) {
    $original = $report->history_extract;
    $cleaned = preg_replace("/(\n\s*){3,}/", "\n\n", $original);
    if ($original !== $cleaned) {
        $report->update(['history_extract' => $cleaned]);
        echo "Cleaned CEP {$report->cep}".PHP_EOL;
    }
}

$cities = \App\Models\City::all();
foreach ($cities as $city) {
    $original = $city->history_extract;
    $cleaned = preg_replace("/(\n\s*){3,}/", "\n\n", $original);
    if ($original !== $cleaned) {
        $city->update(['history_extract' => $cleaned]);
        echo "Cleaned City {$city->name}".PHP_EOL;
    }
}
echo 'Done.'.PHP_EOL;

<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$raw = \App\Models\LocationReport::where('cep', '13201000')->value('history_extract');
echo 'Raw length: '.strlen($raw).PHP_EOL;

$jsonClean = preg_replace('/```json\s?|```/', '', $raw);
$analysis = json_decode(trim($jsonClean), true);

if ($analysis === null) {
    echo 'JSON Decode failed!'.PHP_EOL;
    echo 'Last error: '.json_last_error_msg().PHP_EOL;

    // Debug specific part where it might fail
    // Usually it fails around special characters or unescaped quotes
} else {
    echo 'JSON Decode success!'.PHP_EOL;
    echo 'Narrative length: '.strlen($analysis['narrative']).PHP_EOL;
}

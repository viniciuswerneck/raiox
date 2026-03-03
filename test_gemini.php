<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = config('services.gemini.key');
$models = ['gemini-1.5-flash', 'gemini-1.5-flash-latest', 'gemini-2.0-flash', 'gemini-2.0-flash-lite'];
$endpoints = ['v1', 'v1beta'];

foreach ($endpoints as $v) {
    foreach ($models as $m) {
        $url = "https://generativelanguage.googleapis.com/{$v}/models/{$m}:generateContent?key={$key}";
        echo "Testing {$v} with {$m}... ";
        $response = Illuminate\Support\Facades\Http::withoutVerifying()->post($url, [
            'contents' => [['parts' => [['text' => 'Hi']]]]
        ]);
        if ($response->successful()) {
            echo "SUCCESS!\n";
            exit;
        } else {
            echo "FAILED (" . $response->status() . ")\n";
            // echo $response->body() . "\n";
        }
    }
}

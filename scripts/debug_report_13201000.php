<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cep = '13201000';
$report = \App\Models\LocationReport::where('cep', $cep)->first();
if (! $report) {
    echo "Report not found for CEP {$cep}";
    exit;
}

$data = [
    'history_extract' => $report->history_extract,
    'status' => $report->status,
    'error_message' => $report->error_message,
];

file_put_contents('tmp_report_check.json', json_encode($data, JSON_PRETTY_PRINT));
echo 'Report data written to tmp_report_check.json';

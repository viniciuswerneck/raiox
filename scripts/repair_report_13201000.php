<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cep = '13201000';
$report = \App\Models\LocationReport::where('cep', $cep)->first();

if (! $report) {
    echo "Report not found for CEP {$cep}".PHP_EOL;
    exit;
}

echo 'Current status: '.$report->status.PHP_EOL;
$raw = $report->history_extract;

if (empty($raw)) {
    echo 'Current history_extract is empty.'.PHP_EOL;
    exit;
}

// Instantiate the job class just to use the private methods? No, I'll copy the methods or use reflection.
// Or just replicate the logic here.

function parseStructuredAnalysis(string $raw): ?array
{
    if (preg_match('/\{(?:.|\n)*\}/', $raw, $matches)) {
        $raw = $matches[0];
    }
    $json = preg_replace('/```json\s?|```/', '', $raw);
    $json = trim($json);
    $data = json_decode($json, true);
    if ($data) {
        return $data;
    }
    $sanitized = preg_replace_callback('/"(.*?)"/s', function ($matches) {
        return '"'.str_replace(["\n", "\r"], ['\\n', ''], $matches[1]).'"';
    }, $json);

    return json_decode($sanitized, true);
}

function fallbackNarrativeExtraction(string $raw): string
{
    if (preg_match('/"narrative":\s*"(.*?)"/s', $raw, $matches)) {
        $text = $matches[1];

        return trim(str_replace(['\\n', '\\r'], ["\n", ''], $text));
    }
    $clean = preg_replace('/```json\s?|```|\{|\}|"narrative":|"safety_analysis":|"real_estate":|"preco_m2":|"perfil_imoveis":|"tendencia_valorizacao":/i', '', $raw);
    $clean = preg_replace('/:[^,]+,/', '', $clean);
    $clean = str_replace(['**', '*'], '', $clean);

    return trim($clean);
}

$analysis = parseStructuredAnalysis($raw);
$narrative = '';

if ($analysis && isset($analysis['narrative'])) {
    $narrative = $analysis['narrative'];
    echo 'Successfully parsed JSON.'.PHP_EOL;
} else {
    $narrative = fallbackNarrativeExtraction($raw);
    echo 'Used fallback extraction.'.PHP_EOL;
}

if (! empty($narrative)) {
    $report->update(['history_extract' => $narrative]);
    echo 'Updated report with cleaned narrative. Length: '.strlen($narrative).PHP_EOL;

    // Also update Neighborhood cache if exists
    $cityModel = \App\Models\City::where('name', $report->cidade)->where('uf', $report->uf)->first();
    if ($cityModel && $report->bairro) {
        \App\Models\Neighborhood::where('city_id', $cityModel->id)
            ->where('name', $report->bairro)
            ->update(['history_extract' => $narrative]);
        echo 'Updated Neighborhood cache.'.PHP_EOL;
    }
} else {
    echo 'Could not extract narrative.'.PHP_EOL;
}

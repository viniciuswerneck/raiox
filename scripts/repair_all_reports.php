<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$reports = \App\Models\LocationReport::where('history_extract', 'like', '```%')->orWhere('history_extract', 'like', '{%')->get();

echo "Found " . $reports->count() . " reports with likely JSON-blob narratives." . PHP_EOL;

function parseStructuredAnalysis(string $raw): ?array
{
    if (preg_match('/\{(?:.|\n)*\}/', $raw, $matches)) {
        $raw = $matches[0];
    }
    $json = preg_replace('/```json\s?|```/', '', $raw);
    $json = trim($json);
    $data = json_decode($json, true);
    if ($data) return $data;
    $sanitized = preg_replace_callback('/"(.*?)"/s', function ($matches) {
        return '"' . str_replace(["\n", "\r"], ["\\n", ""], $matches[1]) . '"';
    }, $json);
    return json_decode($sanitized, true);
}

function fallbackNarrativeExtraction(string $raw): string
{
    if (preg_match('/"narrative":\s*"(.*?)"/s', $raw, $matches)) {
        $text = $matches[1];
        return trim(str_replace(['\\n', '\\r'], ["\n", ""], $text));
    }
    $clean = preg_replace('/```json\s?|```|\{|\}|"narrative":|"safety_analysis":|"real_estate":|"preco_m2":|"perfil_imoveis":|"tendencia_valorizacao":/i', '', $raw);
    $clean = preg_replace('/:[^,]+,/', '', $clean);
    $clean = str_replace(['**', '*'], '', $clean);
    return trim($clean);
}

foreach ($reports as $report) {
    echo "Processing CEP {$report->cep}..." . PHP_EOL;
    $raw = $report->history_extract;
    $analysis = parseStructuredAnalysis($raw);
    $narrative = "";

    if ($analysis && isset($analysis['narrative'])) {
        $narrative = $analysis['narrative'];
    } else {
        $narrative = fallbackNarrativeExtraction($raw);
    }

    if (!empty($narrative) && str_contains($narrative, '{') === false) { // Basic sanity check
        $report->update(['history_extract' => $narrative]);
        echo "Updated CEP {$report->cep}. Narrative length: " . strlen($narrative) . PHP_EOL;
        
        $cityModel = \App\Models\City::where('name', $report->cidade)->where('uf', $report->uf)->first();
        if ($cityModel && $report->bairro) {
            \App\Models\Neighborhood::where('city_id', $cityModel->id)
                ->where('name', $report->bairro)
                ->update(['history_extract' => $narrative]);
        }
    } else {
        echo "Could not clean narrative for CEP {$report->cep}" . PHP_EOL;
    }
}
echo "Done." . PHP_EOL;

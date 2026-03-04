<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GeminiService;

$gemini = new GeminiService();
$wikiText = "Jarinu é um município brasileiro do estado de São Paulo, com uma população estimada em 40 007 habitantes, conforme dados do IBGE de 2025. Pertence à Região Metropolitana de Jundiaí.";
$location = "Jarinu, SP";

echo "Testando Gemini para: $location\n";
$result = $gemini->generateNeighborhoodSummary($wikiText, $location);

if ($result) {
    echo "Sucesso!\n";
    echo "HISTORIA (Tamanho: " . strlen($result['historia']) . "):\n";
    echo $result['historia'] . "\n";
} else {
    echo "Falha no Gemini.\n";
}

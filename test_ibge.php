<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$time1 = microtime(true);
$response = Http::withoutVerifying()->get('https://servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/96385|29171|60037|29168|29765/resultados/4106902');
$time2 = microtime(true);
echo 'Took '.($time2 - $time1)." seconds for batch request.\n";

$data = $response->json();
foreach ($data as $ind) {
    if (! empty($ind['res'][0]['res'])) {
        echo "Ind {$ind['id']}: ".end($ind['res'][0]['res'])."\n";
    }
}

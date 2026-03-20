<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\City;
use App\Models\LocationReport;
use App\Services\Agents\POIAgent;

$city = City::where('slug', 'jarinu-sp')->first();
$reports = LocationReport::where('cidade', $city->name)->where('uf', $city->uf)->where('status', 'completed')->get();

$poiAgent = new POIAgent;

echo 'Iniciando re-mapeamento de '.count($reports)." CEPs de Jarinu...\n";

foreach ($reports as $index => $r) {
    if (! $r->lat || ! $r->lng) {
        continue;
    }

    // Simples check para economizar API se já tiver muitos itens
    // Mas aqui queremos forçar o novo mapeamento pois os atuais estão zoados ou incompletos.
    echo 'Processing '.($index + 1).'/'.count($reports).": CEP {$r->cep}...\n";

    $adaptiveData = $poiAgent->fetchPOIsAdaptive($r->lat, $r->lng);
    $pois = $adaptiveData['pois'] ?? [];

    $r->update([
        'pois_json' => $pois,
        'search_radius' => $adaptiveData['radius'] ?? 1000,
    ]);

    if (($index + 1) % 5 == 0) {
        echo "Pausa real de 2s para evitar 429 (Rate Limit)...\n";
        sleep(2);
    }
}

echo "CONCLUÍDO! Agora Jarinu tem os dados atualizados das ruas.\n";

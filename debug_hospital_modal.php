<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\City;
use App\Services\Agents\POIAgent;

$city = City::where('slug', 'jarinu-sp')->first();
$category = 'Saúde (Hospitais)';

$tagMap = [
    'school' => 'Educação', 'kindergarten' => 'Educação Infantil', 'university' => 'Universidades',
    'college' => 'Faculdades', 'bank' => 'Bancos', 'atm' => 'Caixas Eletrônicos',
    'pharmacy' => 'Farmácias', 'hospital' => 'Saúde (Hospitais)', 'clinic' => 'Clínicas Médicas',
    'doctors' => 'Médicos/Consultórios', 'dentist' => 'Odontologia', 'veterinary' => 'Pet/Veterinários',
    'restaurant' => 'Gastronomia', 'cafe' => 'Cafeterias', 'fast_food' => 'Lanches/Fast Food',
    'bar' => 'Lazer Noturno (Bares)', 'pub' => 'Pubs/Bares', 'nightclub' => 'Casas Noturnas',
    'fuel' => 'Postos de Combustível', 'gym' => 'Saúde & Bem-Estar', 'fitness_centre' => 'Academias',
    'park' => 'Lazer & Áreas Verdes', 'square' => 'Praças Públicas', 'church' => 'Templos/Igrejas',
    'place_of_worship' => 'Religião', 'bakery' => 'Padarias/Confeitarias', 'car_repair' => 'Mecânicos/Oficinas',
    'car_wash' => 'Lava Rápido', 'parking' => 'Estacionamento', 'library' => 'Cultura/Bibliotecas',
    'theatre' => 'Cultura/Teatros', 'cinema' => 'Cultura/Cinemas', 'police' => 'Segurança Pública',
    'fire_station' => 'Bombeiros', 'post_office' => 'Serviços Postais', 'townhall' => 'Serviços Públicos',
    'supermarket' => 'Supermercados', 'convenience' => 'Lojas de Conveniência', 'mall' => 'Shopping Center',
    'department_store' => 'Lojas de Departamento', 'clothes' => 'Moda/Vestuário', 'shoes' => 'Lojas de Calçados',
    'beauty' => 'Beleza & Estética', 'hairdresser' => 'Salão de Beleza', 'optician' => 'Óticas',
    'jewelry' => 'Joalherias', 'variety_store' => 'Lojas de Variedades', 'hardware' => 'Material de Construção',
    'doityourself' => 'Ferragens/DIY', 'furniture' => 'Móveis & Decoração', 'stationery' => 'Papelaria',
    'pet' => 'Pet Shop', 'toys' => 'Brinquedos', 'electronics' => 'Eletrônicos',
    'mobile_phone' => 'Lojas de Celular', 'bicycle' => 'Bicicletarias', 'laundry' => 'Lavanderias',
    'florist' => 'Floriculturas', 'butcher' => 'Açougues', 'hotel' => 'Hospedagem (Hoteis)',
    'motel' => 'Hospedagem (Moteis)', 'museum' => 'Cultura/Museus', 'attraction' => 'Pontos Turísticos',
    'viewpoint' => 'Mirantes/Turismo', 'stadium' => 'Esportes/Estádios', 'sports_centre' => 'Centros Esportivos',
    'swimming_pool' => 'Lazer Aquático',
];

$b = $city->bbox_json;
$overpassBBox = "{$b[0]},{$b[2]},{$b[1]},{$b[3]}";
echo "BBOX: $overpassBBox\n";

$agent = app(POIAgent::class);
$pois = $agent->fetchPOIsByBBox($overpassBBox, 5000);
echo 'Total POIs from Overpass: '.count($pois)."\n";

$filtered = array_filter($pois, function ($poi) use ($category, $tagMap) {
    $tags = $poi['tags'] ?? [];
    foreach (['amenity', 'shop', 'leisure', 'tourism', 'healthcare', 'craft'] as $key) {
        if (isset($tags[$key])) {
            $osmVal = $tags[$key];
            $type = $tagMap[$osmVal] ?? null;
            if ($type && $type === $category) {
                return true;
            }
        }
    }

    return false;
});

echo "Filtered POIs for '$category': ".count($filtered)."\n";
foreach ($filtered as $p) {
    echo '- '.($p['tags']['name'] ?? 'Unnamed').' (ID: '.$p['id'].")\n";
}

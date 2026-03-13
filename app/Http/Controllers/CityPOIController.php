<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\LocationReport;
use App\Services\Agents\POIAgent;
use Illuminate\Http\Request;

class CityPOIController extends Controller
{
    protected $poiAgent;

    public function __construct(POIAgent $poiAgent)
    {
        $this->poiAgent = $poiAgent;
    }

    public function getPOIsByCategory(string $slug, Request $request)
    {
        $city = City::where('slug', $slug)->first();
        
        if (!$city) {
            return response()->json(['error' => 'City not found'], 404);
        }

        $category = trim($request->query('category'));
        if (!$category) {
            return response()->json(['error' => 'Category is required'], 400);
        }

        // TagMap idêntico ao do CityDashboardService
        $tagMap = [
            'school' => 'Educação',
            'kindergarten' => 'Educação Infantil',
            'university' => 'Universidades',
            'college' => 'Faculdades',
            'bank' => 'Bancos',
            'atm' => 'Caixas Eletrônicos',
            'pharmacy' => 'Farmácias',
            'hospital' => 'Saúde (Hospitais)',
            'clinic' => 'Clínicas Médicas',
            'doctors' => 'Médicos/Consultórios',
            'dentist' => 'Odontologia',
            'veterinary' => 'Pet/Veterinários',
            'restaurant' => 'Gastronomia',
            'cafe' => 'Cafeterias',
            'fast_food' => 'Lanches/Fast Food',
            'bar' => 'Lazer Noturno (Bares)',
            'pub' => 'Pubs/Bares',
            'nightclub' => 'Casas Noturnas',
            'fuel' => 'Postos de Combustível',
            'gym' => 'Saúde & Bem-Estar',
            'fitness_centre' => 'Academias',
            'park' => 'Lazer & Áreas Verdes',
            'square' => 'Praças Públicas',
            'church' => 'Templos/Igrejas',
            'place_of_worship' => 'Religião',
            'bakery' => 'Padarias/Confeitarias',
            'car_repair' => 'Mecânicos/Oficinas',
            'car_wash' => 'Lava Rápido',
            'parking' => 'Estacionamento',
            'library' => 'Cultura/Bibliotecas',
            'theatre' => 'Cultura/Teatros',
            'cinema' => 'Cultura/Cinemas',
            'police' => 'Segurança Pública',
            'fire_station' => 'Bombeiros',
            'post_office' => 'Serviços Postais',
            'townhall' => 'Serviços Públicos',
            'supermarket' => 'Supermercados',
            'convenience' => 'Lojas de Conveniência',
            'mall' => 'Shopping Center',
            'department_store' => 'Lojas de Departamento',
            'clothes' => 'Moda/Vestuário',
            'shoes' => 'Lojas de Calçados',
            'beauty' => 'Beleza & Estética',
            'hairdresser' => 'Salão de Beleza',
            'optician' => 'Óticas',
            'jewelry' => 'Joalherias',
            'variety_store' => 'Lojas de Variedades',
            'hardware' => 'Material de Construção',
            'doityourself' => 'Ferragens/DIY',
            'furniture' => 'Móveis & Decoração',
            'stationery' => 'Papelaria',
            'pet' => 'Pet Shop',
            'toys' => 'Brinquedos',
            'electronics' => 'Eletrônicos',
            'mobile_phone' => 'Lojas de Celular',
            'bicycle' => 'Bicicletarias',
            'laundry' => 'Lavanderias',
            'florist' => 'Floriculturas',
            'butcher' => 'Açougues',
            'hotel' => 'Hospedagem (Hoteis)',
            'motel' => 'Hospedagem (Moteis)',
            'museum' => 'Cultura/Museus',
            'attraction' => 'Pontos Turísticos',
            'viewpoint' => 'Mirantes/Turismo',
            'stadium' => 'Esportes/Estádios',
            'sports_centre' => 'Centros Esportivos',
            'swimming_pool' => 'Lazer Aquático'
        ];

        // Mapeamento de Atalhos (Dashboard Widgets)
        $shortcutMap = [
            'Postos' => 'Postos de Combustível',
            'Saúde' => ['Saúde (Hospitais)', 'Clínicas Médicas'],
            'Educação' => ['Educação', 'Educação Infantil'],
            'Mercados' => 'Supermercados',
            'Farmácias' => 'Farmácias'
        ];

        $cacheKey = "city_pois_full_list_{$city->slug}";
        $uniquePoisList = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(24), function () use ($city) {
            $list = [];
            // Estratégia BBox
            if ($city->bbox_json) {
                $b = $city->bbox_json;
                $overpassBBox = "{$b[0]},{$b[2]},{$b[1]},{$b[3]}";
                $list = $this->poiAgent->fetchPOIsByBBox($overpassBBox, 15000);
            } else {
                $reports = LocationReport::where('cidade', $city->name)
                    ->where('uf', $city->uf)
                    ->where('status', 'completed')
                    ->get();

                $processedPoisIds = [];
                foreach ($reports as $report) {
                    if (is_array($report->pois_json)) {
                        foreach ($report->pois_json as $poi) {
                            $poiId = ($poi['type'] ?? '') . '_' . ($poi['id'] ?? '');
                            if (in_array($poiId, $processedPoisIds)) continue;
                            $processedPoisIds[] = $poiId;
                            $list[] = $poi;
                        }
                    }
                }
            }
            return $list;
        });

        // Filtrar usando a mesma lógica do Dashboard, mas robusto
        $filtered = array_filter($uniquePoisList, function ($poi) use ($category, $tagMap, $shortcutMap) {
            $tags = $poi['tags'] ?? [];
            foreach (['amenity', 'shop', 'leisure', 'tourism', 'healthcare', 'craft'] as $key) {
                if (isset($tags[$key])) {
                    $osmVal = $tags[$key];
                    $type = $tagMap[$osmVal] ?? null;
                    if ($type) {
                        if (strcasecmp(trim($type), $category) === 0) return true;
                        
                        $shortcut = $shortcutMap[$category] ?? null;
                        if ($shortcut) {
                            if (is_array($shortcut)) {
                                foreach ($shortcut as $s) {
                                    if (strcasecmp(trim($type), trim($s)) === 0) return true;
                                }
                            } else {
                                if (strcasecmp(trim($type), trim($shortcut)) === 0) return true;
                            }
                        }
                    }
                }
            }
            return false;
        });

        // Formatar para o Modal com Filtro de Proximidade Municipal
        $results = [];
        $cityLat = (float) $city->lat;
        $cityLng = (float) $city->lng;

        foreach ($filtered as $poi) {
            $tags = $poi['tags'] ?? [];
            $poiLat = $poi['lat'] ?? null;
            $poiLng = $poi['lon'] ?? null;

            // Filtro de Segurança: Se temos as coordenadas da cidade, ignoramos o que estiver a mais de 20km do centro
            // Isso evita que o Hospital de Campo Limpo apareça para Jarinu
            if ($cityLat && $cityLng && $poiLat && $poiLng) {
                $distance = $this->calculateDistance($cityLat, $cityLng, (float)$poiLat, (float)$poiLng);
                if ($distance > 15) continue; // 15km do centro da cidade é um limite seguro para Jarinu
            }

            $results[] = [
                'name' => $tags['name'] ?? ($tags['operator'] ?? 'Estabelecimento sem nome'),
                'street' => $tags['addr:street'] ?? ($tags['addr:full'] ?? 'Endereço não disponível no mapa'),
                'number' => $tags['addr:housenumber'] ?? '',
                'neighborhood' => $tags['addr:suburb'] ?? '',
                'phone' => $tags['phone'] ?? ($tags['contact:phone'] ?? null),
                'type' => $tags['amenity'] ?? ($tags['shop'] ?? ($tags['leisure'] ?? ($tags['tourism'] ?? 'outro')))
            ];
        }

        \Illuminate\Support\Facades\Log::info("CityPOI Modal: Category '$category' found " . count($results) . " items in " . $city->name);

        return response()->json([
            'category' => $category,
            'count' => count($results),
            'items' => $results
        ]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return ($miles * 1.609344);
    }
}

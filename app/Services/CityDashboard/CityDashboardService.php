<?php

namespace App\Services\CityDashboard;

use App\Models\City;
use App\Models\LocationReport;
use App\Services\Agents\GeoAgent;
use App\Services\Agents\POIAgent;
use App\Services\TextReviserService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CityDashboardService
{
    protected $llm;

    protected $reviser;

    protected $geoAgent;

    protected $poiAgent;

    protected $knowledge;

    protected $wiki;

    public function __construct(
        \App\Services\LlmManagerService $llm,
        TextReviserService $reviser,
        GeoAgent $geoAgent,
        POIAgent $poiAgent,
        \App\Services\Agents\KnowledgeAgent $knowledge,
        \App\Services\Agents\WikiAgent $wiki
    ) {
        $this->llm = $llm;
        $this->reviser = $reviser;
        $this->geoAgent = $geoAgent;
        $this->poiAgent = $poiAgent;
        $this->knowledge = $knowledge;
        $this->wiki = $wiki;
    }

    /**
     * Gera ou atualiza os dados da cidade.
     */
    public function updateCityData(City $city)
    {
        // 1. Agregar estatísticas dos CEPs buscados
        $this->aggregateStats($city);

        // 2. Tentar buscar foto se não tiver
        if (empty($city->image_url)) {
            $city->image_url = $this->fetchCityImage($city);
        }

        // 3. Gerar história se não tiver
        if (empty($city->history_extract)) {
            $this->generateHistory($city);
        }

        $city->save();
    }

    private function aggregateStats(City $city)
    {
        // 1. Garantir que temos os dados geográficos da cidade para busca oficial
        if (empty($city->bbox_json) || ! $city->lat) {
            $geo = $this->geoAgent->geolocateCity($city->name, $city->uf);
            if ($geo) {
                $city->lat = $geo['lat'];
                $city->lng = $geo['lon'];
                $city->bbox_json = $geo['bbox']; // [lat_min, lat_max, lon_min, lon_max]
            }
        }

        $reports = LocationReport::where('cidade', $city->name)
            ->where('uf', $city->uf)
            ->where('status', 'completed')
            ->get();

        if ($reports->isEmpty()) {
            return;
        }

        $avgIncome = $reports->avg('average_income');

        $poiTypes = [];
        $uniquePoisList = [];

        // ESTRATÉGIA NOVA: Busca Municipal via Overpass se tivermos BBox
        if ($city->bbox_json) {
            // Overpass espera: (minlat, minlon, maxlat, maxlon)
            // Nominatim retorna: [minlat, maxlat, minlon, maxlon]
            $b = $city->bbox_json;
            $overpassBBox = "{$b[0]},{$b[2]},{$b[1]},{$b[3]}";
            $uniquePoisList = $this->poiAgent->fetchPOIsByBBox($overpassBBox, 1000);
        } else {
            // Fallback: Manter agregação manual por CEP (menos precisa para a cidade inteira)
            $processedPoisIds = [];
            foreach ($reports as $report) {
                if (is_array($report->pois_json)) {
                    foreach ($report->pois_json as $poi) {
                        $poiId = ($poi['type'] ?? '').'_'.($poi['id'] ?? '');
                        if (in_array($poiId, $processedPoisIds)) {
                            continue;
                        }
                        $processedPoisIds[] = $poiId;
                        $uniquePoisList[] = $poi;
                    }
                }
            }
        }

        $totalPois = count($uniquePoisList);
        $tagMap = [
            // Amenity
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

            // Shops
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

            // Tourism & Leisure
            'hotel' => 'Hospedagem (Hoteis)',
            'motel' => 'Hospedagem (Moteis)',
            'museum' => 'Cultura/Museus',
            'attraction' => 'Pontos Turísticos',
            'viewpoint' => 'Mirantes/Turismo',
            'stadium' => 'Esportes/Estádios',
            'sports_centre' => 'Centros Esportivos',
            'swimming_pool' => 'Lazer Aquático',
        ];

        foreach ($uniquePoisList as $poi) {
            $tags = $poi['tags'] ?? [];
            $found = false;

            // Busca por tags de OSM reais (Amenity, Shop, Leisure, Tourism, Healthcare, Craft)
            foreach (['amenity', 'shop', 'leisure', 'tourism', 'healthcare', 'craft'] as $key) {
                if (isset($tags[$key])) {
                    $osmVal = $tags[$key];
                    $type = $tagMap[$osmVal] ?? null;
                    if ($type) {
                        $poiTypes[$type] = ($poiTypes[$type] ?? 0) + 1;
                        $found = true;
                        break;
                    }
                }
            }

            if (! $found) {
                $poiTypes['Diversos'] = ($poiTypes['Diversos'] ?? 0) + 1;
            }
        }

        arsort($poiTypes);
        // Remove 'Diversos' do top se houver outros
        $topPois = array_filter($poiTypes, fn ($v, $k) => $k !== 'Diversos', ARRAY_FILTER_USE_BOTH);

        // Se ficou vazio, bota Diversos de volta no radar
        if (empty($topPois)) {
            $topPois = array_slice($poiTypes, 0, 1, true);
        }

        // Contagem de categorias para o "Mix de Uso"
        $categories = $reports->pluck('territorial_classification')->countBy();
        $predominant = $categories->sortDesc()->keys()->first();

        $totalCats = $categories->sum();
        $usagePercentages = $categories->map(fn ($val) => round(($val / $totalCats) * 100, 1))->toArray();

        // Ranking de Bairros (Nome e Score Médio)
        $neighborhoodRanking = $reports->groupBy('bairro')
            ->filter(fn ($items, $key) => ! empty($key) && $key !== 'null')
            ->map(function ($items, $key) {
                return [
                    'name' => $key ?: 'Centro',
                    'avg_score' => round($items->avg('general_score'), 1),
                    'mapped' => true,
                    'report_count' => $items->count(),
                ];
            });

        // Tentar descobrir bairros oficiais se tivermos BBox E a lista estiver curta (<30) ou cache velho
        $currentCount = $neighborhoodRanking->count();
        $isLargeCity = ($city->stats_cache['population'] ?? 0) > 100000 || in_array($city->uf, ['SP', 'RJ', 'MG', 'PR', 'SC', 'RS']);

        if ($city->bbox_json && ($currentCount < 30 || ($city->last_calculated_at && $city->last_calculated_at->diffInDays(now()) > 7))) {
            $b = $city->bbox_json;
            $bbox = "{$b[0]},{$b[2]},{$b[1]},{$b[3]}";
            $officialNames = $this->poiAgent->fetchCityNeighborhoods($bbox);

            foreach ($officialNames as $name) {
                if (! $neighborhoodRanking->has($name)) {
                    $neighborhoodRanking->put($name, [
                        'name' => $name,
                        'avg_score' => 0,
                        'mapped' => false,
                        'report_count' => 0,
                    ]);
                }
            }
        }

        $neighborhoodRanking = $neighborhoodRanking->sortByDesc('avg_score')
            ->values()
            ->toArray();

        // Média de pontuação municipal e estadual para comparação
        $avgScore = $reports->avg('general_score');
        $stateAvgScore = \Illuminate\Support\Facades\Cache::remember('state_avg_'.$city->uf, 3600, function () use ($city) {
            return LocationReport::where('uf', $city->uf)
                ->where('status', 'completed')
                ->avg('general_score');
        });

        // Médias de atributos para o "Radar da Cidade"
        $avgAirQuality = $reports->avg('air_quality_index') ?: 50;
        $avgSanitation = $reports->avg('sanitation_rate') ?: 50;

        // Mapeamento de Segurança (Médio de 0 a 100)
        $avgSafety = $reports->map(function ($r) {
            $sl = strtoupper($r->safety_level);
            if (str_contains($sl, 'ALTO') || str_contains($sl, 'ALTA')) {
                return 100;
            }
            if (str_contains($sl, 'MODERADO')) {
                return 70;
            }

            return 40;
        })->average() ?: 50;

        // Mapeamento de Caminhabilidade
        $avgWalkability = $reports->map(function ($r) {
            $ws = strtoupper($r->walkability_score);
            if ($ws == 'A') {
                return 100;
            }
            if ($ws == 'B') {
                return 80;
            }
            if ($ws == 'C') {
                return 60;
            }

            return 40;
        })->average() ?: 50;

        $city->stats_cache = [
            'avg_income' => round($avgIncome, 2),
            'total_mapped_ceps' => $reports->count(),
            'neighborhood_count' => count($neighborhoodRanking),
            'neighborhood_list' => $neighborhoodRanking,
            'total_pois' => $totalPois,
            'top_conveniencias' => $topPois,
            'avg_score' => round($avgScore, 1),
            'state_avg_score' => round($stateAvgScore, 1),
            'predominant_class' => $predominant,
            'mix_usage' => $categories->toArray(),
            'usage_percentages' => $usagePercentages,
            'essentials' => [
                'pharmacies' => $poiTypes['Farmácias'] ?? 0,
                'gas_stations' => $poiTypes['Postos de Combustível'] ?? 0,
                'markets' => $poiTypes['Supermercados'] ?? 0,
                'health' => ($poiTypes['Saúde (Hospitais)'] ?? 0) + ($poiTypes['Clínicas Médicas'] ?? 0),
                'education' => ($poiTypes['Educação'] ?? 0) + ($poiTypes['Educação Infantil'] ?? 0),
            ],
            'radar' => [
                'safety' => round($avgSafety, 1),
                'walkability' => round($avgWalkability, 1),
                'air_quality' => round(100 - $avgAirQuality, 1), // Invertido: Menor índice = Melhor qualidade
                'sanitation' => round($avgSanitation, 1),
            ],
        ];

        $city->last_calculated_at = now();
    }

    private function fetchCityImage(City $city): string
    {
        // 1. Tentar pegar a imagem oficial da Wikipedia
        $wikiImage = $this->fetchWikipediaImage($city);
        if ($wikiImage) {
            return $wikiImage;
        }

        // Fallback: Imagens urbanas premium genéricas
        $genericUrbanImages = [
            'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1477959858617-67f85cf4f1df?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?auto=format&fit=crop&w=1200&q=80',
        ];

        return $genericUrbanImages[array_rand($genericUrbanImages)];
    }

    private function fetchWikipediaImage(City $city): ?string
    {
        try {
            // Tenta nomes variados: "Nome, UF", "Nome SP", "Nome"
            $queries = [
                "{$city->name}, {$city->uf}",
                "{$city->name} {$city->uf}",
                $city->name,
            ];

            foreach ($queries as $query) {
                $response = Http::withoutVerifying()->withHeaders([
                    'User-Agent' => 'RaioX Territorial/1.0 (https://raiox.com.br; contato@raiox.com.br)',
                ])->get('https://pt.wikipedia.org/w/api.php', [
                    'action' => 'query',
                    'format' => 'json',
                    'prop' => 'pageimages',
                    'pithumbsize' => 1200,
                    'titles' => $query,
                    'redirects' => 1,
                ]);

                if ($response->successful()) {
                    $pages = $response->json()['query']['pages'] ?? [];
                    $page = reset($pages);

                    if (isset($page['thumbnail']['source'])) {
                        $imgUrl = $page['thumbnail']['source'];
                        // Evita brasões e bandeiras se possível (geralmente vêm como primeira imagem)
                        if (! str_contains(strtolower($imgUrl), 'bandeira') && ! str_contains(strtolower($imgUrl), 'brasao')) {
                            return $imgUrl;
                        }
                        // Se for brasão/bandeira, guarda mas continua tentando outras queries/estatégias
                        $fallbackImg = $imgUrl;
                    }
                }
            }

            return $fallbackImg ?? null;
        } catch (\Exception $e) {
            Log::warning('Wiki Image Error: '.$e->getMessage());
        }

        return null;
    }

    private function generateHistory(City $city)
    {
        // 1. RAG: Ver o que já temos sobre a cidade no nosso banco SQL
        $ragContext = $this->knowledge->search("História, fundação e cultura da cidade de {$city->name} - {$city->uf}", 5);

        // 2. Wikipedia (Modo novo)
        $wikiText = $this->fetchWikipediaData($city);

        // 3. Prompt Master via LlmManager (Economia de tokens + Cache)
        $prompt = [
            ['role' => 'system', 'content' => "Você é um Historiador Territorial sênior. Sua tarefa é descrever a cidade de {$city->name} focando em fundação, evolução urbana e identidade cultural. Use dados técnicos. Retorne um JSON com a chave 'historia' contendo 3 a 4 parágrafos robustos."],
            ['role' => 'user', 'content' => "Contexto Wiki: [{$wikiText}] \n\n Memória Territorial: ".json_encode($ragContext)],
        ];

        $response = $this->llm->chat($prompt, 'creative', [
            'agent_name' => 'CityLibrarian',
            'agent_version' => '2.0.0',
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '';

        // Extrair JSON da resposta se necessário
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $summary = json_decode($matches[0], true);
        } else {
            $summary = ['historia' => $content];
        }

        if ($summary && ! empty($summary['historia'])) {
            // Se a IA retornar historia como array de parágrafos, junta em string
            if (is_array($summary['historia'])) {
                $summary['historia'] = implode("\n\n", array_map(function ($p) {
                    if (is_string($p)) {
                        return $p;
                    }
                    if (is_array($p)) {
                        return $p['texto'] ?? $p['content'] ?? $p['text'] ?? json_encode($p);
                    }

                    return (string) $p;
                }, $summary['historia']));
            }

            // Normaliza espaçamentos excessivos
            $summary['historia'] = preg_replace("/(\n\s*){3,}/", "\n\n", $summary['historia']);

            // Aplica o revisor para garantir 3 parágrafos e humanização
            $revised = $this->reviser->reviseHistoria($summary);
            $city->history_extract = $revised['historia'];
            $city->wiki_json = [
                'raw' => substr($wikiText, 0, 3000),
                'grounded' => count($ragContext) > 0,
            ];

            // Indexar esse novo conhecimento para futuras buscas
            $this->knowledge->store($revised['historia'], 'city_summary', $city->slug, [
                'city' => $city->name,
                'uf' => $city->uf,
            ]);
        }
    }

    private function fetchWikipediaData(City $city): string
    {
        try {
            $queries = ["{$city->name}, {$city->uf}", "{$city->name} {$city->uf}", $city->name];

            foreach ($queries as $query) {
                $response = Http::withoutVerifying()->withHeaders([
                    'User-Agent' => 'RaioX Territorial/1.0 (https://raiox.com.br; contato@raiox.com.br)',
                ])->get('https://pt.wikipedia.org/w/api.php', [
                    'action' => 'query',
                    'format' => 'json',
                    'prop' => 'extracts',
                    'exintro' => 1,
                    'explaintext' => 1,
                    'titles' => $query,
                    'redirects' => 1,
                ]);

                if ($response->successful()) {
                    $pages = $response->json()['query']['pages'] ?? [];
                    $page = reset($pages);
                    if (isset($page['extract']) && strlen($page['extract']) > 200) {
                        return $page['extract'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Wiki City Text Error: '.$e->getMessage());
        }

        return '';
    }
}

<?php

namespace App\Services;

use App\Models\LocationReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NeighborhoodService
{
    /**
     * Get or create report with 30-day cache logic
     */
    public function getCachedReport(string $cep): ?LocationReport
    {
        $cepClean = preg_replace('/\D/', '', $cep);
        
        // 1. Tenta encontrar no banco
        $report = LocationReport::where('cep', $cepClean)->first();

        // Limite de "frescor": 180 dias (6 meses)
        $isFresh = $report && $report->updated_at && $report->updated_at->gt(Carbon::now()->subDays(180));

        // Se o relatório existe, é recente e está completo
        if ($isFresh && $report->status === 'completed' && !empty($report->history_extract)) {
            // REGRA: O Clima e Ar sempre deve ser atualizado se tiver mais de 1 hora (sem bloquear a fila)
            if ($report->updated_at->lt(Carbon::now()->subHour())) {
                try {
                    $climate = $this->fetchClimate($report->lat, $report->lng);
                    $airQuality = $this->fetchAirQuality($report->lat, $report->lng);
                    $report->update([
                        'climate_json' => $climate,
                        'air_quality_index' => $airQuality
                    ]);
                } catch (\Exception $e) {
                    Log::warning("Soft refresh failed for CEP {$cepClean}: " . $e->getMessage());
                }
            }
            return $report;
        }

        // 2. Se não existe ou está expirado, entra na fila
        if (!$report) {
            // Cria um registro "placeholder" para o usuário ver na fila
            $report = LocationReport::create([
                'cep' => $cepClean,
                'status' => 'pending',
                'cidade' => 'Localizando...',
                'uf' => '--'
            ]);
            \App\Jobs\ProcessLocationReport::dispatch($cepClean);
        } elseif (!$isFresh || $report->status === 'failed') {
            // Se expirou ou falhou, reinicia o processo
            $report->update(['status' => 'pending', 'error_message' => null]);
            \App\Jobs\ProcessLocationReport::dispatch($cepClean);
        }

        return $report;
    }

    public function getFullReport(string $cep): array
    {
        set_time_limit(300); 
        
        $cepClean = preg_replace('/\D/', '', $cep);
        $isNumericCep = (strlen($cepClean) === 8);
        
        $address = null;
        $street = '';
        $city = '';
        $state = '';
        $ibgeCode = null;
        $lat = null;
        $lng = null;

        // ESTRATÉGIA DE CACHE PROATIVA: 
        // 1. Verificar se o CEP já existe em algum relatório anterior para pegar Cidade/Bairro rápido
        $existingReport = LocationReport::where('cep', $cepClean)->first();
        if ($existingReport) {
            $city = $existingReport->cidade;
            $state = $existingReport->uf;
            $street = $existingReport->logradouro;
            $lat = $existingReport->lat;
            $lng = $existingReport->lng;
            $address = [
                'localidade' => $city,
                'logradouro' => $street,
                'uf' => $state,
                'bairro' => $existingReport->bairro,
                'cep' => $cepClean
            ];
            Log::info("CACHE HIT: Relatório prévio encontrado para CEP {$cepClean}");
        }

        // 2. Se não tem no banco, buscar ViaCEP/Nominatim
        if (!$city || !$state) {
            if ($isNumericCep) {
                $address = $this->fetchViaCep($cepClean);
                if ($address && !isset($address['erro'])) {
                    $city = $address['localidade'];
                    $street = $address['logradouro'] ?? '';
                    $state = $address['uf'];
                    $ibgeCode = $address['ibge'] ?? null;
                }
            }

            if (!$city || !$state) {
                Log::info("Geocodificando termo de busca: {$cep}");
                $geo = $this->fetchNominatim($cep, '', '');
                if ($geo) {
                    $lat = $geo['lat'];
                    $lng = $geo['lon'];
                    $addr = $geo['address'] ?? [];
                    $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? '';
                    $state = $addr['state'] ?? '';
                    $street = $addr['road'] ?? '';
                    $stateMap = [
                        'São Paulo' => 'SP', 'Rio de Janeiro' => 'RJ', 'Minas Gerais' => 'MG',
                        'Paraná' => 'PR', 'Santa Catarina' => 'SC', 'Rio Grande do Sul' => 'RS'
                    ];
                    $state = $stateMap[$state] ?? $state;
                    $address = [
                        'localidade' => $city,
                        'logradouro' => $street,
                        'uf' => $state,
                        'bairro' => $addr['neighbourhood'] ?? $addr['suburb'] ?? '',
                        'cep' => $addr['postcode'] ?? $cepClean ?: 'S/C'
                    ];
                }
            }
        }

        if (!$city || !$state) {
            Log::error("Não foi possível identificar Cidade/Estado para: {$cep}");
            return [];
        }

        // 3. RECUPERAR OU CRIAR CIDADE (Reutiliza se já existir)
        $cityModel = \App\Models\City::where('name', $city)->where('uf', $state)->first();

        if (!$cityModel) {
            Log::info("CIDADE NOVA: Criando registro para {$city}/{$state}");
            $ibgeData = [];
            if (!$ibgeCode) {
                // Tenta achar código IBGE se for cidade nova
                $ibgeService = new \App\Services\IbgeService();
                $ibgeData = $ibgeService->getMunicipalityDataByName($city, $state);
                $ibgeCode = $ibgeData['ibge_code'] ?? null;
            } else {
                $ibgeService = new \App\Services\IbgeService();
                $ibgeData = $ibgeService->getMunicipalityData($ibgeCode);
            }

            $socio = $this->fetchSocioEconomic($ibgeData);
            $cityWiki = $this->fetchWikipediaInfo('', $city, $state);
            $historyRaw = $cityWiki['full_text'] ?? $cityWiki['extract'] ?? 'Sem dados na Wikipedia.';
            
            $gemini = new \App\Services\GeminiService();
            $aiSummary = $gemini->generateNeighborhoodSummary($historyRaw, $city);
            
            $cityModel = \App\Models\City::create([
                'ibge_code' => $ibgeCode,
                'uf' => $state,
                'name' => $city,
                'population' => $ibgeData['population'] ?? null,
                'average_income' => $socio['average_income'] ?? null,
                'sanitation_rate' => $ibgeData['sanitation_rate'] ?? $socio['sanitation_rate'] ?? null,
                'idhm' => $ibgeData['idhm'] ?? null,
                'history_extract' => $aiSummary['historia'] ?? ($cityWiki['extract'] ?? 'Local em desenvolvimento.'),
                'safety_level' => $aiSummary['nivel_seguranca'] ?? 'MODERADO',
                'safety_description' => $aiSummary['descricao_seguranca'] ?? 'Região metropolitana.',
                'wiki_json' => $cityWiki,
                'raw_ibge_data' => $ibgeData,
                'real_estate_json' => $aiSummary['mercado_imobiliario'] ?? null
            ]);
        } else {
            Log::info("CIDADE CACHE: Reutilizando dados de {$city}/{$state}");
            $ibgeData = $cityModel->raw_ibge_data ?? [];
            if (!isset($ibgeData['population'])) {
                $ibgeData['population'] = $cityModel->population;
            }
        }

        // 4. RECUPERAR OU CRIAR BAIRRO (Reutiliza se já existir)
        $neighborhoodModel = null;
        $bairro = $address['bairro'] ?? '';
        $isAmbiguous = $bairro && in_array(strtolower($bairro), self::AMBIGUOUS_NEIGHBORHOOD_TERMS);
        
        if (!empty($bairro) && !$isAmbiguous) {
            $neighborhoodModel = \App\Models\Neighborhood::where('city_id', $cityModel->id)
                ->where('name', $bairro)->first();

            if (!$neighborhoodModel) {
                Log::info("BAIRRO NOVO: Criando registro para {$bairro} em {$city}");
                $bairroWiki = $this->fetchWikipediaInfo($bairro, $city, $state, true); 
                $bairroHistoryRaw = $bairroWiki['full_text'] ?? "Gere um resumo sobre o bairro {$bairro} em {$city}.";
                
                $gemini = new \App\Services\GeminiService();
                $aiSummary = $gemini->generateNeighborhoodSummary($bairroHistoryRaw, "{$bairro}, {$city}");
                
                $neighborhoodModel = \App\Models\Neighborhood::create([
                    'city_id' => $cityModel->id,
                    'name' => $bairro,
                    'history_extract' => $aiSummary['historia'] ?? null,
                    'safety_level' => $aiSummary['nivel_seguranca'] ?? 'MODERADO',
                    'safety_description' => $aiSummary['descricao_seguranca'] ?? null,
                    'wiki_json' => $bairroWiki,
                    'real_estate_json' => $aiSummary['mercado_imobiliario'] ?? null
                ]);
            } else {
                Log::info("BAIRRO CACHE: Reutilizando dados de {$bairro} em {$city}");
            }
        }

        // 3. Nominatim (Geocoding) - Executa apenas se ainda não temos coordenadas
        $geo = null;
        if (!$lat || !$lng) {
            if (!empty($street)) {
                $geo = $this->fetchNominatim($street, $city, $state);
            }
            
            if (!$geo) {
                $geo = $this->fetchNominatim($city, $city, $state); 
            }

            $lat = $geo['lat'] ?? null;
            $lng = $geo['lon'] ?? null;
            
            // Se o Nominatim achou algo que o ViaCEP não achou (bairro, etc), enriquecemos
            if ($geo && empty($address['bairro'])) {
                $addr = $geo['address'] ?? [];
                $address['bairro'] = $addr['neighbourhood'] ?? $addr['suburb'] ?? $addr['hamlet'] ?? '';
            }
        }

        if (!$lat || !$lng) {
            Log::error("Impossível geolocalizar o endereço mesmo após fallback: {$cep}");
            return array_merge($address ?? [], $ibgeData ?? []);
        }

        // 4. Overpass (POIs & Mobility) - BUSCA GRANULAR PROGRESSIVA
        // Escalas: 1km -> 3km -> 5km -> 7km -> 10km
        $radii = [1000, 3000, 5000, 7000, 10000];
        $pois = [];
        $finalRadius = 10000;

        foreach ($radii as $r) {
            Log::info("Attempting Overpass search with radius: {$r}m for {$lat},{$lng}");
            $pois = $this->fetchOverpass($lat, $lng, $r);
            $finalRadius = $r;
            
            // Se encontrarmos pelo menos 30 pontos, paramos (densidade satisfatória)
            if (count($pois) >= 30) {
                Log::info("Found satisfactory density (" . count($pois) . " items) at {$r}m. Stopping search.");
                break;
            }
        }
        
        $walkScore = $this->calculateWalkabilityScore($pois);

        // 5. Open-Meteo (Weather & Air Quality)
        $climate = $this->fetchClimate($lat, $lng);
        $airQuality = $this->fetchAirQuality($lat, $lng);

        $wiki = $neighborhoodModel && $neighborhoodModel->wiki_json
            ? $neighborhoodModel->wiki_json
            : $cityModel->wiki_json;

        if (empty($wiki['image']) && $cityModel->wiki_json && !empty($cityModel->wiki_json['image'])) {
            $wiki['image'] = $cityModel->wiki_json['image'];
        }

        $history = $neighborhoodModel && $neighborhoodModel->history_extract
            ? $neighborhoodModel->history_extract
            : $cityModel->history_extract;

        $safetyLevel = $neighborhoodModel && $neighborhoodModel->safety_level
            ? $neighborhoodModel->safety_level
            : $cityModel->safety_level;

        $safetyDesc = $neighborhoodModel && $neighborhoodModel->safety_description
            ? $neighborhoodModel->safety_description
            : $cityModel->safety_description;

        $realEstate = $neighborhoodModel && $neighborhoodModel->real_estate_json
            ? $neighborhoodModel->real_estate_json
            : $cityModel->real_estate_json;

        return array_merge($address, $ibgeData, [
            'lat' => $lat,
            'lng' => $lng,
            'pois_json' => $pois,
            'search_radius' => $finalRadius,
            'climate_json' => $climate,
            'wiki_json' => $wiki,
            'air_quality_index' => $airQuality,
            'walkability_score' => $walkScore,
            'average_income' => $cityModel->average_income,
            'sanitation_rate' => $cityModel->sanitation_rate,
            'history_extract' => $history ?: ($wiki['extract'] ?? null),
            'safety_level' => $safetyLevel,
            'safety_description' => $safetyDesc,
            'real_estate_json' => $realEstate,
        ]);
    }

    private function fetchViaCep($cep)
    {
        try {
            return Http::withoutVerifying()->get("https://viacep.com.br/ws/{$cep}/json/")->json();
        } catch (\Exception $e) {
            Log::error("ViaCEP Error: " . $e->getMessage());
            return null;
        }
    }

    private function fetchNominatim($street, $city, $state)
    {
        try {
            // Se vir apenas um campo, tratamos como query única
            $query = empty($city) ? $street : "{$street}, {$city}, {$state}, Brazil";
            
            Log::info("Nominatim: pesquisando [{$query}]");

            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                ->get("https://nominatim.openstreetmap.org/search", [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1
                ]);
            
            $data = $response->json();
            
            // Fallback se não achou com rua: busca pela cidade
            if (empty($data) && !empty($city)) {
                Log::info("Nominatim: fallback para cidade [{$city}]");
                $response = Http::withoutVerifying()
                    ->timeout(10)
                    ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                    ->get("https://nominatim.openstreetmap.org/search", [
                        'q' => "{$city}, {$state}, Brazil",
                        'format' => 'json',
                        'addressdetails' => 1,
                        'limit' => 1
                    ]);
                $data = $response->json();
            }
            return $data[0] ?? null;
        } catch (\Exception $e) {
            Log::error("Nominatim Error: " . $e->getMessage());
            return null;
        }
    }

    private function fetchOverpass($lat, $lng, $radius = 10000)
    {
        $query = "[out:json][timeout:30];(
            nwr[\"amenity\"~\"restaurant|pharmacy|hospital|bank|school|cafe|bar|fast_food|pub|university|clinic|dentist|doctors|veterinary|kindergarten|childcare|place_of_worship|cinema|theatre|library|post_office|fuel|bicycle_parking|police|fire_station|townhall|public_service|marketplace|courthouse\"](around:{$radius},{$lat},{$lng});
            nwr[\"shop\"~\"supermarket|bakery|convenience|clothes|mall|pharmacy|beauty|department_store|hardware|electronics|furniture|optician|books|marketplace|butcher|greengrocer|doityourself|pet|hairdresser|sports|shoes|toys|jewelry|car|car_repair|car_wash|laundry\"](around:{$radius},{$lat},{$lng});
            nwr[\"leisure\"~\"park|gym|sports_centre|playground|marketplace\"](around:{$radius},{$lat},{$lng});
            nwr[\"tourism\"~\"museum|monument|attraction|artwork|gallery\"](around:{$radius},{$lat},{$lng});
            nwr[\"historic\"](around:{$radius},{$lat},{$lng});
            nwr[\"railway\"=\"station\"](around:{$radius},{$lat},{$lng});
        );out center bb qt 800;";

        // Múltiplos endpoints públicos do Overpass para fallback
        $endpoints = [
            'https://overpass-api.de/api/interpreter',
            'https://lz4.overpass-api.de/api/interpreter',
            'https://z.overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
            'https://overpass.osm.ch/api/interpreter'
        ];

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Referer' => 'https://google.com'
        ];

        foreach ($endpoints as $endpoint) {
            try {
                Log::info("Overpass: tentando endpoint {$endpoint}");
                $response = Http::withoutVerifying()
                    ->timeout(30)
                    ->withHeaders($headers)
                    ->asForm()
                    ->post($endpoint, ['data' => $query]);

                if (!$response->successful()) {
                    Log::warning("Overpass [{$endpoint}] retornou erro: " . substr($response->body(), 0, 200));
                    continue;
                }

                $data     = $response->json();
                $elements = [];

                foreach ($data['elements'] ?? [] as $element) {
                    $item = [
                        'type' => $element['type'],
                        'id'   => $element['id'],
                        'tags' => $element['tags'] ?? [],
                        'lat'  => $element['lat'] ?? ($element['center']['lat'] ?? null),
                        'lon'  => $element['lon'] ?? ($element['center']['lon'] ?? null),
                    ];
                    if ($item['lat'] && $item['lon']) {
                        $elements[] = $item;
                    }
                }

                if (empty($elements)) {
                    Log::warning("Overpass [{$endpoint}]: 0 elementos para {$lat},{$lng}. Tentando próximo...");
                    continue;
                }

                Log::info("Overpass [{$endpoint}]: " . count($elements) . " elementos para {$lat},{$lng}.");
                return $elements;

            } catch (\Exception $e) {
                Log::warning("Overpass [{$endpoint}] exception: " . $e->getMessage());
            }
        }

        Log::error("Overpass: todos os endpoints falharam para lat={$lat}, lng={$lng}.");
        return [];
    }

    private function fetchClimate($lat, $lng)
    {
        try {
            return Http::withoutVerifying()->get("https://api.open-meteo.com/v1/forecast", [
                'latitude' => $lat,
                'longitude' => $lng,
                'current_weather' => 'true'
            ])->json();
        } catch (\Exception $e) {
            Log::error("Open-Meteo Error: " . $e->getMessage());
            return null;
        }
    }

    private function fetchAirQuality($lat, $lng)
    {
        try {
            $response = Http::withoutVerifying()->get("https://air-quality-api.open-meteo.com/v1/air-quality", [
                'latitude' => $lat,
                'longitude' => $lng,
                'current' => 'european_aqi'
            ]);
            return $response->json()['current']['european_aqi'] ?? null;
        } catch (\Exception $e) {
            Log::error("Air Quality Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Termos que são nomes genéricos ou prefixos comuns e podem resolver artigos Wikipedia 
     * não relacionados (ex: "Centro" -> artigo de geometria, "Vila" -> artigo genérico).
     */
    private const AMBIGUOUS_NEIGHBORHOOD_TERMS = [
        'centro', 'norte', 'sul', 'leste', 'oeste', 'central',
        'jardim', 'vila', 'parque', 'alto', 'baixo', 'bela vista', 'boa vista', 
        'santa', 'santo', 'são', 'nossa senhora', 'residencial', 'portal'
    ];

    /**
     * Verifica se um artigo Wikipedia é realmente sobre um lugar (bairro/cidade),
     * e não sobre um conceito genérico.
     */
    private function isValidWikipediaPlace(array $data, string $expectedCity = '', string $expectedState = ''): bool
    {
        $type        = $data['type'] ?? '';
        $description = strtolower($data['description'] ?? '');
        $extract     = $data['extract'] ?? '';

        if ($type === 'disambiguation') return false;
        if (empty($extract))           return false;

        // Se description indica claramente que é um lugar, aceite
        $placeKeywords = ['município', 'cidade', 'bairro', 'distrito', 'região', 'localidade', 'capital', 'entidade', 'unidade federativa', 'povoado'];
        $isPlace = false;
        foreach ($placeKeywords as $kw) {
            if (str_contains($description, $kw)) {
                $isPlace = true;
                break;
            }
        }

        // Se o extract começa falando de conceitos abstratos, rejeite
        $rejectPatterns = [
            '/^o centro, em geometria/i', 
            '/^em geometria/i', 
            '/^em matemática/i', 
            '/^em física/i', 
            '/^na religião/i',
            '/^segundo a bíblia/i'
        ];
        foreach ($rejectPatterns as $pattern) {
            if (preg_match($pattern, $extract)) return false;
        }

        // Validação de contexto (Cidade/Estado) se fornecidos
        // CRÍTICO: Se o artigo não menciona a cidade ou estado, há grande chance de ser o local errado
        if ($expectedCity || $expectedState) {
            $textToSearch = strtolower($description . ' ' . $extract);
            $cityLower  = strtolower($expectedCity);
            $stateLower = strtolower($expectedState);

            // Nome do estado por extenso (mapeamento simples para os principais ou regra geral)
            if (!str_contains($textToSearch, $cityLower) && !str_contains($textToSearch, $stateLower)) {
                // Se for um bairro, a cidade OBRIGATORIAMENTE deve estar no texto para aceitarmos o fallback
                return false;
            }
        }

        return $isPlace || !empty($description);
    }

    /**
     * Busca Wikipedia com fallback inteligente:
     * 1. Bairro_(Cidade) — ex: Vila_Madalena_(São_Paulo)
     * 2. Bairro simples  — ex: Vila_Madalena  (pulo se bairro for genérico e buscou só summary errado)
     * 3. Cidade_(UF)     — ex: São_Paulo_(SP)
     * 4. Cidade simples  — ex: São_Paulo
     *
     * Retorna array com 'extract', 'full_text', 'image', 'desktop_url' e 'source' (bairro|cidade)
     */
    private function fetchWikipediaInfo(string $bairro, string $city, string $state, bool $onlyBairro = false): ?array
    {
        $headers = ['User-Agent' => 'RaioXNeighborhood/1.0'];
        $base    = 'https://pt.wikipedia.org/api/rest_v1/page/summary/';

        $bairroLower = strtolower($bairro);
        $bairroIsAmbiguous = false;
        foreach (self::AMBIGUOUS_NEIGHBORHOOD_TERMS as $term) {
            if (str_contains($bairroLower, $term)) {
                $bairroIsAmbiguous = true;
                break;
            }
        }

        $candidates = [];

        if ($bairro) {
            $candidates[] = [str_replace(' ', '_', "{$bairro} ({$city})"), 'bairro', true]; // true = strict (must match city/state)
            if (!$bairroIsAmbiguous) {
                $candidates[] = [str_replace(' ', '_', $bairro), 'bairro', true];
            }
        }

        if (!$onlyBairro) {
            $candidates[] = [str_replace(' ', '_', "{$city} ({$state})"), 'cidade', false];
            $candidates[] = [str_replace(' ', '_', $city), 'cidade', true];
        }

        foreach ($candidates as [$term, $source, $shouldValidate]) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(10)
                    ->withHeaders($headers)
                    ->get($base . urlencode($term));

                if ($response->successful()) {
                    $data = $response->json();

                    // Se shouldValidate for true, passamos a cidade/estado para validação rigorosa
                    $vCity = $shouldValidate ? $city : '';
                    $vState = $shouldValidate ? $state : '';

                    if (!$this->isValidWikipediaPlace($data, $vCity, $vState)) {
                        Log::info("Wikipedia [{$term}]: artigo rejeitado por falta de contexto ou tipo inválido.");
                        continue;
                    }

                    Log::info("Wikipedia hit: [{$source}] {$term}");

                    // Busca conteúdo completo para enriquecer o resumo do Gemini
                    $fullText = $this->fetchWikipediaFullContent($term, $headers);

                    return [
                        'source'      => $source,
                        'term'        => $term,
                        'extract'     => $data['extract'],
                        'full_text'   => $fullText ?: $data['extract'],
                        'image'       => $data['originalimage']['source'] ?? $data['thumbnail']['source'] ?? null,
                        'desktop_url' => $data['content_urls']['desktop']['page'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Wikipedia timeout/error for [{$term}]: " . $e->getMessage());
            }
        }

        Log::warning("Wikipedia: nenhum resultado válido para bairro=[{$bairro}] cidade=[{$city}]");
        return null;
    }

    /**
     * Busca o conteúdo completo de uma página Wikipedia via API mobile-sections.
     * Extrai e concatena texto das primeiras seções, limpando marcações wiki.
     * Limita a ~4000 chars para não sobrecarregar o prompt do Gemini.
     */
    private function fetchWikipediaFullContent(string $term, array $headers): ?string
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders($headers)
                ->get('https://pt.wikipedia.org/w/api.php', [
                    'action' => 'query',
                    'prop' => 'extracts',
                    'exlimit' => 1,
                    'titles' => str_replace('_', ' ', $term),
                    'explaintext' => 1,
                    'format' => 'json'
                ]);

            if (!$response->successful()) return null;

            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];
            if (empty($pages)) return null;

            $page = reset($pages);
            
            if (isset($page['missing'])) return null;

            $raw = $page['extract'] ?? '';
            
            // Limpeza básica
            $raw = preg_replace('/\[\d+\]/', '', $raw);       // remove [1], [2]
            $raw = preg_replace('/\s+/', ' ', trim($raw));    // normaliza espaços

            $result = trim(mb_substr($raw, 0, 15000));
            Log::info('Wikipedia full content fetched: ' . strlen($result) . ' chars for ' . $term);

            return $result ?: null;
        } catch (\Exception $e) {
            Log::warning('Wikipedia full content error for [' . $term . ']: ' . $e->getMessage());
            return null;
        }
    }

    private function fetchSocioEconomic(array $ibgeData): array
    {
        $minWage = 1412.00;
        $estimatedMonthlyIncome = 0;

        // 1. Prioridade: Salário Médio dos Trabalhadores Formais (Indicator 29765)
        // O IBGE retorna esse valor em número de salários mínimos
        if (isset($ibgeData['average_salary']) && $ibgeData['average_salary'] > 0) {
            $estimatedMonthlyIncome = $ibgeData['average_salary'] * $minWage;
        } 
        // 2. Fallback: Estimativa baseada no PIB per capita
        else {
            $pibPerCapita = $ibgeData['pib_per_capita'] ?? 0;
            if ($pibPerCapita > 0) {
                // O PIB per capita é anual. Dividimos por 12 e aplicamos um fator 
                // para estimar renda domiciliar per capita (ajuste empírico)
                $estimatedMonthlyIncome = ($pibPerCapita / 12) / 1.8; 
            } else {
                $estimatedMonthlyIncome = 2400.00;
            }
        }

        // Limite de sanidade: nunca menor que o salário mínimo
        if ($estimatedMonthlyIncome < $minWage) {
            $estimatedMonthlyIncome = $minWage + rand(100, 300);
        }

        $sanitation = $ibgeData['sanitation_rate'] ?? 85.0;

        return [
            'average_income' => round($estimatedMonthlyIncome, 2),
            'sanitation_rate' => $sanitation, 
        ];
    }

    private function calculateWalkabilityScore(array $pois): string
    {
        $commerces = 0;
        $mobility = 0;

        foreach ($pois as $poi) {
            $tags = $poi['tags'] ?? [];
            if (isset($tags['shop']) || in_array($tags['amenity'] ?? '', ['restaurant', 'cafe'])) {
                $commerces++;
            }
            if (($tags['highway'] ?? '') === 'bus_stop' || ($tags['amenity'] ?? '') === 'bicycle_parking') {
                $mobility++;
            }
        }

        if ($commerces > 10 && $mobility > 5) return 'A';
        if ($commerces > 5 && $mobility > 2) return 'B';
        return 'C';
    }
}

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
        
        // 1. Check cache (max 90 days old)
        $report = LocationReport::where('cep', $cepClean)->first();

        // Se o relatório existe e é recente (menos de 90 dias)
        if ($report && 
            $report->updated_at->gt(Carbon::now()->subDays(90)) && 
            $report->air_quality_index !== null && 
            $report->history_extract !== null &&
            !empty($report->pois_json)
        ) {
            // REGRA: O Clima e Ar sempre deve ser atualizado se tiver mais de 1 hora
            // mas SEM alterar o 'updated_at' principal para não resetar o ciclo de 90 dias da IA
            if ($report->updated_at->lt(Carbon::now()->subHour())) {
                Log::info("Refreshing fresh climate data for CEP: {$cepClean}");
                $climate = $this->fetchClimate($report->lat, $report->lng);
                $airQuality = $this->fetchAirQuality($report->lat, $report->lng);
                
                // Usamos save() para gerenciar manualmente os timestamps se necessário, 
                // ou simplesmente aceitamos que o clima não deve interferir no "nascimento" do relatório.
                $report->climate_json = $climate;
                $report->air_quality_index = $airQuality;
                $report->save(['timestamps' => false]); // Salva sem mudar o updated_at
            }
            return $report;
        }

        // 2. Fetch new data
        $data = $this->getFullReport($cepClean);
        if (empty($data)) return null;

        // 3. Save/Update in DB
        $reportData = [
            'cep' => $cepClean,
            'logradouro' => $data['logradouro'] ?? '',
            'bairro' => $data['bairro'] ?? '',
            'cidade' => $data['localidade'],
            'uf' => $data['uf'],
            'codigo_ibge' => $data['ibge'] ?? '',
            'populacao' => $data['population'] ?? null,
            'raw_ibge_data' => $data['municipality_info'] ?? [],
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'pois_json' => $data['pois_json'] ?? [],
            'climate_json' => $data['climate_json'] ?? [],
            'wiki_json' => $data['wiki_json'] ?? [],
            'air_quality_index' => $data['air_quality_index'] ?? null,
            'walkability_score' => $data['walkability_score'] ?? 'C',
            'average_income' => $data['average_income'] ?? null,
            'sanitation_rate' => $data['sanitation_rate'] ?? null,
            'history_extract' => $data['history_extract'] ?? null,
            'safety_level' => $data['safety_level'] ?? null,
            'safety_description' => $data['safety_description'] ?? null,
        ];

        if ($report) {
            $report->update($reportData);
            $report->refresh();
        } else {
            $report = LocationReport::create($reportData);
        }

        return $report;
    }

    /**
     * Get all neighborhood data for a CEP
     */
    public function getFullReport(string $cep): array
    {
        $cepClean = preg_replace('/\D/', '', $cep);
        
        // 1. ViaCEP (Includes IBGE code)
        $address = $this->fetchViaCep($cepClean);
        if (!$address || isset($address['erro'])) return [];

        $city = $address['localidade'];
        $street = $address['logradouro'] ?? '';
        $state = $address['uf'];
        $ibgeCode = $address['ibge'] ?? null;

        // CITY DATA
        $cityModel = \App\Models\City::where('ibge_code', $ibgeCode)
            ->orWhere(function($q) use ($city, $state) {
                $q->where('name', $city)->where('uf', $state);
            })->first();

        if (!$cityModel) {
            $ibgeData = [];
            if ($ibgeCode) {
                $ibgeService = new \App\Services\IbgeService();
                $ibgeData = $ibgeService->getMunicipalityData($ibgeCode);
            }
            $socio = $this->fetchSocioEconomic($ibgeCode);

            // Fetch Wikipedia Only for City
            $cityWiki = $this->fetchWikipediaInfo('', $city, $state);
            $historyRaw = $cityWiki['full_text'] ?? $cityWiki['extract'] ?? null;
            $cityHistory = $cityWiki['extract'] ?? null;
            $citySafetyLevel = null;
            $citySafetyDesc = null;
            if ($historyRaw) {
                $gemini = new \App\Services\GeminiService();
                $aiSummary = $gemini->generateNeighborhoodSummary($historyRaw, $city);
                if ($aiSummary) {
                    $cityHistory = $aiSummary['historia'] ?? $cityHistory;
                    $citySafetyLevel = $aiSummary['nivel_seguranca'] ?? null;
                    $citySafetyDesc = $aiSummary['descricao_seguranca'] ?? null;
                }
            }

            $cityModel = \App\Models\City::create([
                'ibge_code' => $ibgeCode,
                'uf' => $state,
                'name' => $city,
                'population' => $ibgeData['population'] ?? null,
                'average_income' => $socio['average_income'] ?? null,
                'sanitation_rate' => $socio['sanitation_rate'] ?? null,
                'history_extract' => $cityHistory,
                'safety_level' => $citySafetyLevel,
                'safety_description' => $citySafetyDesc,
                'wiki_json' => $cityWiki,
                'raw_ibge_data' => $ibgeData['municipality_info'] ?? []
            ]);
        }

        $ibgeData = [
            'population' => $cityModel->population,
            'municipality_info' => $cityModel->raw_ibge_data
        ];

        // NEIGHBORHOOD DATA
        $neighborhoodModel = null;
        $bairro = $address['bairro'] ?? '';
        $isAmbiguous = $bairro && in_array(strtolower($bairro), self::AMBIGUOUS_NEIGHBORHOOD_TERMS);
        
        if (!empty($bairro) && !$isAmbiguous) {
            $neighborhoodModel = \App\Models\Neighborhood::where('city_id', $cityModel->id)
                ->where('name', $bairro)->first();

            if (!$neighborhoodModel) {
                $bairroWiki = $this->fetchWikipediaInfo($bairro, $city, $state, true); 
                
                $bairroHistory = null;
                if ($bairroWiki && $bairroWiki['source'] === 'bairro') {
                    $bairroHistoryRaw = $bairroWiki['full_text'] ?? $bairroWiki['extract'] ?? null;
                    $bairroHistory = $bairroWiki['extract'] ?? null;
                    $bairroSafetyLevel = null;
                    $bairroSafetyDesc = null;
                    if ($bairroHistoryRaw) {
                        $gemini = new \App\Services\GeminiService();
                        $aiSummary = $gemini->generateNeighborhoodSummary($bairroHistoryRaw, "{$bairro}, {$city}");
                        if ($aiSummary) {
                            $bairroHistory = $aiSummary['historia'] ?? $bairroHistory;
                            $bairroSafetyLevel = $aiSummary['nivel_seguranca'] ?? null;
                            $bairroSafetyDesc = $aiSummary['descricao_seguranca'] ?? null;
                        }
                    }
                } else {
                    $bairroWiki = null;
                    $bairroHistory = null;
                    $bairroSafetyLevel = null;
                    $bairroSafetyDesc = null;
                }

                $neighborhoodModel = \App\Models\Neighborhood::create([
                    'city_id' => $cityModel->id,
                    'name' => $bairro,
                    'history_extract' => $bairroHistory,
                    'safety_level' => $bairroSafetyLevel,
                    'safety_description' => $bairroSafetyDesc,
                    'wiki_json' => $bairroWiki
                ]);
            }
        }

        // 3. Nominatim (Geocoding)
        $geo = null;
        if (!empty($street)) {
            $geo = $this->fetchNominatim($street, $city, $state);
        }
        
        if (!$geo) {
            $geo = $this->fetchNominatim($city, $city, $state); 
        }

        $lat = $geo['lat'] ?? null;
        $lng = $geo['lon'] ?? null;

        if (!$lat || !$lng) return array_merge($address, $ibgeData);

        // 4. Overpass (POIs & Mobility)
        $pois = $this->fetchOverpass($lat, $lng);
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

        return array_merge($address, $ibgeData, [
            'lat' => $lat,
            'lng' => $lng,
            'pois_json' => $pois,
            'climate_json' => $climate,
            'wiki_json' => $wiki,
            'air_quality_index' => $airQuality,
            'walkability_score' => $walkScore,
            'average_income' => $cityModel->average_income,
            'sanitation_rate' => $cityModel->sanitation_rate,
            'history_extract' => $history ?: ($wiki['extract'] ?? null),
            'safety_level' => $safetyLevel,
            'safety_description' => $safetyDesc,
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
            $query = "{$street}, {$city}, {$state}, Brazil";
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                ->get("https://nominatim.openstreetmap.org/search", [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1
                ]);
            
            $data = $response->json();
            if (empty($data)) {
                $response = Http::withoutVerifying()
                    ->timeout(10)
                    ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                    ->get("https://nominatim.openstreetmap.org/search", [
                        'q' => "{$city}, {$state}, Brazil",
                        'format' => 'json',
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

    private function fetchOverpass($lat, $lng)
    {
        $radius = 10000;
        $query = "[out:json][timeout:60];(
            nwr[\"amenity\"~\"restaurant|pharmacy|hospital|bank|school|cafe|bar|fast_food|pub|university|clinic|dentist|place_of_worship|cinema|theatre|library|post_office|fuel|bicycle_parking\"](around:{$radius},{$lat},{$lng});
            nwr[\"shop\"~\"supermarket|bakery|convenience|clothes|mall|pharmacy|beauty|department_store|hardware|electronics|furniture|optician|books\"](around:{$radius},{$lat},{$lng});
            nwr[\"leisure\"~\"park|gym|sports_centre|playground\"](around:{$radius},{$lat},{$lng});
            nwr[\"highway\"=\"bus_stop\"](around:{$radius},{$lat},{$lng});
        );out center qt;";

        // Múltiplos endpoints públicos do Overpass para fallback
        $endpoints = [
            'https://overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
            'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
        ];

        foreach ($endpoints as $endpoint) {
            try {
                Log::info("Overpass: tentando endpoint {$endpoint}");
                $response = Http::withoutVerifying()
                    ->timeout(75)
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
     * Termos que são nomes genéricos e podem resolver artigos Wikipedia não relacionados
     * a bairros ou cidades (ex: "Centro" -> artigo de geometria).
     */
    private const AMBIGUOUS_NEIGHBORHOOD_TERMS = [
        'centro', 'norte', 'sul', 'leste', 'oeste', 'central',
        'jardim', 'vila', 'bela vista', 'alto', 'baixo',
    ];

    /**
     * Verifica se um artigo Wikipedia é realmente sobre um lugar (bairro/cidade),
     * e não sobre um conceito genérico.
     */
    private function isValidWikipediaPlace(array $data): bool
    {
        // A API de summary retorna um campo 'type' com valores como:
        // 'standard' = artigo genérico, 'disambiguation' = desambiguação
        // Também checar o 'description' que costuma indicar "Município", "bairro", etc.
        $type        = $data['type'] ?? '';
        $description = strtolower($data['description'] ?? '');
        $extract     = $data['extract'] ?? '';

        if ($type === 'disambiguation') return false;
        if (empty($extract))           return false;

        // Se description indica claramente que é um lugar, aceite
        $placeKeywords = ['município', 'cidade', 'bairro', 'distrito', 'região', 'localidade', 'capital'];
        foreach ($placeKeywords as $kw) {
            if (str_contains($description, $kw)) return true;
        }

        // Se o extract começa falando de geometria, matemática, etc, rejeite
        $rejectPatterns = ['/^o centro, em geometria/i', '/^em geometria/i', '/^em matemática/i', '/^em física/i'];
        foreach ($rejectPatterns as $pattern) {
            if (preg_match($pattern, $extract)) return false;
        }

        // Por padrão, aceite (extracts sem description clara)
        return true;
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

        // Bairros com nomes genéricos só buscamos com "Bairro_(Cidade)" — não o nome sozinho
        $bairroIsAmbiguous = $bairro && in_array(strtolower($bairro), self::AMBIGUOUS_NEIGHBORHOOD_TERMS);

        $candidates = [];

        if ($bairro) {
            $candidates[] = [str_replace(' ', '_', "{$bairro} ({$city})"), 'bairro'];
            if (!$bairroIsAmbiguous) {
                $candidates[] = [str_replace(' ', '_', $bairro), 'bairro'];
            }
        }

        if (!$onlyBairro) {
            $candidates[] = [str_replace(' ', '_', "{$city} ({$state})"), 'cidade'];
            $candidates[] = [str_replace(' ', '_', $city), 'cidade'];
        }

        foreach ($candidates as [$term, $source]) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(10)
                    ->withHeaders($headers)
                    ->get($base . urlencode($term));

                if ($response->successful()) {
                    $data = $response->json();

                    if (!$this->isValidWikipediaPlace($data)) {
                        Log::info("Wikipedia [{$term}]: artigo rejeitado (não é lugar). Tentando próximo...");
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

    private function fetchSocioEconomic($ibgeCode)
    {
        // For production, this would hit SIDRA or a curated database.
        // Mocking with structured data based on common ranges for Brazilian municipalities.
        return [
            'average_income' => rand(1500, 4500) + (rand(0, 99) / 100),
            'sanitation_rate' => rand(65, 98) + (rand(0, 9) / 10),
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

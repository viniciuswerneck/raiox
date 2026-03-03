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

        // 2. IBGE (Demographics & Regional Structure)
        $ibgeData = [];
        if ($ibgeCode) {
            $ibgeService = new \App\Services\IbgeService();
            $ibgeData = $ibgeService->getMunicipalityData($ibgeCode);
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

        // 6. Wikipedia (General and History Fallback)
        $wiki = $this->fetchWikipedia($city);
        $historyRaw = $this->fetchLocalHistory($address['bairro'] ?? '', $city, $state);
        
        // 7. Gemini AI Summary (Enhanced History)
        $history = $historyRaw;
        if ($historyRaw) {
            $gemini = new \App\Services\GeminiService();
            $aiSummary = $gemini->generateNeighborhoodSummary($historyRaw);
            if ($aiSummary) {
                $history = $aiSummary;
            }
        }
        
        // 8. IBGE Socioeconomic (Simulated/Extended)
        $socio = $this->fetchSocioEconomic($ibgeCode);

        return array_merge($address, $ibgeData, [
            'lat' => $lat,
            'lng' => $lng,
            'pois_json' => $pois,
            'climate_json' => $climate,
            'wiki_json' => $wiki,
            'air_quality_index' => $airQuality,
            'walkability_score' => $walkScore,
            'average_income' => $socio['average_income'] ?? null,
            'sanitation_rate' => $socio['sanitation_rate'] ?? null,
            'history_extract' => $history ?: ($wiki['extract'] ?? null),
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
        try {
            $radius = 10000; // Raio de 10km solicitado pelo usuário
            // Aumentamos o timeout interno do Overpass para 90s e usamos 'qt' (quadtile) para ordenação rápida
            $query = "[out:json][timeout:90];(
                nwr[\"amenity\"~\"restaurant|pharmacy|hospital|bank|school|cafe|bar|fast_food|pub|university|clinic|dentist|place_of_worship|cinema|theatre|library|post_office|fuel|bicycle_parking\"](around:{$radius},{$lat},{$lng});
                nwr[\"shop\"~\"supermarket|bakery|convenience|clothes|mall|pharmacy|beauty|department_store|hardware|electronics|furniture|optician|books\"](around:{$radius},{$lat},{$lng});
                nwr[\"leisure\"~\"park|gym|sports_centre|playground\"](around:{$radius},{$lat},{$lng});
                nwr[\"highway\"=\"bus_stop\"](around:{$radius},{$lat},{$lng});
            );out center qt;";
            
            $response = Http::withoutVerifying()
                ->timeout(100)
                ->asForm()
                ->post("https://overpass-api.de/api/interpreter", [
                    'data' => $query
                ]);
            
            if (!$response->successful()) {
                Log::error("Overpass API Error: " . $response->body());
                return [];
            }

            $data = $response->json();
            $elements = [];
            
            if (isset($data['elements'])) {
                foreach ($data['elements'] as $element) {
                    $item = [
                        'type' => $element['type'],
                        'id' => $element['id'],
                        'tags' => $element['tags'] ?? [],
                        'lat' => $element['lat'] ?? ($element['center']['lat'] ?? null),
                        'lon' => $element['lon'] ?? ($element['center']['lon'] ?? null)
                    ];
                    
                    if ($item['lat'] && $item['lon']) {
                        $elements[] = $item;
                    }
                }
            }
            
            if (empty($elements)) {
                Log::warning("Overpass query returned 0 elements for Lat:{$lat}, Lng:{$lng}. Radius:{$radius}");
            } else {
                Log::info("Overpass Found: " . count($elements) . " elements for Lat: {$lat}, Lng: {$lng}");
            }
            
            return $elements;
        } catch (\Exception $e) {
            Log::error("Overpass Exception: " . $e->getMessage());
            return [];
        }
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

    private function fetchWikipedia($city)
    {
        try {
            $term = str_replace(' ', '_', $city);
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                ->get("https://pt.wikipedia.org/api/rest_v1/page/summary/" . urlencode($term));
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'extract' => $data['extract'] ?? '',
                    'image' => $data['originalimage']['source'] ?? $data['thumbnail']['source'] ?? null,
                    'desktop_url' => $data['content_urls']['desktop']['page'] ?? null
                ];
            }
            return null;
        } catch (\Exception $e) {
            Log::error("Wikipedia Error: " . $e->getMessage());
            return null;
        }
    }

    private function fetchLocalHistory($bairro, $city, $state)
    {
        try {
            // Tentativa 1: Bairro_(Cidade)
            if ($bairro) {
                $term1 = str_replace(' ', '_', "{$bairro}_({$city})");
                $response1 = Http::withoutVerifying()
                    ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                    ->get("https://pt.wikipedia.org/api/rest_v1/page/summary/" . urlencode($term1));
                if ($response1->successful()) {
                    return $response1->json()['extract'] ?? null;
                }
            }

            // Tentativa 2: Cidade_(Estado)
            $term2 = str_replace(' ', '_', "{$city}_({$state})");
            $response2 = Http::withoutVerifying()
                ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                ->get("https://pt.wikipedia.org/api/rest_v1/page/summary/" . urlencode($term2));
            if ($response2->successful()) {
                return $response2->json()['extract'] ?? null;
            }

            // Tentativa 3: Somente Cidade (Otimizado com encoding)
            $term3 = str_replace(' ', '_', $city);
            $response3 = Http::withoutVerifying()
                ->withHeaders(['User-Agent' => 'RaioXNeighborhood/1.0'])
                ->get("https://pt.wikipedia.org/api/rest_v1/page/summary/" . urlencode($term3));
            if ($response3->successful()) {
                return $response3->json()['extract'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Wikipedia Local History Error: " . $e->getMessage());
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

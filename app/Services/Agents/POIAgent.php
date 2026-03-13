<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class POIAgent
{
    /**
     * Traz os POIs via Overpass usando o HttpClient do Laravel
     */
    public function fetchPOIs(float $lat, float $lng, int $radius = 1000): array
    {
        Log::info("POIAgent: Iniciando busca de POIs em [{$lat}, {$lng}] com raio {$radius}m");
        
        $margin = ($radius / 1000) * 0.009;
        $lat_min = $lat - $margin;
        $lat_max = $lat + $margin;
        $lon_min = $lng - $margin;
        $lon_max = $lng + $margin;
        $bbox = "{$lat_min},{$lon_min},{$lat_max},{$lon_max}";

        return $this->executeQuery($bbox, 300);
    }

    public function fetchPOIsByBBox(string $bbox, int $limit = 1000): array
    {
        Log::info("POIAgent: Iniciando busca municipal de POIs via BBox: {$bbox}");
        return $this->executeQuery($bbox, $limit);
    }

    private function executeQuery(string $bbox, int $limit): array
    {
        $query = "[out:json][timeout:35];(
            nwr({$bbox})[\"amenity\"~\"restaurant|pharmacy|hospital|bank|atm|school|university|clinic|doctors|police|fire_station|post_office|marketplace|cinema|theatre|library|community_centre|fuel|car_repair|dentist|veterinary|kindergarten|townhall|courthouse|events_venue\"];
            nwr({$bbox})[\"shop\"~\"supermarket|bakery|convenience|clothes|beauty|department_store|books|butcher|greengrocer|laundry|mall|pharmacy|hardware|shoes|optician|jewelry|variety_store|furniture|stationery|pet|toys|electronics|mobile_phone|bicycle|florist|butcher|car_repair|hairdresser\"];
            nwr({$bbox})[\"leisure\"~\"park|square|gym|sports_centre|playground|garden|beach|stadium|fitness_centre|swimming_pool\"];
            nwr({$bbox})[\"tourism\"~\"museum|monument|attraction|artwork|gallery|viewpoint|hotel|motel|guest_house\"];
            nwr({$bbox})[\"healthcare\"~\"pharmacy|hospital|clinic|dentist\"];
            nwr({$bbox})[\"craft\"~\"carpenter|electrician|plumber|gardener\"];
            nwr({$bbox})[\"historic\"];
            nwr({$bbox})[\"railway\"~\"station|stop\"];
            nwr({$bbox})[\"highway\"~\"bus_stop|bus_station\"];
            nwr({$bbox})[\"amenity\"=\"subway_entrance\"];
        );out center qt {$limit};";

        $endpoints = [
            'https://lz4.overpass-api.de/api/interpreter',
            'https://overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
            'https://z.overpass-api.de/api/interpreter'
        ];

        $headers = [
            'User-Agent' => 'RaioXNeighborhood-Agent/1.0',
            'Referer' => 'https://google.com'
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $startTime = microtime(true);
                $response = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                    ->timeout(12) 
                    ->withHeaders($headers)
                    ->asForm()
                    ->post($endpoint, ['data' => $query]);

                $duration = round(microtime(true) - $startTime, 2);

                if ($response->successful()) {
                    $elements = [];
                    $json = $response->json();
                    
                    if (isset($json['elements'])) {
                        foreach ($json['elements'] as $element) {
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
                    }

                    if (count($elements) > 0) {
                        Log::info("POIAgent: Sucesso com servidor [{$endpoint}] em {$duration}s. Itens: " . count($elements));
                        return $elements;
                    } else {
                        Log::warning("POIAgent: Servidor [{$endpoint}] retornou ZERO elementos em {$duration}s.");
                    }
                } else {
                    Log::error("POIAgent: Servidor [{$endpoint}] falhou com status {$response->status()} em {$duration}s. Response: " . substr($response->body(), 0, 100));
                }
            } catch (\Exception $e) {
                Log::warning("POIAgent: Erro fatal no servidor [{$endpoint}]: " . $e->getMessage());
            }
        }

        return [];
    }

    /**
     * Traz os POIs com raio adaptativo. Se encontrar poucos itens, escala o raio.
     */
    public function fetchPOIsAdaptive(float $lat, float $lng): array
    {
        $radius = 1000;
        $pois = $this->fetchPOIs($lat, $lng, $radius);

        if (count($pois) < 15) {
            Log::info("POIAgent: Poucos resultados em 1km. Escalando para 2.5km");
            $radius = 2500;
            $pois = $this->fetchPOIs($lat, $lng, $radius);

            if (count($pois) < 10) {
                Log::info("POIAgent: Ainda poucos resultados. Escalando final para 5km");
                $radius = 5000;
                $pois = $this->fetchPOIs($lat, $lng, $radius);
            }
        }

        return [
            'pois' => $pois,
            'radius' => $radius
        ];
    }

    public function calculateWalkabilityScore(array $pois): string
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

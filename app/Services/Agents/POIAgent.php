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
        
        // Conversão aproximada de metros para graus para criar uma Bounding Box (BBox)
        // BBox é MUITO mais rápida que 'around' no servidor Overpass.
        $margin = 0.009; // Aprox 1km
        $lat_min = $lat - $margin;
        $lat_max = $lat + $margin;
        $lon_min = $lng - $margin;
        $lon_max = $lng + $margin;
        $bbox = "{$lat_min},{$lon_min},{$lat_max},{$lon_max}";

        // Consulta de alta performance: busca por chaves principais dentro da BBox
        // Usamos nwr para garantir que polígonos (shoppings, parques) também venham, 
        // mas o BBox mantém a resposta instantânea.
        $query = "[out:json][timeout:25];(
            nwr({$bbox})[\"amenity\"~\"restaurant|pharmacy|hospital|bank|school|university|clinic|doctors|police|fire_station|post_office|marketplace|cinema|theatre|library|community_centre\"];
            nwr({$bbox})[\"shop\"~\"supermarket|bakery|convenience|clothes|beauty|department_store|books|butcher|greengrocer|laundry|mall|pharmacy|hardware\"];
            nwr({$bbox})[\"leisure\"~\"park|square|gym|sports_centre|playground|garden|beach|stadium\"];
            nwr({$bbox})[\"tourism\"~\"museum|monument|attraction|artwork|gallery|viewpoint|hotel\"];
            nwr({$bbox})[\"historic\"];
            nwr({$bbox})[\"railway\"~\"station|stop\"];
            nwr({$bbox})[\"highway\"~\"bus_stop|bus_station\"];
            nwr({$bbox})[\"amenity\"=\"subway_entrance\"];
        );out center qt 300;";

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
                $response = Http::withoutVerifying()
                    ->timeout(25) // Timeout generoso de 25s para compensar latência de rede + 15s de processamento Overpass
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

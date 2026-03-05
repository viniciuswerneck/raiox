<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class POIAgent
{
    /**
     * Traz os POIs via Overpass usando o HttpClient do Laravel
     */
    public function fetchPOIs(float $lat, float $lng, int $radius = 4000): array
    {
        $query = "[out:json][timeout:15];(
            nwr[\"amenity\"~\"restaurant|pharmacy|hospital|bank|school|cafe|bar|fast_food|pub|university|clinic|dentist|doctors|veterinary|kindergarten|childcare|place_of_worship|cinema|theatre|library|post_office|fuel|bicycle_parking|police|fire_station|townhall|public_service|marketplace|courthouse\"](around:{$radius},{$lat},{$lng});
            nwr[\"shop\"~\"supermarket|bakery|convenience|clothes|mall|pharmacy|beauty|department_store|hardware|electronics|furniture|optician|books|marketplace|butcher|greengrocer|doityourself|pet|hairdresser|sports|shoes|toys|jewelry|car|car_repair|car_wash|laundry\"](around:{$radius},{$lat},{$lng});
            nwr[\"leisure\"~\"park|gym|sports_centre|playground|marketplace\"](around:{$radius},{$lat},{$lng});
            nwr[\"tourism\"~\"museum|monument|attraction|artwork|gallery\"](around:{$radius},{$lat},{$lng});
            nwr[\"historic\"](around:{$radius},{$lat},{$lng});
            nwr[\"railway\"=\"station\"](around:{$radius},{$lat},{$lng});
        );out center bb qt 500;";

        $endpoints = [
            'https://overpass-api.de/api/interpreter',
            'https://lz4.overpass-api.de/api/interpreter',
            'https://z.overpass-api.de/api/interpreter'
        ];

        $headers = [
            'User-Agent' => 'RaioXNeighborhood-Agent/1.0',
            'Referer' => 'https://google.com'
        ];

        // Se quiser testar Pool aqui, daria, mas como os endpoints são falhos, fallback síncrono é mais seguro pros dados estruturais essenciais.
        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(12)
                    ->withHeaders($headers)
                    ->asForm()
                    ->post($endpoint, ['data' => $query]);

                if ($response->successful()) {
                    $elements = [];
                    foreach ($response->json('elements') ?? [] as $element) {
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
                    if (count($elements) > 0) return $elements;
                }
            } catch (\Exception $e) {
                Log::warning("POIAgent [{$endpoint}] exception: " . $e->getMessage());
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

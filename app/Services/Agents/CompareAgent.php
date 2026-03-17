<?php

namespace App\Services\Agents;

use App\Models\LocationReport;
use Illuminate\Support\Facades\Log;

class CompareAgent extends BaseAgent
{
    public const VERSION = '1.1.0';

    /**
     * Gera os scores comparativos de dois relatórios
     */
    public function compare(LocationReport $reportA, LocationReport $reportB): array
    {
        $this->logInfo("Comparando {$reportA->cep} vs {$reportB->cep}");

        $metricsA = $this->getRegionMetrics($reportA->pois_json ?? []);
        $metricsB = $this->getRegionMetrics($reportB->pois_json ?? []);

        // Cálculo de distância (Haversine básico)
        $distance = $this->calculateDistance($reportA->lat, $reportA->lng, $reportB->lat, $reportB->lng);

        return [
            'metrics_a' => $metricsA,
            'metrics_b' => $metricsB,
            'distance_km' => round($distance, 2),
            'deltas' => [
                'score_diff' => $metricsA['total_score'] - $metricsB['total_score'],
                'infra_diff' => $metricsA['infra'] - $metricsB['infra'],
                'mobilidade_diff' => $metricsA['mobility'] - $metricsB['mobility'],
                'lazer_diff' => $metricsA['leisure'] - $metricsB['leisure'],
                'income_diff' => ($reportA->average_income ?? 0) - ($reportB->average_income ?? 0),
            ],
            'profiles' => [
                'a' => [
                    'class' => $reportA->territorial_classification ?? 'Não Definido',
                    'safety' => $reportA->safety_level ?? 'MODERADO',
                    'noise' => $this->estimateNoiseLevel($metricsA)
                ],
                'b' => [
                    'class' => $reportB->territorial_classification ?? 'Não Definido',
                    'safety' => $reportB->safety_level ?? 'MODERADO',
                    'noise' => $this->estimateNoiseLevel($metricsB)
                ]
            ],
            'agent_version' => self::VERSION
        ];
    }

    /**
     * Calcula métricas numéricas baseadas no POI JSON
     */
    public function getRegionMetrics(array $pois): array
    {
        $infra = 0;
        $mobility = 0;
        $leisure = 0;
        $commerce = 0;

        foreach ($pois as $poi) {
            $tags = $poi['tags'] ?? [];
            $amenity = $tags['amenity'] ?? '';
            $shop = $tags['shop'] ?? '';
            $leisure_tag = $tags['leisure'] ?? '';
            $highway = $tags['highway'] ?? '';

            // Infraestrutura (Saúde, Educação, Bancos, Segurança)
            if (in_array($amenity, ['hospital', 'clinic', 'doctors', 'pharmacy', 'school', 'university', 'bank', 'police', 'fire_station', 'post_office'])) {
                $infra++;
            }

            // Mobilidade (Transporte, Combustível)
            if ($highway === 'bus_stop' || in_array($amenity, ['fuel', 'bicycle_parking', 'parking', 'taxi']) || isset($tags['railway'])) {
                $mobility++;
            }

            // Lazer (Parques, Cultura, Turismo)
            if (in_array($leisure_tag, ['park', 'playground', 'sports_centre', 'gym', 'garden', 'square']) || 
                in_array($amenity, ['bar', 'pub', 'cinema', 'theatre', 'arts_centre', 'museum']) || 
                isset($tags['tourism']) || isset($tags['historic'])) {
                $leisure++;
            }

            // Comércio Geral
            if (!empty($shop) || in_array($amenity, ['restaurant', 'cafe', 'fast_food', 'bakery', 'marketplace'])) {
                $commerce++;
            }
        }

        $totalScore = ($infra * 5) + ($mobility * 8) + ($leisure * 4) + ($commerce * 1);

        return [
            'infra' => $infra,
            'mobility' => $mobility,
            'leisure' => $leisure,
            'commerce' => $commerce,
            'total_score' => (int)min($totalScore, 100) 
        ];
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

    private function estimateNoiseLevel(array $metrics): string
    {
        $noiseScore = ($metrics['commerce'] * 1) + ($metrics['mobility'] * 2);
        if ($noiseScore > 40) return 'ALTO (Comercial Agitado)';
        if ($noiseScore > 15) return 'MODERADO (Urbano Padrão)';
        return 'BAIXO (Residencial Calmo)';
    }
}

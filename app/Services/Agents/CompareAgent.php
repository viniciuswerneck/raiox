<?php

namespace App\Services\Agents;

use App\Models\LocationReport;
use Illuminate\Support\Facades\Log;

class CompareAgent
{
    /**
     * Gera os scores comparativos de dois relatórios
     */
    public function compare(LocationReport $reportA, LocationReport $reportB): array
    {
        Log::info("CompareAgent: Comparando {$reportA->cep} vs {$reportB->cep}");

        $metricsA = $this->getRegionMetrics($reportA->pois_json ?? []);
        $metricsB = $this->getRegionMetrics($reportB->pois_json ?? []);

        return [
            'metrics_a' => $metricsA,
            'metrics_b' => $metricsB,
            'deltas' => [
                'score_diff' => $metricsA['total_score'] - $metricsB['total_score'],
                'infra_diff' => $metricsA['infra'] - $metricsB['infra'],
                'mobilidade_diff' => $metricsA['mobility'] - $metricsB['mobility'],
                'lazer_diff' => $metricsA['leisure'] - $metricsB['leisure'],
            ]
        ];
    }

    /**
     * Calcula métricas numéricas baseadas no POI JSON (Público para reuso no Pipeline)
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

        // Normalização de Score Geral (Simples ponderação)
        // Infra: 40%, Mobilidade: 30%, Lazer: 20%, Comércio: 10%
        $totalScore = ($infra * 5) + ($mobility * 8) + ($leisure * 4) + ($commerce * 1);

        return [
            'infra' => $infra,
            'mobility' => $mobility,
            'leisure' => $leisure,
            'commerce' => $commerce,
            'total_score' => (int)min($totalScore, 100) 
        ];
    }
}

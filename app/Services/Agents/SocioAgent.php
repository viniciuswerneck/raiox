<?php

namespace App\Services\Agents;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class SocioAgent
{
    /**
     * Retorna closures pro Master Pool
     */
    public function getPoolRequests(Pool $pool, ?string $ibgeCode): array
    {
        if (!$ibgeCode) return [];

        $ids = '29765|29168|60037|96385|29171'; // salary, idhm, sanitation, population, pib
        
        return [
            'ibge_basic' => $pool->as('ibge_basic')->timeout(10)
                ->get("https://servicodados.ibge.gov.br/api/v1/localidades/municipios/{$ibgeCode}"),
                
            'ibge_indicators' => $pool->as('ibge_indicators')->timeout(10)
                ->get("https://servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/{$ids}/resultados/{$ibgeCode}")
        ];
    }

    /**
     * Parseia os resultados do Pool e aplica a conversão salarial e IBGE Data
     */
    public function processResults(array $responses, ?string $ibgeCode): array
    {
        $raw = [];
        $population = null;
        $sanitation = 85.0; // Padrão pessimista default
        $idhm = null;
        $avg_income = 1500.0;

        if ($ibgeCode) {
            if (isset($responses['ibge_basic']) && $responses['ibge_basic']->successful()) {
                $raw = $responses['ibge_basic']->json();
            }

            if (isset($responses['ibge_indicators']) && $responses['ibge_indicators']->successful()) {
                $indicators = [
                    '29765' => 'worker_salary', '29168' => 'idhm', 
                    '60037' => 'sanitation', '96385' => 'population', '29171' => 'pib'
                ];
                $results = [];

                foreach ($responses['ibge_indicators']->json() as $ind) {
                    if (!empty($ind['res'][0]['res'])) {
                        $key = $indicators[$ind['id']] ?? null;
                        if ($key) {
                            $results[$key] = end($ind['res'][0]['res']);
                        }
                    }
                }

                $population = isset($results['population']) ? (int)$results['population'] : null;
                $sanitation = isset($results['sanitation']) ? (float)$results['sanitation'] : 85.0;
                $idhm = isset($results['idhm']) ? (float)$results['idhm'] : null;

                $minWage = 1412.00;
                if (isset($results['worker_salary']) && $results['worker_salary'] > 0) {
                    $avg_income = $results['worker_salary'] * $minWage;
                } else {
                    $pibPerCapita = $results['pib'] ?? 0;
                    $avg_income = ($pibPerCapita > 0) ? ($pibPerCapita / 12) / 1.8 : 2400.00;
                }

                if ($avg_income < $minWage) {
                    $avg_income = $minWage + rand(100, 300);
                }
                $avg_income = round($avg_income, 2);
            }
        }

        return [
            'raw_ibge_data' => $raw,
            'population' => $population,
            'average_income' => $avg_income,
            'sanitation_rate' => $sanitation,
            'idhm' => $idhm
        ];
    }

    public function fetchIbgeCodeByName(string $city, string $stateUf): ?string
    {
        try {
            $response = Http::withoutVerifying()->timeout(8)
                ->get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$stateUf}/municipios");
            if ($response->successful()) {
                foreach ($response->json() as $mun) {
                    if (mb_strtolower($mun['nome']) === mb_strtolower($city)) {
                        return $mun['id'] ?? null;
                    }
                }
            }
        } catch (\Exception $e) {}
        return null;
    }
}

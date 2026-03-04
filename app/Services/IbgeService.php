<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IbgeService
{
    /**
     * Get demographic data for a municipality
     */
    public function getMunicipalityData(string $ibgeCode): array
    {
        // Info básica
        $municipality = Http::withoutVerifying()->get("https://servicodados.ibge.gov.br/api/v1/localidades/municipios/{$ibgeCode}");
        
        $indicators = [
            'population' => '96385',
            'pib' => '29171',
            'sanitation' => '60037', // Esgotamento sanitário adequado
            'idhm' => '29168'         // IDH Municipal
        ];

        $results = [];
        foreach ($indicators as $key => $id) {
            $response = Http::withoutVerifying()->timeout(10)->get("https://servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/{$id}/resultados/{$ibgeCode}");
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data) && isset($data[0]['res'][0]['res'])) {
                    $results[$key] = end($data[0]['res'][0]['res']);
                }
            }
        }

        return [
            'municipality_info' => $municipality->json(),
            'population' => isset($results['population']) ? (int)$results['population'] : null,
            'pib_per_capita' => isset($results['pib']) ? (float)$results['pib'] : null,
            'sanitation_rate' => isset($results['sanitation']) ? (float)$results['sanitation'] : null,
            'idhm' => isset($results['idhm']) ? (float)$results['idhm'] : null,
            'raw_data' => $municipality->json()
        ];
    }
}

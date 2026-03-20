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
        $municipality = Http::withoutVerifying()->retry(3, 100)->timeout(20)->get("https://servicodados.ibge.gov.br/api/v1/localidades/municipios/{$ibgeCode}");

        $indicators = [
            '29765' => 'worker_salary',
            '29168' => 'idhm',
            '60037' => 'sanitation',
            '96385' => 'population',
            '29171' => 'pib',
        ];

        $results = [];
        $ids = implode('|', array_keys($indicators));

        $response = Http::withoutVerifying()->retry(2, 200)->timeout(20)->get("https://servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/{$ids}/resultados/{$ibgeCode}");

        if ($response->successful()) {
            foreach ($response->json() as $ind) {
                if (! empty($ind['res'][0]['res'])) {
                    $key = $indicators[$ind['id']] ?? null;
                    if ($key) {
                        $results[$key] = end($ind['res'][0]['res']);
                    }
                }
            }
        }

        return [
            'municipality_info' => $municipality->json(),
            'population' => isset($results['population']) ? (int) $results['population'] : null,
            'pib_per_capita' => isset($results['pib']) ? (float) $results['pib'] : null,
            'sanitation_rate' => isset($results['sanitation']) ? (float) $results['sanitation'] : null,
            'idhm' => isset($results['idhm']) ? (float) $results['idhm'] : null,
            'average_salary' => isset($results['worker_salary']) ? (float) $results['worker_salary'] : null,
            'raw_data' => $municipality->json(),
        ];
    }

    public function getMunicipalityDataByName(string $name, string $uf): array
    {
        $response = Http::withoutVerifying()->get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios");
        if ($response->successful()) {
            foreach ($response->json() as $municipality) {
                if (mb_strtolower($municipality['nome']) === mb_strtolower($name)) {
                    $code = $municipality['id'];

                    return array_merge(['ibge_code' => $code], $this->getMunicipalityData($code));
                }
            }
        }

        return [];
    }
}

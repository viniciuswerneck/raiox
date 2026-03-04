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
        // Basic municipality info
        $municipality = Http::withoutVerifying()->get("https://servicodados.ibge.gov.br/api/v1/localidades/municipios/{$ibgeCode}");
        
        // Estimated population (96385)
        $populationRequest = Http::withoutVerifying()->get("https://servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/96385/resultados/{$ibgeCode}");
        // PIB per capita (29171)
        $pibRequest = Http::withoutVerifying()->get("https://servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/29171/resultados/{$ibgeCode}");
        
        $population = null;
        if ($populationRequest->successful()) {
            $popData = $populationRequest->json();
            if (!empty($popData) && isset($popData[0]['res'][0]['res'])) {
                $popValue = end($popData[0]['res'][0]['res']);
                $population = (int) $popValue;
            }
        }

        $pibPerCapita = null;
        if ($pibRequest->successful()) {
            $pibData = $pibRequest->json();
            if (!empty($pibData) && isset($pibData[0]['res'][0]['res'])) {
                $pibValue = end($pibData[0]['res'][0]['res']);
                $pibPerCapita = (float) $pibValue;
            }
        }

        return [
            'municipality_info' => $municipality->json(),
            'population' => $population,
            'pib_per_capita' => $pibPerCapita,
            'raw_data' => $municipality->json()
        ];
    }
}

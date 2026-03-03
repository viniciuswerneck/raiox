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
        
        // Estimated population (using indicator 96385 - IBGE Research ID 29)
        // Note: In real scenarios, these codes may change
        $populationRequest = Http::withoutVerifying()->get("https://servicodados.ibge.gov.br/api/v1/pesquisas/indicadores/96385/resultados/{$ibgeCode}");
        
        $population = null;
        if ($populationRequest->successful()) {
            $popData = $populationRequest->json();
            // Dig into the IBGE JSON structure
            if (!empty($popData) && isset($popData[0]['res'][0]['res'])) {
                $populationRes = $popData[0]['res'][0]['res'];
                // Some IBGE endpoints return results as { "year": "value" }, so end() gets the most recent value
                $populationValue = is_array($populationRes) ? end($populationRes) : $populationRes;
                $population = (int) $populationValue;
            }
        }

        return [
            'municipality_info' => $municipality->json(),
            'population' => $population,
            'idhm' => null, // IDHM usually requires a different API or static mapping for simplicity
            'raw_data' => $municipality->json()
        ];
    }
}

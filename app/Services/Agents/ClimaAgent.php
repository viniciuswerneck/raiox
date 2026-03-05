<?php

namespace App\Services\Agents;

use Illuminate\Http\Client\Pool;

class ClimaAgent
{
    /**
     * Retorna os closures para serem adicionados no Master Pool
     */
    public function getPoolRequests(Pool $pool, float $lat, float $lng): array
    {
        return [
            'weather' => $pool->as('weather')->timeout(8)
                ->get("https://api.open-meteo.com/v1/forecast", [
                    'latitude' => $lat, 'longitude' => $lng, 'current_weather' => 'true'
                ]),
                
            'air_quality' => $pool->as('air_quality')->timeout(8)
                ->get("https://air-quality-api.open-meteo.com/v1/air-quality", [
                    'latitude' => $lat, 'longitude' => $lng, 'current' => 'european_aqi'
                ])
        ];
    }

    /**
     * Processa a resposta após o Pool executar
     */
    public function processResults(array $responses): array
    {
        $weather = isset($responses['weather']) && $responses['weather']->successful() 
                    ? $responses['weather']->json() : [];
        
        $aqi = null;
        if (isset($responses['air_quality']) && $responses['air_quality']->successful()) {
            $aqi = $responses['air_quality']->json()['current']['european_aqi'] ?? null;
        }

        return [
            'climate_json' => $weather,
            'air_quality_index' => $aqi
        ];
    }
}

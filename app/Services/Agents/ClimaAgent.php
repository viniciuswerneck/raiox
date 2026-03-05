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
        $weatherRes = $responses['weather'] ?? null;
        $weather = ($weatherRes instanceof \Illuminate\Http\Client\Response && $weatherRes->successful()) 
                    ? $weatherRes->json() : [];
        
        $aqi = null;
        $aqiRes = $responses['air_quality'] ?? null;
        if ($aqiRes instanceof \Illuminate\Http\Client\Response && $aqiRes->successful()) {
            $aqi = $aqiRes->json()['current']['european_aqi'] ?? null;
        }

        return [
            'climate_json' => $weather,
            'air_quality_index' => $aqi
        ];
    }
}

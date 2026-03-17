<?php

namespace App\Services\Agents;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Log;

class ClimaAgent extends BaseAgent
{
    public const VERSION = '1.0.1';

    /**
     * Retorna os closures para serem adicionados no Master Pool
     */
    public function getPoolRequests(Pool $pool, float $lat, float $lng): array
    {
        $this->logInfo("Preparando requisições de Pool para Clima em [{$lat}, {$lng}]");

        return [
            'weather' => $pool->as('weather')->when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(8)
                ->get("https://api.open-meteo.com/v1/forecast", [
                    'latitude' => $lat, 'longitude' => $lng, 'current_weather' => 'true'
                ]),
                
            'air_quality' => $pool->as('air_quality')->when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(8)
                ->get("https://air-quality-api.open-meteo.com/v1/air-quality", [
                    'latitude' => $lat, 'longitude' => $lng, 'current' => 'european_aqi,us_aqi'
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
            $data = $aqiRes->json();
            $aqi = $data['current']['european_aqi'] ?? $data['current']['us_aqi'] ?? null;
        }

        if (empty($weather)) {
            $this->logInfo("Dados meteorológicos de [weather] falharam no master pool.");
        }

        return [
            'climate_json' => $weather,
            'air_quality_index' => $aqi,
            'agent_version' => self::VERSION
        ];
    }
}

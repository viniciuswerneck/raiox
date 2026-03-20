<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExternalApiRateLimiter
{
    private array $apiConfigs = [
        'viacep' => [
            'name' => 'ViaCEP',
            'baseUrl' => 'https://viacep.com.br/ws/01001000/json/',
            'maxRequests' => 50,
            'windowSeconds' => 60,
            'timeout' => 5,
        ],
        'brasilapi' => [
            'name' => 'BrasilAPI',
            'baseUrl' => 'https://brasilapi.com.br/api/cep/v1/01001000',
            'maxRequests' => 100,
            'windowSeconds' => 60,
            'timeout' => 5,
        ],
        'awesomeapi' => [
            'name' => 'AwesomeAPI',
            'baseUrl' => 'https://cep.awesomeapi.com.br/json/01001000',
            'maxRequests' => 100,
            'windowSeconds' => 60,
            'timeout' => 5,
        ],
        'ibge' => [
            'name' => 'IBGE',
            'baseUrl' => 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios/3550308',
            'maxRequests' => 30,
            'windowSeconds' => 60,
            'timeout' => 20,
        ],
        'nominatim' => [
            'name' => 'OpenStreetMap Nominatim',
            'baseUrl' => 'https://nominatim.openstreetmap.org/search',
            'maxRequests' => 10,
            'windowSeconds' => 60,
            'timeout' => 10,
        ],
        'overpass' => [
            'name' => 'Overpass API (OSM)',
            'baseUrl' => 'https://overpass-api.de/api/status',
            'maxRequests' => 10,
            'windowSeconds' => 60,
            'timeout' => 30,
        ],
        'openmeteo' => [
            'name' => 'Open-Meteo',
            'baseUrl' => 'https://api.open-meteo.com/v1/forecast',
            'maxRequests' => 100,
            'windowSeconds' => 60,
            'timeout' => 10,
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'baseUrl' => 'https://generativelanguage.googleapis.com',
            'maxRequests' => 60,
            'windowSeconds' => 60,
            'timeout' => 30,
        ],
        'openrouter' => [
            'name' => 'OpenRouter',
            'baseUrl' => 'https://openrouter.ai/api/v1',
            'maxRequests' => 40,
            'windowSeconds' => 60,
            'timeout' => 30,
        ],
        'groq' => [
            'name' => 'Groq',
            'baseUrl' => 'https://api.groq.com/openai/v1',
            'maxRequests' => 30,
            'windowSeconds' => 60,
            'timeout' => 30,
        ],
    ];

    public function canMakeRequest(string $apiName): bool
    {
        if (! isset($this->apiConfigs[$apiName])) {
            return true;
        }

        $config = $this->apiConfigs[$apiName];
        $key = "rate_limit:{$apiName}";
        $current = Cache::get($key, 0);

        return $current < $config['maxRequests'];
    }

    public function recordRequest(string $apiName): void
    {
        if (! isset($this->apiConfigs[$apiName])) {
            return;
        }

        $config = $this->apiConfigs[$apiName];
        $key = "rate_limit:{$apiName}";
        $current = Cache::get($key, 0);

        Cache::put($key, $current + 1, $config['windowSeconds']);
    }

    public function getRemainingRequests(string $apiName): int
    {
        if (! isset($this->apiConfigs[$apiName])) {
            return -1;
        }

        $config = $this->apiConfigs[$apiName];
        $key = "rate_limit:{$apiName}";
        $current = Cache::get($key, 0);

        return max(0, $config['maxRequests'] - $current);
    }

    public function getStatus(): array
    {
        $status = [];

        foreach ($this->apiConfigs as $name => $config) {
            $key = "rate_limit:{$name}";
            $current = Cache::get($key, 0);

            $status[$name] = [
                'name' => $config['name'],
                'used' => $current,
                'max' => $config['maxRequests'],
                'remaining' => $this->getRemainingRequests($name),
                'window_seconds' => $config['windowSeconds'],
                'percentage' => round(($current / $config['maxRequests']) * 100, 1),
                'is_limited' => $current >= $config['maxRequests'],
            ];
        }

        return $status;
    }

    public function checkApiHealth(string $apiName): array
    {
        if (! isset($this->apiConfigs[$apiName])) {
            return ['status' => 'unknown', 'message' => 'API not configured'];
        }

        $config = $this->apiConfigs[$apiName];

        try {
            $startTime = microtime(true);
            $response = Http::timeout($config['timeout'])
                ->withoutVerifying()
                ->get($config['baseUrl']);
            $latency = round((microtime(true) - $startTime) * 1000);

            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'latency_ms' => $latency,
                'http_code' => $response->status(),
                'message' => $response->successful() ? 'OK' : 'HTTP Error',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unreachable',
                'latency_ms' => null,
                'http_code' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function checkAllApisHealth(): array
    {
        $health = [];

        foreach (array_keys($this->apiConfigs) as $apiName) {
            $health[$apiName] = $this->checkApiHealth($apiName);
        }

        return $health;
    }
}

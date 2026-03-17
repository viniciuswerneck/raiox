<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoAgent extends BaseAgent
{
    public const VERSION = '1.2.0';

    private const STATES_MAP = [
        'Acre' => 'AC', 'Alagoas' => 'AL', 'Amapá' => 'AP', 'Amazonas' => 'AM', 
        'Bahia' => 'BA', 'Ceará' => 'CE', 'Distrito Federal' => 'DF', 'Espírito Santo' => 'ES', 
        'Goiás' => 'GO', 'Maranhão' => 'MA', 'Mato Grosso' => 'MT', 'Mato Grosso do Sul' => 'MS', 
        'Minas Gerais' => 'MG', 'Pará' => 'PA', 'Paraíba' => 'PB', 'Paraná' => 'PR', 
        'Pernambuco' => 'PE', 'Piauí' => 'PI', 'Rio de Janeiro' => 'RJ', 'Rio Grande do Norte' => 'RN', 
        'Rio Grande do Sul' => 'RS', 'Rondônia' => 'RO', 'Roraima' => 'RR', 'Santa Catarina' => 'SC', 
        'São Paulo' => 'SP', 'Sergipe' => 'SE', 'Tocantins' => 'TO'
    ];

    /**
     * Resolve endereço via ViaCEP com múltiplos retries e fallbacks
     */
    public function resolveCep(string $cep): ?array
    {
        $this->logInfo("Iniciando resolução de CEP: {$cep}");

        // Tratamento para CEPs inativos conhecido
        $knownInactiveCeps = [
            '13089470' => [
                'logradouro' => 'Rua Cesario Galli',
                'bairro' => 'Jardim Nilópolis',
                'localidade' => 'Campinas',
                'uf' => 'SP',
                'ibge' => '3509502',
                'lat' => '-22.84982',
                'lon' => '-47.03545'
            ],
            '12300010' => [
                'logradouro' => 'Rua Comendador Xavier de Toledo',
                'bairro' => 'Centro',
                'localidade' => 'Jacareí',
                'uf' => 'SP',
                'ibge' => '3524402',
                'lat' => '-23.3050682',
                'lon' => '-45.9723075'
            ]
        ];

        if (array_key_exists($cep, $knownInactiveCeps)) {
            $this->logInfo("CEP identificado em base interna de conhecidos.");
            return $knownInactiveCeps[$cep];
        }

        // Estratégia 1: ViaCEP
        try {
            $response = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(4)
                ->get("https://viacep.com.br/ws/{$cep}/json/");
            if ($response->successful() && !isset($response->json()['erro'])) {
                return $response->json();
            }
        } catch (\Exception $e) {
            $this->logError("Erro ViaCEP: " . $e->getMessage());
        }

        // Estratégia 2: BrasilAPI
        try {
            Log::info("GeoAgent: Tentando BrasilAPI para CEP {$cep}");
            $res = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(5)
                ->get("https://brasilapi.com.br/api/cep/v1/{$cep}");
            if ($res->successful()) {
                $data = $res->json();
                return [
                    'logradouro' => $data['street'] ?? '',
                    'bairro' => $data['neighborhood'] ?? '',
                    'localidade' => $data['city'] ?? '',
                    'uf' => $data['state'] ?? '',
                    'ibge' => $data['city_code'] ?? null
                ];
            }
        } catch (\Exception $e) {
            $this->logError("Erro BrasilAPI: " . $e->getMessage());
        }

        // Estratégia 3: AwesomeAPI
        try {
            Log::info("GeoAgent: Tentando AwesomeAPI para CEP {$cep}");
            $res = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(5)
                ->get("https://cep.awesomeapi.com.br/json/{$cep}");
            if ($res->successful()) {
                $data = $res->json();
                return [
                    'logradouro' => $data['address'] ?? '',
                    'bairro' => $data['district'] ?? '',
                    'localidade' => $data['city'] ?? '',
                    'uf' => $data['state'] ?? '',
                    'ibge' => $data['city_ibge'] ?? null,
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lng'] ?? null
                ];
            }
        } catch (\Exception $e) {
            $this->logError("Erro AwesomeAPI: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Resolve coordenadas via Nominatim
     */
    public function geolocateCity(string $city, string $state): ?array
    {
        $headers = ['User-Agent' => 'GeoAgent-RaioX/' . self::VERSION];
        $queryParams = [
            'city' => $city,
            'state' => $state,
            'country' => 'Brazil',
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 1
        ];

        $node = $this->queryNominatim($queryParams, $headers);

        if ($node) {
            return [
                'lat' => $node['lat'] ?? null,
                'lon' => $node['lon'] ?? null,
                'bbox' => $node['boundingbox'] ?? null,
            ];
        }

        return null;
    }

    public function geolocate(string $street, string $city, string $state, ?string $cep = null): ?array
    {
        $headers = ['User-Agent' => 'GeoAgent-RaioX/' . self::VERSION];
        
        $queryBase = empty($city) ? "{$street}" : "{$street}, {$city}, {$state}";
        $queryBase = trim(trim($queryBase), ',');

        $queryParams = [
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 1,
            'countrycodes' => 'br'
        ];
        
        if ($cep) {
            $queryParams['postalcode'] = $cep;
        }

        $queryParams['q'] = $queryBase ? "{$queryBase}, Brazil" : "Brazil";
        
        $node = $this->queryNominatim($queryParams, $headers);

        // Fallback: Apenas Cidade e Estado
        if (!$node && !empty($street) && !empty($city)) {
            $this->logInfo("Fallback Nominatim (Cidade apenas).");
            $fallbackParams = [
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 1,
                'countrycodes' => 'br',
                'q' => "{$city}, {$state}, Brazil"
            ];
            $node = $this->queryNominatim($fallbackParams, $headers);
        }

        if ($node) {
            $countryCode = $node['address']['country_code'] ?? '';
            if ($countryCode !== 'br') {
                $this->logInfo("Nominatim retornou resultado fora do Brasil ({$countryCode}). Ignorando.");
                return null;
            }

            $stateName = $node['address']['state'] ?? '';
            $mappedState = self::STATES_MAP[$stateName] ?? null;

            $finalState = $mappedState ?: (strlen($stateName) === 2 ? strtoupper($stateName) : substr($stateName, 0, 2));

            return [
                'lat' => $node['lat'] ?? null,
                'lon' => $node['lon'] ?? null,
                'city' => $node['address']['city'] ?? $node['address']['town'] ?? $node['address']['village'] ?? '',
                'state' => $finalState,
                'suburb' => $node['address']['neighbourhood'] ?? $node['address']['suburb'] ?? $node['address']['hamlet'] ?? ''
            ];
        }

        return null;
    }

    private function queryNominatim($query, $headers)
    {
        try {
            $params = is_array($query) ? $query : [
                'q' => $query,
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 1,
                'countrycodes' => 'br'
            ];
            
            $response = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(5)->withHeaders($headers)
                ->get("https://nominatim.openstreetmap.org/search", $params);
                
            return $response->json()[0] ?? null;
        } catch (\Exception $e) {
            $this->logError("Erro Nominatim: " . $e->getMessage());
            return null;
        }
    }
}

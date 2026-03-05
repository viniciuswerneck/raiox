<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoAgent
{
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
     * Resolve endereço via ViaCEP com 2 retries
     */
    public function resolveCep(string $cep): ?array
    {
        try {
            $response = Http::withoutVerifying()->timeout(5)->retry(2, 200)
                ->get("https://viacep.com.br/ws/{$cep}/json/");
            if ($response->successful() && !isset($response->json()['erro'])) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error("GeoAgent [ViaCEP]: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Resolve coordenadas via Nominatim
     */
    public function geolocate(string $street, string $city, string $state): ?array
    {
        $headers = ['User-Agent' => 'GeoAgent-RaioX/1.0'];
        
        // Tentativa principal: Rua + Cidade + Estado
        $query = empty($city) ? $street : "{$street}, {$city}, {$state}, Brazil";
        $node = $this->queryNominatim($query, $headers);

        // Fallback: Apenas Cidade e Estado
        if (!$node && !empty($street) && !empty($city)) {
            Log::info("GeoAgent: Fallback Nominatim (Cidade apenas).");
            $node = $this->queryNominatim("{$city}, {$state}, Brazil", $headers);
        }

        if ($node) {
            $stateName = $node['address']['state'] ?? '';
            return [
                'lat' => $node['lat'] ?? null,
                'lon' => $node['lon'] ?? null,
                'city' => $node['address']['city'] ?? $node['address']['town'] ?? $node['address']['village'] ?? '',
                'state' => self::STATES_MAP[$stateName] ?? $stateName,
                'suburb' => $node['address']['neighbourhood'] ?? $node['address']['suburb'] ?? $node['address']['hamlet'] ?? ''
            ];
        }

        return null;
    }

    private function queryNominatim($query, $headers)
    {
        try {
            $response = Http::withoutVerifying()->timeout(8)->retry(1, 500)->withHeaders($headers)
                ->get("https://nominatim.openstreetmap.org/search", [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1
                ]);
            return $response->json()[0] ?? null;
        } catch (\Exception $e) {
            Log::error("GeoAgent [Nominatim]: " . $e->getMessage());
            return null;
        }
    }
}

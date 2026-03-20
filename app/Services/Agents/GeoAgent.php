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
        'São Paulo' => 'SP', 'Sergipe' => 'SE', 'Tocantins' => 'TO',
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
                'lon' => '-47.03545',
            ],
            '12300010' => [
                'logradouro' => 'Rua Comendador Xavier de Toledo',
                'bairro' => 'Centro',
                'localidade' => 'Jacareí',
                'uf' => 'SP',
                'ibge' => '3524402',
                'lat' => '-23.3050682',
                'lon' => '-45.9723075',
            ],
            '86600010' => [
                'logradouro' => 'Avenida Interventor Manoel Ribas',
                'bairro' => 'Centro',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3067',
                'lon' => '-51.3621',
            ],
            '86600020' => [
                'logradouro' => 'Avenida Interventor Manoel Ribas',
                'bairro' => 'Centro',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3075',
                'lon' => '-51.3630',
            ],
            '86600030' => [
                'logradouro' => 'Rua Monteiro Lobato',
                'bairro' => 'Centro',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3117',
                'lon' => '-51.3663',
            ],
            '86600040' => [
                'logradouro' => 'Vila Oliveira',
                'bairro' => 'Vila Oliveira',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3175',
                'lon' => '-51.3750',
            ],
            '86600050' => [
                'logradouro' => 'Centro',
                'bairro' => 'Centro',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3080',
                'lon' => '-51.3640',
            ],
            '86600060' => [
                'logradouro' => 'Centro',
                'bairro' => 'Centro',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3090',
                'lon' => '-51.3650',
            ],
            '86600070' => [
                'logradouro' => 'Centro',
                'bairro' => 'Centro',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3100',
                'lon' => '-51.3660',
            ],
            '86600080' => [
                'logradouro' => 'Rua Santa Catarina',
                'bairro' => 'Centro',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3110',
                'lon' => '-51.3670',
            ],
            '86600090' => [
                'logradouro' => 'Avenida dos Expedicionários',
                'bairro' => 'Centro',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3120',
                'lon' => '-51.3680',
            ],
            '86601000' => [
                'logradouro' => 'Rua Maringá',
                'bairro' => 'Manoel Müller',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3059',
                'lon' => '-51.3643',
            ],
            '86602000' => [
                'logradouro' => 'Rua Carlos Luz',
                'bairro' => 'Jardim Rosângelo',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3073',
                'lon' => '-51.3708',
            ],
            '86603000' => [
                'logradouro' => 'Rua Waldomiro Flauzino da Silva',
                'bairro' => 'Residencial Portal Arabela',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3150',
                'lon' => '-51.3800',
            ],
            '86604000' => [
                'logradouro' => 'Vila Oliveira',
                'bairro' => 'Vila Oliveira',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3180',
                'lon' => '-51.3760',
            ],
            '86605000' => [
                'logradouro' => 'Rua Aguiar de Lima',
                'bairro' => 'Jardim Caviúna',
                'localidade' => 'Rolândia',
                'uf' => 'PR',
                'ibge' => '4122404',
                'lat' => '-23.3210',
                'lon' => '-51.3790',
            ],
        ];

        if (array_key_exists($cep, $knownInactiveCeps)) {
            $this->logInfo('CEP identificado em base interna de conhecidos.');

            return $knownInactiveCeps[$cep];
        }

        // Estratégia 1: ViaCEP
        try {
            $response = Http::when(app()->isProduction(), fn ($h) => $h, fn ($h) => $h->withoutVerifying())
                ->timeout(4)
                ->get("https://viacep.com.br/ws/{$cep}/json/");
            if ($response->successful() && ! isset($response->json()['erro'])) {
                return $response->json();
            }
        } catch (\Exception $e) {
            $this->logError('Erro ViaCEP: '.$e->getMessage());
        }

        // Estratégia 2: BrasilAPI
        try {
            Log::info("GeoAgent: Tentando BrasilAPI para CEP {$cep}");
            $res = Http::when(app()->isProduction(), fn ($h) => $h, fn ($h) => $h->withoutVerifying())
                ->timeout(5)
                ->get("https://brasilapi.com.br/api/cep/v1/{$cep}");
            if ($res->successful()) {
                $data = $res->json();

                return [
                    'logradouro' => $data['street'] ?? '',
                    'bairro' => $data['neighborhood'] ?? '',
                    'localidade' => $data['city'] ?? '',
                    'uf' => $data['state'] ?? '',
                    'ibge' => $data['city_code'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            $this->logError('Erro BrasilAPI: '.$e->getMessage());
        }

        // Estratégia 3: AwesomeAPI
        try {
            Log::info("GeoAgent: Tentando AwesomeAPI para CEP {$cep}");
            $res = Http::when(app()->isProduction(), fn ($h) => $h, fn ($h) => $h->withoutVerifying())
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
                    'lon' => $data['lng'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            $this->logError('Erro AwesomeAPI: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Resolve coordenadas via Nominatim
     */
    public function geolocateCity(string $city, string $state): ?array
    {
        $headers = ['User-Agent' => 'GeoAgent-RaioX/'.self::VERSION];
        $queryParams = [
            'city' => $city,
            'state' => $state,
            'country' => 'Brazil',
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 1,
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
        $headers = ['User-Agent' => 'GeoAgent-RaioX/'.self::VERSION];

        $queryBase = empty($city) ? "{$street}" : "{$street}, {$city}, {$state}";
        $queryBase = trim(trim($queryBase), ',');

        $queryParams = [
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 1,
            'countrycodes' => 'br',
        ];

        if ($cep) {
            $queryParams['postalcode'] = $cep;
        }

        $queryParams['q'] = $queryBase ? "{$queryBase}, Brazil" : 'Brazil';

        $node = $this->queryNominatim($queryParams, $headers);

        // Fallback: Apenas Cidade e Estado
        if (! $node && ! empty($street) && ! empty($city)) {
            $this->logInfo('Fallback Nominatim (Cidade apenas).');
            $fallbackParams = [
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 1,
                'countrycodes' => 'br',
                'q' => "{$city}, {$state}, Brazil",
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
                'suburb' => $node['address']['neighbourhood'] ?? $node['address']['suburb'] ?? $node['address']['hamlet'] ?? '',
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
                'countrycodes' => 'br',
            ];

            $response = Http::when(app()->isProduction(), fn ($h) => $h, fn ($h) => $h->withoutVerifying())
                ->timeout(5)->withHeaders($headers)
                ->get('https://nominatim.openstreetmap.org/search', $params);

            return $response->json()[0] ?? null;
        } catch (\Exception $e) {
            $this->logError('Erro Nominatim: '.$e->getMessage());

            return null;
        }
    }
}

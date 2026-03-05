<?php

namespace App\Services\Agents;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PipelineCoordinator
{
    private $geoAgent;
    private $poiAgent;
    private $climaAgent;
    private $socioAgent;

    public function __construct(
        GeoAgent $geoAgent,
        POIAgent $poiAgent,
        ClimaAgent $climaAgent,
        SocioAgent $socioAgent
    ) {
        $this->geoAgent = $geoAgent;
        $this->poiAgent = $poiAgent;
        $this->climaAgent = $climaAgent;
        $this->socioAgent = $socioAgent;
    }

    /**
     * Orquestra a construção dos dados rápidos assíncronos.
     */
    public function orchestrateFastPath(string $cepClean): ?array
    {
        Log::info("PipelineCoordinator: Iniciando resolução básica de GeoAgent para {$cepClean}");
        
        $street = ''; $city = ''; $state = ''; $ibgeCode = null; $bairro = '';
        
        // Fase 1: Identidade Básica
        $viaCep = $this->geoAgent->resolveCep($cepClean);
        if ($viaCep) {
            $street = $viaCep['logradouro'] ?? '';
            $city = $viaCep['localidade'];
            $state = $viaCep['uf'];
            $ibgeCode = $viaCep['ibge'] ?? null;
            $bairro = $viaCep['bairro'] ?? '';
        }

        if (!$city || !$state) { // Fallback, no momento consideramos o Fast Path falho se não tiver CEP no viacep.
            Log::error("PipelineCoordinator: FastPath interrompido. Endereço base não localizado.");
            return null;
        }

        // Fase 2: Lat/Lng
        $geo = $this->geoAgent->geolocate($street, $city, $state);
        $lat = $geo['lat'] ?? null;
        $lng = $geo['lon'] ?? null;
        $bairro = $bairro ?: ($geo['suburb'] ?? '');

        if (!$lat || !$lng) {
            Log::error("PipelineCoordinator: Falha ao geolocalizar (Nominatim).");
            return [
                'city' => $city, 'state' => $state, 'street' => $street, 'ibge_code' => $ibgeCode, 'error' => true,
                'error_message' => 'Nó territorial indisponível.', 'status' => 'failed'
            ];
        }

        if (!$ibgeCode) {
            $ibgeCode = $this->socioAgent->fetchIbgeCodeByName($city, $state);
        }

        // Fase 3: MASTER POOL ASSINCRONO (Clima, Ar e SocioEconomico)
        Log::info("PipelineCoordinator: Lançando Master Pool Assíncrono [{$lat}, {$lng}] - IBGE: {$ibgeCode}");
        
        // Aqui o Pool Assíncrono da API do Laravel é acionado
        $poolResponses = Http::withoutVerifying()->pool(function ($pool) use ($lat, $lng, $ibgeCode) {
            $requests = array_merge(
                $this->climaAgent->getPoolRequests($pool, $lat, $lng),
                $this->socioAgent->getPoolRequests($pool, $ibgeCode)
            );
            return $requests;
        });

        // Extraimos a magia de cada um...
        $climateData = $this->climaAgent->processResults($poolResponses);
        $socioData = $this->socioAgent->processResults($poolResponses, $ibgeCode);

        // Fase 4: POIs (Roda logo em seguida pq os Endpoints overpass quebram se colocar no Pool de cima e eles demoram os retries)
        $pois = $this->poiAgent->fetchPOIs($lat, $lng);
        $walkScore = $this->poiAgent->calculateWalkabilityScore($pois);

        // Agregando tudo
        return [
            'lat' => $lat,
            'lng' => $lng,
            'logradouro' => $street,
            'bairro' => $bairro,
            'cidade' => $city,
            'uf' => $state,
            'codigo_ibge' => $ibgeCode,
            'pois_json' => $pois,
            'walkability_score' => $walkScore,
            'climate_json' => $climateData['climate_json'],
            'air_quality_index' => $climateData['air_quality_index'],
            'population' => $socioData['population'],
            'idhm' => $socioData['idhm'],
            'average_income' => $socioData['average_income'],
            'sanitation_rate' => $socioData['sanitation_rate'],
            'raw_ibge_data' => $socioData['raw_ibge_data'],
            'status' => 'processing_text', // Define a trave de Polling pro Frontend
            'error' => false
        ];
    }

    /**
     * Aplica compensações para AACT/Qualificação.
     */
    public function calculateCategorization(array $data): array
    {
        $pois = $data['pois_json'];
        $walkScore = $data['walkability_score'];
        $bairro = $data['bairro'] ?? '';
        $income = $data['average_income'] ?? 1500;

        $poiCounts = ['popular' => 0, 'central' => 0, 'turistico' => 0, 'lazer_alto' => 0];
        foreach ($pois as $poi) {
            $shop = $poi['tags']['shop'] ?? '';
            $amenity = $poi['tags']['amenity'] ?? '';
            $tourism = $poi['tags']['tourism'] ?? '';
            if (in_array($shop, ['supermarket', 'convenience', 'doityourself'])) $poiCounts['popular']++;
            if (in_array($amenity, ['bank', 'hospital', 'university']) || $shop == 'mall') $poiCounts['central']++;
            if (!empty($tourism) || in_array($amenity, ['bar'])) $poiCounts['turistico']++;
            if (in_array($amenity, ['arts_centre', 'theatre'])) $poiCounts['lazer_alto']++;
        }

        $classification = 'Residencial Médio';
        $isCentro = (str_contains(strtolower($bairro), 'centro') || str_contains(strtolower($bairro), 'central'));
        if ($poiCounts['turistico'] >= 5) $classification = 'Turístico Premium';
        elseif ($isCentro || $poiCounts['central'] >= 5) $classification = 'Comercial Central';
        elseif ($income > 5000 || $poiCounts['lazer_alto'] > 3) $classification = 'Residencial Alto Padrão';
        elseif (!$isCentro && $income < 2000 && $poiCounts['central'] < 2) $classification = 'Residencial Popular';
        else if (count($pois) < 5) $classification = 'Zona de Expansão / Rural';

        $calibratedSanitation = $data['sanitation_rate'] ?? 85.0;
        if ($walkScore === 'A') $calibratedSanitation = max($calibratedSanitation, 95.5);
        elseif ($walkScore === 'B') $calibratedSanitation = max($calibratedSanitation, 85.0);
        if ($isCentro) $calibratedSanitation = max($calibratedSanitation, 98.0);

        return [
            'classification' => $classification,
            'sanitation_rate' => $calibratedSanitation
        ];
    }
}

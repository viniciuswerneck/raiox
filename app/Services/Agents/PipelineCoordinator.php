<?php

namespace App\Services\Agents;

use App\Models\City;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PipelineCoordinator
{
    private $geoAgent;
    private $poiAgent;
    private $climaAgent;
    private $socioAgent;
    private $compareAgent;

    public function __construct(
        GeoAgent $geoAgent,
        POIAgent $poiAgent,
        ClimaAgent $climaAgent,
        SocioAgent $socioAgent,
        CompareAgent $compareAgent
    ) {
        $this->geoAgent = $geoAgent;
        $this->poiAgent = $poiAgent;
        $this->climaAgent = $climaAgent;
        $this->socioAgent = $socioAgent;
        $this->compareAgent = $compareAgent;
    }

    /**
     * Orquestra a construção dos dados rápidos assíncronos.
     */
    public function orchestrateFastPath(string $cepClean): ?array
    {
        Log::info("PipelineCoordinator: Iniciando resolução básica de GeoAgent para {$cepClean}");
        
        $street = ''; $city = ''; $state = ''; $ibgeCode = null; $bairro = ''; $lat = null; $lng = null;
        
        // Fase 1: Identidade Básica
        $viaCep = $this->geoAgent->resolveCep($cepClean);
        if ($viaCep) {
            $street = $viaCep['logradouro'] ?? '';
            $city = $viaCep['localidade'];
            $state = $viaCep['uf'];
            $ibgeCode = $viaCep['ibge'] ?? null;
            $bairro = $viaCep['bairro'] ?? '';
            $lat = $viaCep['lat'] ?? null;
            $lng = $viaCep['lon'] ?? null;
        }

        if (!$city || !$state) { 
            Log::error("PipelineCoordinator: FastPath interrompido. Endereço base não localizado nas APIs de CEP.");
            return [
                'city' => $city, 'state' => $state, 'street' => $street, 'ibge_code' => $ibgeCode, 'error' => true,
                'error_message' => 'CEP inválido ou sem cobertura geográfica identificada.', 'status' => 'failed'
            ];
        }

        // Fase 2: Lat/Lng (Se não veio do resolveCep)
        if (!$lat || !$lng) {
            $geo = $this->geoAgent->geolocate($street, $city, $state, $cepClean);
            $lat = $geo['lat'] ?? null;
            $lng = $geo['lon'] ?? null;
            $bairro = $bairro ?: ($geo['suburb'] ?? '');
        }

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

        // Fase 3: MASTER POOL ASSÍNCRONO (Clima, Ar e SocioEconomico)
        Log::info("PipelineCoordinator: Lançando Master Pool Assíncrono [{$lat}, {$lng}] - IBGE: {$ibgeCode}");
        
        $poolResponses = Http::pool(fn ($pool) => array_merge(
            $this->climaAgent->getPoolRequests($pool, $lat, $lng),
            $this->socioAgent->getPoolRequests($pool, $ibgeCode)
        ));

        // Extraimos os dados de cada agente
        $climateData = $this->climaAgent->processResults($poolResponses);
        $socioData = $this->socioAgent->processResults($poolResponses, $ibgeCode);

        // Fase 4: POIs (Busca Adaptativa)
        $adaptiveData = $this->poiAgent->fetchPOIsAdaptive($lat, $lng);
        $pois = $adaptiveData['pois'];
        $currentRadius = $adaptiveData['radius'];

        $walkScore = $this->poiAgent->calculateWalkabilityScore($pois);
        
        // Novo: Cálculo de Scores para o Banco
        $metrics = $this->compareAgent->getRegionMetrics($pois);
        
        Log::info("PipelineCoordinator: POIs capturados: " . count($pois) . " | WalkScore: {$walkScore}");

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
            'search_radius' => $currentRadius,
            'walkability_score' => $walkScore,
            'infra_score' => $metrics['infra'],
            'mobility_score' => $metrics['mobility'],
            'leisure_score' => $metrics['leisure'],
            'general_score' => $metrics['total_score'],
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
     * Garante que a cidade existe no banco de dados.
     */
    public function ensureCityExists(array $data): void
    {
        try {
            City::firstOrCreate(
                ['name' => $data['cidade'], 'uf' => $data['uf']],
                [
                    'ibge_code' => $data['codigo_ibge'],
                    'population' => $data['population'],
                    'average_income' => $data['average_income'],
                    'idhm' => $data['idhm'],
                    'sanitation_rate' => $data['sanitation_rate'] ?? 0
                ]
            );
        } catch (\Exception $e) {
            Log::warning("Erro ao criar cidade: " . $e->getMessage());
        }
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
        elseif ($isCentro || $poiCounts['central'] >= 8) $classification = 'Comercial Central';
        elseif ($income > 7500 || $poiCounts['lazer_alto'] > 4) $classification = 'Residencial Alto Padrão';
        elseif ($income > 3800 || $poiCounts['lazer_alto'] >= 2) $classification = 'Residencial Nobre';
        elseif (!$isCentro && $income < 2000 && $poiCounts['central'] < 2) $classification = 'Residencial Popular';
        else if (count($pois) < 5) $classification = 'Zona de Expansão / Rural';

        $calibratedSanitation = $data['sanitation_rate'] ?? 85.0;
        if ($walkScore === 'A') $calibratedSanitation = max($calibratedSanitation, 95.5);
        elseif ($walkScore === 'B') $calibratedSanitation = max($calibratedSanitation, 85.0);
        if ($isCentro) $calibratedSanitation = max($calibratedSanitation, 98.0);

        $safetyLevel = 'MODERADO';
        if ($classification === 'Turístico Premium') $safetyLevel = 'ALTO (POLICIAMENTO)';
        elseif ($classification === 'Comercial Central') $safetyLevel = 'ALTO FLUXO / ATENÇÃO';
        elseif ($classification === 'Residencial Alto Padrão') $safetyLevel = 'ZONA PROTEGIDA';
        elseif ($classification === 'Residencial Nobre') $safetyLevel = 'ALTA SEGURANÇA';
        elseif ($classification === 'Residencial Popular') $safetyLevel = 'MODERADO / LOCAL';
        elseif ($classification === 'Residencial Médio') $safetyLevel = 'ZONA MONITORADA';

        return [
            'classification' => $classification,
            'sanitation_rate' => $calibratedSanitation,
            'safety_level' => $safetyLevel
        ];
    }
}

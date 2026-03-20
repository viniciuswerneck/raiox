<?php

namespace App\Services\Territory;

use App\Services\Agents\ClimaAgent;
use App\Services\Agents\CompareAgent;
use App\Services\Agents\GeoAgent;
use App\Services\Agents\POIAgent;
use App\Services\Agents\SocioAgent;
use App\Services\Agents\WikiAgent;
use App\Services\LlmManagerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TerritoryEngine v3.0.0
 * O Orquestrador Mestre do sistema Raio-X.
 * Coordena micro-agentes versionados e garante a integridade territorial dos dados.
 */
class TerritoryEngine
{
    protected GeoAgent $geo;

    protected POIAgent $poi;

    protected ClimaAgent $clima;

    protected SocioAgent $socio;

    protected WikiAgent $wiki;

    protected CompareAgent $compare;

    protected \App\Services\Agents\KnowledgeAgent $knowledge;

    protected LlmManagerService $llm;

    public function __construct(
        GeoAgent $geo,
        POIAgent $poi,
        ClimaAgent $clima,
        SocioAgent $socio,
        WikiAgent $wiki,
        CompareAgent $compare,
        \App\Services\Agents\KnowledgeAgent $knowledge,
        LlmManagerService $llm
    ) {
        $this->geo = $geo;
        $this->poi = $poi;
        $this->clima = $clima;
        $this->socio = $socio;
        $this->wiki = $wiki;
        $this->compare = $compare;
        $this->knowledge = $knowledge;
        $this->llm = $llm;
    }

    /**
     * Resolução Rápida (Síncrona): Apenas Identidade Territorial
     * Usado para mostrar a página imediatamente com skeletons.
     */
    public function resolveFast(string $cep): array
    {
        Log::info("[TerritoryEngine] Resolvendo Identidade Territorial Rápida para: {$cep}");
        $geoData = $this->geo->resolveCep($cep);

        if (! $geoData || ! isset($geoData['localidade'])) {
            return ['status' => 'failed', 'error' => 'CEP não localizado.'];
        }

        $lat = $geoData['lat'] ?? null;
        $lng = $geoData['lon'] ?? null;
        $suburb = $geoData['bairro'] ?? '';

        if (! $lat || ! $lng) {
            $coords = $this->geo->geolocate($geoData['logradouro'] ?? '', $geoData['localidade'], $geoData['uf'], $cep);
            $lat = $coords['lat'] ?? null;
            $lng = $coords['lon'] ?? null;
            if (! $suburb) {
                $suburb = $coords['suburb'] ?? '';
            }
        }

        return [
            'status' => 'success',
            'location' => [
                'cep' => $cep,
                'address' => $geoData['logradouro'] ?? '',
                'neighborhood' => $suburb,
                'city' => $geoData['localidade'],
                'state' => $geoData['uf'],
                'ibge' => $geoData['ibge'] ?? null,
                'coordinates' => ['lat' => $lat, 'lng' => $lng],
            ],
        ];
    }

    /**
     * Orquestra a coleta completa de dados para um CEP.
     */
    public function resolve(string $cep, array $preResolvedGeo = []): array
    {
        Log::info("[TerritoryEngine] Iniciando resolução completa para o CEP: {$cep}");

        // 1. Identidade Territorial
        if (! empty($preResolvedGeo)) {
            $geoData = $preResolvedGeo;
        } else {
            $fast = $this->resolveFast($cep);
            if ($fast['status'] === 'failed') {
                return $fast;
            }
            $geoData = $fast['location'];
        }

        $city = $geoData['city'] ?? $geoData['localidade'] ?? '';
        $state = $geoData['state'] ?? $geoData['uf'] ?? '';
        $lat = $geoData['coordinates']['lat'] ?? $geoData['lat'] ?? null;
        $lng = $geoData['coordinates']['lng'] ?? $geoData['lon'] ?? null;
        $ibgeCode = $geoData['ibge'] ?? null;
        $suburb = $geoData['neighborhood'] ?? $geoData['bairro'] ?? '';

        // 2. ORQUESTRAÇÃO DE AGENTES (MACRO-PARALELISMO)

        // [RAG] 2a. Busca em Memória Territorial Interna (Síncrono mas otimizado SQL agora)
        $ragContext = $this->knowledge->search("História e cultura de {$suburb} em {$city}", 3);

        // Se o IBGE não veio no CEP, buscamos agora para o pool
        if (! $ibgeCode) {
            $ibgeCode = $this->socio->fetchIbgeCodeByName($city, $state);
        }

        Log::info('[TerritoryEngine] Disparando Macro-Pool para Wiki, Socio e Clima.');

        // DISPARO CONCORRENTE: Wikipedia + IBGE + Clima em um único túnel
        $responses = Http::pool(function ($pool) use ($suburb, $city, $state, $ibgeCode, $lat, $lng) {
            return array_merge(
                $this->wiki->getPoolRequests($pool, $suburb, $city, $state),
                $this->socio->getPoolRequests($pool, $ibgeCode),
                $this->clima->getPoolRequests($pool, $lat, $lng)
            );
        });

        // 3. Processamento Paralelo dos Resultados
        $wikiData = $this->wiki->processResultsFromPool($responses, $suburb, $city, $state);
        $socioData = $this->socio->processResults($responses, $ibgeCode);
        $climaData = $this->clima->processResults($responses);

        // [RAG] 3a. Indexação de novos conhecimentos da Wiki
        if (! empty($wikiData['full_text'])) {
            $this->knowledge->store(
                $wikiData['full_text'],
                'wiki',
                $cep,
                ['city' => $city, 'neighborhood' => $suburb, 'source' => 'wikipedia']
            );
        }

        // 4. Infraestrutura (POIs) - Ainda semi-síncrono por depender de lat/lng final,
        // mas agora o caminho até aqui foi muito mais rápido.
        $poiResult = $this->poi->fetchPOIsAdaptive($lat, $lng);
        $pois = $poiResult['pois'] ?? [];
        $metrics = $this->compare->getRegionMetrics($pois);
        $walkScore = $this->poi->calculateWalkabilityScore($pois);

        // 5. Consolidação Final
        return [
            'status' => 'success',
            'engine_version' => '3.0.0',
            'location' => [
                'cep' => $cep,
                'address' => $geoData['logradouro'] ?? '',
                'neighborhood' => $suburb,
                'city' => $city,
                'state' => $state,
                'ibge' => $ibgeCode,
                'coordinates' => ['lat' => $lat, 'lng' => $lng],
            ],
            'agents' => [
                'geo' => ['version' => $this->geo::VERSION],
                'socio' => $socioData,
                'clima' => $climaData,
                'poi' => [
                    'version' => $this->poi::VERSION,
                    'radius' => $poiResult['radius'] ?? 1000,
                    'count' => count($pois),
                    'walk_score' => $walkScore,
                    'metrics' => $metrics,
                    'data' => $pois,
                ],
                'wiki' => $wikiData,
                'knowledge_base' => [
                    'version' => $this->knowledge::VERSION,
                    'is_grounded' => count($ragContext) > 0,
                    'context_chunks' => $ragContext,
                ],
            ],
            'categorization' => $this->calculateCategorization($socioData, $pois, $walkScore, $suburb),
        ];
    }

    /**
     * Aplica compensações para AACT/Qualificação Territorial.
     */
    public function calculateCategorization(array $socio, array $pois, string $walkScore, string $bairro): array
    {
        $income = $socio['average_income'] ?? 1500;
        $poiCounts = ['popular' => 0, 'central' => 0, 'turistico' => 0, 'lazer_alto' => 0];

        foreach ($pois as $poi) {
            $shop = $poi['tags']['shop'] ?? '';
            $amenity = $poi['tags']['amenity'] ?? '';
            $tourism = $poi['tags']['tourism'] ?? '';
            if (in_array($shop, ['supermarket', 'convenience', 'doityourself'])) {
                $poiCounts['popular']++;
            }
            if (in_array($amenity, ['bank', 'hospital', 'university']) || $shop == 'mall') {
                $poiCounts['central']++;
            }
            if (! empty($tourism) || in_array($amenity, ['bar'])) {
                $poiCounts['turistico']++;
            }
            if (in_array($amenity, ['arts_centre', 'theatre'])) {
                $poiCounts['lazer_alto']++;
            }
        }

        $classification = 'Residencial Médio';
        $isCentro = (str_contains(strtolower($bairro), 'centro') || str_contains(strtolower($bairro), 'central'));

        if ($poiCounts['turistico'] >= 5) {
            $classification = 'Turístico Premium';
        } elseif ($isCentro || $poiCounts['central'] >= 8) {
            $classification = 'Comercial Central';
        } elseif ($income > 7500 || $poiCounts['lazer_alto'] > 4) {
            $classification = 'Residencial Alto Padrão';
        } elseif ($income > 3800 || $poiCounts['lazer_alto'] >= 2) {
            $classification = 'Residencial Nobre';
        } elseif (! $isCentro && $income < 2000 && $poiCounts['central'] < 2) {
            $classification = 'Residencial Popular';
        } elseif (count($pois) < 5) {
            $classification = 'Zona de Expansão / Rural';
        }

        $calibratedSanitation = $socio['sanitation_rate'] ?? 85.0;
        if ($walkScore === 'A') {
            $calibratedSanitation = max($calibratedSanitation, 95.5);
        } elseif ($walkScore === 'B') {
            $calibratedSanitation = max($calibratedSanitation, 85.0);
        }
        if ($isCentro) {
            $calibratedSanitation = max($calibratedSanitation, 98.0);
        }

        $safetyLevel = 'MODERADO';
        if ($classification === 'Turístico Premium') {
            $safetyLevel = 'ALTO (POLICIAMENTO)';
        } elseif ($classification === 'Comercial Central') {
            $safetyLevel = 'ALTO FLUXO / ATENÇÃO';
        } elseif ($classification === 'Residencial Alto Padrão') {
            $safetyLevel = 'ZONA PROTEGIDA';
        } elseif ($classification === 'Residencial Nobre') {
            $safetyLevel = 'ALTA SEGURANÇA';
        } elseif ($classification === 'Residencial Popular') {
            $safetyLevel = 'MODERADO / LOCAL';
        } else {
            $safetyLevel = 'ZONA MONITORADA';
        }

        return [
            'classification' => $classification,
            'sanitation_rate' => $calibratedSanitation,
            'safety_level' => $safetyLevel,
        ];
    }

    /**
     * Garante que a cidade existe no banco de dados.
     */
    public function ensureCityExists(array $location, array $socio): void
    {
        try {
            \App\Models\City::updateOrCreate(
                ['name' => $location['city'], 'uf' => $location['state']],
                [
                    'ibge_code' => $location['ibge'],
                    'population' => $socio['population'],
                    'average_income' => $socio['average_income'],
                    'idhm' => $socio['idhm'],
                    'sanitation_rate' => $socio['sanitation_rate'] ?? 0,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Erro ao criar/atualizar cidade: '.$e->getMessage());
        }
    }
}

<?php

namespace App\Services;

use App\Services\Agents\CacheAgent;
use App\Services\Agents\LLMAgent;
use App\Services\Agents\PipelineCoordinator;
use App\Services\CityDashboard\CityDashboardService;
use App\Models\City;
use Illuminate\Support\Facades\Log;

class NeighborhoodService
{
    protected $coordinator;
    protected $cacheAgent;
    protected $llmAgent;
    protected $cityService;

    public function __construct(
        PipelineCoordinator $coordinator,
        CacheAgent $cacheAgent,
        LLMAgent $llmAgent,
        CityDashboardService $cityService
    ) {
        $this->coordinator = $coordinator;
        $this->cacheAgent = $cacheAgent;
        $this->llmAgent = $llmAgent;
        $this->cityService = $cityService;
    }

    /**
     * Retorna o Report cacheado ou engatilha o Pipeline Fast-Path + Background LLM
     */
    public function getCachedReport(string $cep): ?\App\Models\LocationReport
    {
        set_time_limit(100); 
        session_write_close(); 

        $cepClean = preg_replace('/\D/', '', $cep);
        
        // 1. CHECAR CACHE E TTL
        $cachedReport = $this->cacheAgent->getCachedReport($cepClean);

        if ($cachedReport && in_array($cachedReport->status, ['completed', 'processing_text', 'processing'])) {
            Log::info("NeighborhoodService: Cache Hit para CEP {$cepClean} (Status: {$cachedReport->status})");
            return $cachedReport;
        }

        // 2. Bloqueio Atômico para evitar múltiplas orquestrações simultâneas
        $lock = \Illuminate\Support\Facades\Cache::lock("orchestrate_{$cepClean}", 90);
        
        if (!$lock->get()) {
            // Se não conseguiu o lock, espera um pouco e tenta ler o cache de novo
            // (outro processo deve estar terminando a orquestração)
            usleep(1500000); // 1.5s
            return $this->cacheAgent->getCachedReport($cepClean);
        }

        try {
            Log::info("NeighborhoodService: Iniciando Orquestração Completa para CEP {$cepClean}");
            
            $fastPathData = $this->coordinator->orchestrateFastPath($cepClean);

            if (!$fastPathData || ($fastPathData['error'] ?? false)) {
                $errData = [
                    'cep' => $cepClean,
                    'cidade' => $fastPathData['city'] ?? 'Não Localizado',
                    'uf' => $fastPathData['state'] ?? '??',
                    'codigo_ibge' => $fastPathData['ibge_code'] ?? '0',
                    'status' => 'failed',
                    'error_message' => $fastPathData['error_message'] ?? 'CEP inválido ou sem cobertura geográfica inicial.'
                ];
                return $this->cacheAgent->upsertBasicData($cepClean, $errData);
            }

            // 3. APLICA COMPENSAÇÕES
            $categorization = $this->coordinator->calculateCategorization($fastPathData);

            // 4. SALVAR ESTRUCTURA NO CACHE (DB)
            $reportData = [
                'cep' => $cepClean,
                'logradouro' => $fastPathData['logradouro'] ?? '',
                'bairro' => $fastPathData['bairro'] ?? '',
                'cidade' => $fastPathData['cidade'],
                'uf' => $fastPathData['uf'],
                'codigo_ibge' => $fastPathData['codigo_ibge'],
                'populacao' => $fastPathData['population'],
                'idhm' => $fastPathData['idhm'],
                'lat' => $fastPathData['lat'],
                'lng' => $fastPathData['lng'],
                'pois_json' => $fastPathData['pois_json'],
                'search_radius' => 1000,
                'climate_json' => $fastPathData['climate_json'],
                'air_quality_index' => $fastPathData['air_quality_index'],
                'walkability_score' => $fastPathData['walkability_score'],
                'infra_score' => $fastPathData['infra_score'] ?? 0,
                'mobility_score' => $fastPathData['mobility_score'] ?? 0,
                'leisure_score' => $fastPathData['leisure_score'] ?? 0,
                'general_score' => $fastPathData['general_score'] ?? 0,
                'average_income' => $fastPathData['average_income'],
                'sanitation_rate' => $categorization['sanitation_rate'],
                'territorial_classification' => $categorization['classification'],
                'safety_level' => $categorization['safety_level'] ?? 'ANÁLISE',
                'raw_ibge_data' => $fastPathData['raw_ibge_data'] ?? null,
                'status' => 'processing_text',
                'data_version' => 3,
                'error_message' => null
            ];

            $report = $this->cacheAgent->upsertBasicData($cepClean, $reportData);

            // Garantir que a cidade existe
            $this->coordinator->ensureCityExists($reportData);
            
            // Atualizar dashboard da cidade (Estatísticas e História se necessário)
            $cityObj = City::where('name', $reportData['cidade'])->where('uf', $reportData['uf'])->first();
            if ($cityObj) {
                $this->cityService->updateCityData($cityObj);
            }

            // 5. DELEGAR NARRATIVA LLM
            $wikiContext = [
                'bairro' => $reportData['bairro'],
                'city' => $reportData['cidade'],
                'state' => $reportData['uf']
            ];
            
            $this->llmAgent->dispatchTextGeneration($cepClean, $report->id, $wikiContext);

            return $report;
        } finally {
            if (isset($lock)) $lock->release();
        }
    }

    public function getFullReport(string $cep): array { return []; } 
}

<?php

namespace App\Services;

use App\Services\Agents\CacheAgent;
use App\Services\Agents\LLMAgent;
use App\Services\Agents\PipelineCoordinator;
use Illuminate\Support\Facades\Log;

class NeighborhoodService
{
    protected $coordinator;
    protected $cacheAgent;
    protected $llmAgent;

    public function __construct(
        PipelineCoordinator $coordinator,
        CacheAgent $cacheAgent,
        LLMAgent $llmAgent
    ) {
        $this->coordinator = $coordinator;
        $this->cacheAgent = $cacheAgent;
        $this->llmAgent = $llmAgent;
    }

    /**
     * Retorna o Report cacheado ou engatilha o Pipeline Fast-Path + Background LLM
     */
    public function getCachedReport(string $cep): ?\App\Models\LocationReport
    {
        set_time_limit(100); 

        $cepClean = preg_replace('/\D/', '', $cep);
        
        // ============================================
        // 1. CHECAR CACHE E TTL
        // ============================================
        $cachedReport = $this->cacheAgent->getCachedReport($cepClean);

        if ($cachedReport && in_array($cachedReport->status, ['completed', 'processing_text', 'processing'])) {
            Log::info("NeighborhoodService: Cache Hit para CEP {$cepClean}");
            // Optional: Soft refresh pra clima (ignoramos pra manter resposta sempre rápida)
            return $cachedReport;
        }

        // ============================================
        // 2. ORQUESTRAR FAST-PATH HORIZONTAL
        // ============================================
        Log::info("NeighborhoodService: Iniciando Orquestração Completa para CEP {$cepClean}");
        
        $fastPathData = $this->coordinator->orchestrateFastPath($cepClean);

        if (!$fastPathData || ($fastPathData['error'] ?? false)) {
            $errData = [
                'cep' => $cepClean,
                'status' => 'failed',
                'error_message' => $fastPathData['error_message'] ?? 'CEP inválido ou sem cobertura geográfica inicial.'
            ];
            return $this->cacheAgent->upsertBasicData($cepClean, $errData);
        }

        // ============================================
        // 3. APLICA COMPENSAÇÕES
        // ============================================
        $categorization = $this->coordinator->calculateCategorization($fastPathData);

        // ============================================
        // 4. SALVAR ESTRUCTURA NO CACHE (DB)
        // ============================================
        $reportData = [
            'cep' => $cepClean,
            'logradouro' => $fastPathData['logradouro'],
            'bairro' => $fastPathData['bairro'] ?? '',
            'cidade' => $fastPathData['cidade'],
            'uf' => $fastPathData['uf'],
            'codigo_ibge' => $fastPathData['codigo_ibge'],
            'populacao' => $fastPathData['population'],
            'idhm' => $fastPathData['idhm'],
            'lat' => $fastPathData['lat'],
            'lng' => $fastPathData['lng'],
            'pois_json' => $fastPathData['pois_json'],
            'search_radius' => 4000,
            'climate_json' => $fastPathData['climate_json'],
            'air_quality_index' => $fastPathData['air_quality_index'],
            'walkability_score' => $fastPathData['walkability_score'],
            'average_income' => $fastPathData['average_income'],
            'sanitation_rate' => $categorization['sanitation_rate'],
            'territorial_classification' => $categorization['classification'],
            'status' => 'processing_text',
            'error_message' => null
        ];

        $report = $this->cacheAgent->upsertBasicData($cepClean, $reportData);

        // ============================================
        // 5. DELEGAR NARRATIVA LLM PARA JOB BACKGROUND (Fila 'NarrativeBackground')
        // ============================================
        $wikiContext = [
            'bairro' => $reportData['bairro'],
            'city' => $reportData['cidade'],
            'state' => $reportData['uf']
        ];
        
        $this->llmAgent->dispatchTextGeneration($cepClean, $report->id, $wikiContext);

        Log::info("NeighborhoodService: Processo assíncrono concluído com sucesso para o Frontend (CEP: {$cepClean}).");
        
        return $report;
    }

    public function getFullReport(string $cep): array { return []; } 
}

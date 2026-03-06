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
        session_write_close(); 

        $cepClean = preg_replace('/\D/', '', $cep);
        
        // ============================================
        // 1. CHECAR CACHE E TTL
        // ============================================
        $cachedReport = $this->cacheAgent->getCachedReport($cepClean);

        // Se o status for 'failed', permitimos o cache hit para evitar loop infinito de re-orquestração
        // O Job de background tentará novamente e corrigirá o status se necessário.
        if ($cachedReport && in_array($cachedReport->status, ['completed', 'processing_text', 'processing', 'failed'])) {
            Log::info("NeighborhoodService: Cache Hit para CEP {$cepClean} (Status: {$cachedReport->status})");
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
                'cidade' => $fastPathData['city'] ?? 'Não Localizado',
                'uf' => $fastPathData['state'] ?? '??',
                'codigo_ibge' => $fastPathData['ibge_code'] ?? '0',
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

        // --- BLINDAGEM DE SOBREPOSIÇÃO (Preservar dados bons se a rede falhar agora) ---
        if ($cachedReport) {
            // Preservar POIs e Score
            if (empty($reportData['pois_json']) && !empty($cachedReport->pois_json)) {
                Log::warning("NeighborhoodService: OSM Falhou. Preservando POIs antigos para {$cepClean}");
                $reportData['pois_json'] = $cachedReport->pois_json;
                $reportData['walkability_score'] = $cachedReport->walkability_score;
            }
            // Preservar Dados Demográficos
            if (empty($reportData['populacao']) && !empty($cachedReport->populacao)) {
                $reportData['populacao'] = $cachedReport->populacao;
            }
            if (empty($reportData['idhm']) && !empty($cachedReport->idhm)) {
                $reportData['idhm'] = $cachedReport->idhm;
            }
            if (empty($reportData['average_income']) && !empty($cachedReport->average_income)) {
                $reportData['average_income'] = $cachedReport->average_income;
            }
        }

        Log::info("NeighborhoodService: Salvando relatório para {$cepClean}. Income: {$reportData['average_income']}, Pop: {$reportData['populacao']}, IDHM: {$reportData['idhm']}");

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

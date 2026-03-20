<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NeighborhoodService
{
    protected $territory;

    protected $cacheAgent;

    protected $llmAgent;

    protected $cityService;

    public function __construct(
        \App\Services\Territory\TerritoryEngine $territory,
        \App\Services\Agents\CacheAgent $cacheAgent,
        \App\Services\Agents\LLMAgent $llmAgent,
        \App\Services\CityDashboard\CityDashboardService $cityService
    ) {
        $this->territory = $territory;
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
        $lock = null;
        $lock = \Illuminate\Support\Facades\Cache::lock("orchestrate_{$cepClean}", 90);

        if (! $lock->get()) {
            // Se não conseguiu o lock, espera um pouco e tenta ler o cache de novo
            // (outro processo deve estar terminando a orquestração)
            usleep(1500000); // 1.5s

            return $this->cacheAgent->getCachedReport($cepClean);
        }

        try {
            Log::info("NeighborhoodService: Iniciando Orquestração ASYNC para CEP {$cepClean}");

            // 3. FAST-PATH: Resolver apenas GEOGRAFIA (Síncrono, ~500ms)
            $fastData = $this->territory->resolveFast($cepClean);

            if ($fastData['status'] === 'failed') {
                $errData = [
                    'cep' => $cepClean,
                    'status' => 'failed',
                    'error_message' => $fastData['error'] ?? 'CEP inválido.',
                    'cidade' => 'Não Localizado',
                    'uf' => '??',
                    'codigo_ibge' => '0',
                ];

                return $this->cacheAgent->upsertBasicData($cepClean, $errData);
            }

            $location = $fastData['location'];

            // 4. SALVAR PLACEHOLDER NO BANCO (Status: processing)
            $reportData = [
                'cep' => $cepClean,
                'logradouro' => $location['address'],
                'bairro' => $location['neighborhood'],
                'cidade' => $location['city'],
                'uf' => $location['state'],
                'codigo_ibge' => $location['ibge'],
                'lat' => $location['coordinates']['lat'],
                'lng' => $location['coordinates']['lng'],
                'status' => 'processing',
                'data_version' => 3,
            ];

            $report = $this->cacheAgent->upsertBasicData($cepClean, $reportData);

            // 5. DISPARAR JOB DE PROCESSAMENTO COMPLETO (Background)
            // Este Job fará o trabalho pesado de Wiki, POIs, Clima, etc.
            \App\Jobs\ProcessLocationReport::dispatch($cepClean, $location);

            return $report;
        } finally {
            if ($lock !== null) {
                $lock->release();
            }
        }
    }

    public function getFullReport(string $cep): array
    {
        return [];
    }
}

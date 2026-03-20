<?php

namespace App\Jobs;

use App\Models\LocationReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLocationReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cep;

    protected $preResolvedGeo;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(string $cep, array $preResolvedGeo = [])
    {
        $this->cep = $cep;
        $this->preResolvedGeo = $preResolvedGeo;
    }

    /**
     * Execute the job.
     */
    public function handle(
        \App\Services\Territory\TerritoryEngine $territory,
        \App\Services\Agents\CacheAgent $cacheAgent,
        \App\Services\Agents\LLMAgent $llmAgent,
        \App\Services\CityDashboard\CityDashboardService $cityService
    ): void {
        $cepClean = preg_replace('/\D/', '', $this->cep);
        $report = LocationReport::where('cep', $cepClean)->first();

        try {
            Log::info("Job ProcessLocationReport started for CEP: {$cepClean}");

            // 1. Orquestração Completa
            $territoryData = $territory->resolve($cepClean, $this->preResolvedGeo);

            if ($territoryData['status'] === 'failed') {
                throw new \Exception($territoryData['error'] ?? 'Falha na resolução territorial.');
            }

            $location = $territoryData['location'];
            $agents = $territoryData['agents'];
            $categorization = $territoryData['categorization'];

            // 2. Mapeamento de dados para o Modelo
            $reportData = [
                'populacao' => $agents['socio']['population'],
                'idhm' => $agents['socio']['idhm'],
                'pois_json' => $agents['poi']['data'],
                'search_radius' => $agents['poi']['radius'],
                'climate_json' => $agents['clima']['climate_json'],
                'air_quality_index' => $agents['clima']['air_quality_index'],
                'walkability_score' => $agents['poi']['walk_score'],
                'infra_score' => $agents['poi']['metrics']['infra'] ?? 0,
                'mobility_score' => $agents['poi']['metrics']['mobility'] ?? 0,
                'leisure_score' => $agents['poi']['metrics']['leisure'] ?? 0,
                'general_score' => $agents['poi']['metrics']['total_score'] ?? 0,
                'average_income' => $agents['socio']['average_income'],
                'sanitation_rate' => $categorization['sanitation_rate'],
                'territorial_classification' => $categorization['classification'],
                'safety_level' => $categorization['safety_level'] ?? 'ANÁLISE',
                'raw_ibge_data' => $agents['socio']['raw_ibge_data'] ?? null,
                'wiki_json' => $agents['wiki'],
                'status' => 'processing_text', // Agora vai para a fase de narrativa
                'data_version' => 3,
                'error_message' => null,
            ];

            if ($report) {
                $report->update($reportData);
            } else {
                $report = LocationReport::create(array_merge(['cep' => $cepClean], $reportData));
            }

            // 3. Garantir Cidade e Dashboard
            $territory->ensureCityExists($location, $agents['socio']);
            $cityObj = \App\Models\City::where('name', $location['city'])->where('uf', $location['state'])->first();
            if ($cityObj) {
                $cityService->updateCityData($cityObj);
            }

            // 4. Disparar Narrativa LLM
            $llmAgent->dispatchTextGeneration($cepClean, $report->id, $agents['knowledge_base'] ?? []);

            Log::info("Job ProcessLocationReport completed successfully for CEP: {$cepClean}");

        } catch (\Exception $e) {
            Log::error("Job ProcessLocationReport failed for CEP: {$cepClean}. Error: ".$e->getMessage());
            if ($report) {
                $report->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
            throw $e;
        }
    }
}

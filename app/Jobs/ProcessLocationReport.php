<?php

namespace App\Jobs;

use App\Models\LocationReport;
use App\Services\NeighborhoodService;
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

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(string $cep)
    {
        $this->cep = $cep;
    }

    /**
     * Execute the job.
     */
    public function handle(NeighborhoodService $service): void
    {
        $cepClean = preg_replace('/\D/', '', $this->cep);
        $report = LocationReport::where('cep', $cepClean)->first();

        if ($report) {
            $report->update(['status' => 'processing']);
        }

        try {
            Log::info("Job started for CEP: {$cepClean}");
            
            // Reutiliza a lógica existente do serviço, mas forçando o processamento
            // já que o Job só é disparado quando o cache expirou ou não existe.
            $data = $service->getFullReport($cepClean);
            
            if (empty($data)) {
                throw new \Exception("Could not fetch data for CEP: {$cepClean}");
            }

            $reportData = [
                'cep' => $cepClean,
                'logradouro' => $data['logradouro'] ?? '',
                'bairro' => $data['bairro'] ?? '',
                'cidade' => $data['localidade'],
                'uf' => $data['uf'],
                'codigo_ibge' => $data['ibge_code'] ?? $data['ibge'] ?? '',
                'populacao' => $data['population'] ?? null,
                'idhm' => $data['idhm'] ?? null,
                'raw_ibge_data' => $data['municipality_info'] ?? [],
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'pois_json' => $data['pois_json'] ?? [],
                'search_radius' => $data['search_radius'] ?? 10000,
                'climate_json' => $data['climate_json'] ?? [],
                'wiki_json' => $data['wiki_json'] ?? [],
                'air_quality_index' => $data['air_quality_index'] ?? null,
                'walkability_score' => $data['walkability_score'] ?? 'C',
                'average_income' => $data['average_income'] ?? null,
                'sanitation_rate' => $data['sanitation_rate'] ?? null,
                'history_extract' => $data['history_extract'] ?? null,
                'safety_level' => $data['safety_level'] ?? null,
                'safety_description' => $data['safety_description'] ?? null,
                'real_estate_json' => $data['real_estate_json'] ?? null,
                'territorial_classification' => $data['territorial_classification'] ?? null,
                'aact_log' => $data['aact_log'] ?? null,
                'status' => 'completed',
                'error_message' => null,
            ];

            if ($report) {
                $report->update($reportData);
            } else {
                LocationReport::create($reportData);
            }

            Log::info("Job completed successfully for CEP: {$cepClean}");

        } catch (\Exception $e) {
            Log::error("Job failed for CEP: {$cepClean}. Error: " . $e->getMessage());
            
            if ($report) {
                $report->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
            
            throw $e;
        }
    }
}

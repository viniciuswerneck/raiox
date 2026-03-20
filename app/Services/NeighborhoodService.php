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
            usleep(1500000);

            return $this->cacheAgent->getCachedReport($cepClean);
        }

        try {
            // 3. VERIFICAR SE JÁ TEM DADOS BÁSICOS DO SCANNER (Status: pending)
            $pendingReport = \App\Models\LocationReport::where('cep', $cepClean)
                ->where('status', 'pending')
                ->whereNotNull('cidade')
                ->whereNotNull('uf')
                ->first();

            if ($pendingReport) {
                Log::info("NeighborhoodService: CEP {$cepClean} encontrado via scanner (pending). Retornando para geração sob demanda.");

                // NÃO dispara job - a geração será feita no show() de forma síncrona
                return $pendingReport;
            }

            Log::info("NeighborhoodService: Iniciando Orquestração ASYNC para CEP {$cepClean}");

            // 4. FAST-PATH: Resolver apenas GEOGRAFIA (Síncrono, ~500ms)
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

            // 5. SALVAR PLACEHOLDER NO BANCO (Status: processing)
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

            // 6. DISPARAR JOB DE PROCESSAMENTO COMPLETO (Background)
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

    /**
     * Gera relatório AI SINCRONAMENTE (sem queue worker)
     */
    public function generateReportSync(string $cep): \App\Models\LocationReport
    {
        set_time_limit(120);
        $cepClean = preg_replace('/\D/', '', $cep);

        $report = \App\Models\LocationReport::where('cep', $cepClean)->first();

        if (! $report) {
            throw new \Exception('Relatório não encontrado para CEP: '.$cepClean);
        }

        Log::info("NeighborhoodService: Gerando relatório SYNC para CEP {$cepClean}");

        $report->update(['status' => 'processing']);

        $lat = $report->lat;
        $lng = $report->lng;

        if (! $lat || ! $lng) {
            $geoData = $this->resolveCoordinates($report->cidade, $report->uf, $report->bairro);
            $lat = $geoData['lat'];
            $lng = $geoData['lng'];

            $report->update(['lat' => $lat, 'lng' => $lng]);
        }

        $location = [
            'address' => $report->logradouro,
            'neighborhood' => $report->bairro,
            'city' => $report->cidade,
            'state' => $report->uf,
            'ibge' => $report->codigo_ibge,
            'coordinates' => [
                'lat' => $lat,
                'lng' => $lng,
            ],
        ];

        $territoryData = $this->territory->resolve($cepClean, $location);

        if ($territoryData['status'] === 'failed') {
            $report->update([
                'status' => 'failed',
                'error_message' => $territoryData['error'] ?? 'Erro na resolução territorial.',
            ]);

            return $report;
        }

        $agents = $territoryData['agents'];
        $categorization = $territoryData['categorization'];

        $report->update([
            'populacao' => $agents['socio']['population'] ?? 0,
            'idhm' => $agents['socio']['idhm'] ?? 0,
            'pois_json' => $agents['poi']['data'] ?? [],
            'search_radius' => $agents['poi']['radius'] ?? 0,
            'climate_json' => $agents['clima']['climate_json'] ?? [],
            'air_quality_index' => $agents['clima']['air_quality_index'] ?? 0,
            'walkability_score' => $agents['poi']['walk_score'] ?? 0,
            'infra_score' => $agents['poi']['metrics']['infra'] ?? 0,
            'mobility_score' => $agents['poi']['metrics']['mobility'] ?? 0,
            'leisure_score' => $agents['poi']['metrics']['leisure'] ?? 0,
            'general_score' => $agents['poi']['metrics']['total_score'] ?? 0,
            'average_income' => $agents['socio']['average_income'] ?? 0,
            'sanitation_rate' => $categorization['sanitation_rate'] ?? 0,
            'territorial_classification' => $categorization['classification'] ?? 'Desconhecido',
            'safety_level' => $categorization['safety_level'] ?? 'ANÁLISE',
            'raw_ibge_data' => $agents['socio']['raw_ibge_data'] ?? null,
            'wiki_json' => $agents['wiki'] ?? [],
            'status' => 'processing_text',
            'data_version' => 3,
            'error_message' => null,
        ]);

        $this->territory->ensureCityExists($location, $agents['socio'] ?? []);

        $this->llmAgent->dispatchTextGeneration($cepClean, $report->id, $agents['knowledge_base'] ?? []);

        $report->update(['status' => 'completed']);

        Log::info("NeighborhoodService: Relatório SYNC completo para CEP {$cepClean}");

        return $report->fresh();
    }

    private function resolveCoordinates(string $city, string $state, ?string $neighborhood): array
    {
        $query = $neighborhood
            ? "{$neighborhood}, {$city}, {$state}"
            : "{$city}, {$state}";

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RaioX/1.0',
            'Referer' => 'https://raiox.app/',
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'br',
                ]);

            if ($response->successful() && ! empty($response->json())) {
                $result = $response->json()[0];

                return [
                    'lat' => (float) $result['lat'],
                    'lng' => (float) $result['lon'],
                ];
            }
        } catch (\Exception $e) {
            Log::warning("Failed to resolve coordinates for {$query}: ".$e->getMessage());
        }

        return ['lat' => 0.0, 'lng' => 0.0];
    }
}

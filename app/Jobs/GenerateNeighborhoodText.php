<?php

namespace App\Jobs;

use App\Models\City;
use App\Models\LocationReport;
use App\Models\Neighborhood;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateNeighborhoodText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cep;
    protected $reportId;
    protected $wikiSearchContext;

    public $tries = 2;
    public $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(string $cep, int $reportId, array $wikiSearchContext)
    {
        $this->cep = $cep;
        $this->reportId = $reportId;
        $this->wikiSearchContext = $wikiSearchContext;
    }

    /**
     * Termos que são ambíguos na Wiki
     */
    private const AMBIGUOUS_TERMS = [
        'centro', 'norte', 'sul', 'leste', 'oeste', 'central',
        'jardim', 'vila', 'parque', 'alto', 'baixo', 'bela vista', 'boa vista', 
        'santa', 'santo', 'são', 'nossa senhora', 'residencial', 'portal'
    ];

    /**
     * Execute the job.
     */
    public function handle(GeminiService $gemini): void
    {
        $report = LocationReport::find($this->reportId);
        if (!$report) {
            Log::error("TextGenerator Job Failed: Report {$this->reportId} not found.");
            return;
        }

        try {
            $city = $report->cidade;
            $state = $report->uf;
            $bairro = $report->bairro ?? '';
            $ibgeCode = $report->codigo_ibge;

            // 1. Wikipedia Múltipla Lógica (Bairro ou Cidade)
            $wikiResult = $this->fetchWikipediaInfo($bairro, $city, $state);
            $historyRaw = $wikiResult['full_text'] ?? $wikiResult['extract'] ?? "Local em desenvolvimento. Sobe as informações disponíveis sobre a cidade {$city}.";

            // 2. AACT Context para a IA
            $poisCount = is_array($report->pois_json) ? count($report->pois_json) : 0;
            $income = $report->average_income ?? 1500;
            $classification = $report->territorial_classification ?? 'Misto';

            $aactContext = [
                'categoria' => $classification,
                'pois_count' => $poisCount,
                'renda' => $income,
                'populacao' => $report->populacao ?? 0
            ];

            // 3. Gemini Generation
            Log::info("TextGenerator: Disparando Gemini para {$bairro}, {$city}");
            $locationName = $bairro ? "{$bairro}, {$city}" : $city;
            $aiSummary = $gemini->generateNeighborhoodSummary($historyRaw, $locationName, $aactContext);

            if (!$aiSummary) {
                // Se o Gemini falhou por completo, finaliza e muda pra status de erro
                $report->update([
                    'status' => 'failed',
                    'error_message' => 'O Motor Duelo Territorial não suportou a narrativa.'
                ]);
                return;
            }

            // 4. Salvar Entidades Locais (Bairro e Cidade) para cache
            $cityModel = City::where('name', $city)->where('uf', $state)->first();
            if (!$cityModel) {
                $cityModel = City::create([
                    'name' => $city,
                    'uf' => $state,
                    'ibge_code' => $ibgeCode,
                    'populacao' => $report->populacao,
                    'idhm' => $report->idhm,
                    'sanitation_rate' => $report->sanitation_rate,
                    'average_income' => $income,
                    'history_extract' => $bairro ? '' : ($aiSummary['historia'] ?? null),
                    'safety_level' => $bairro ? 'MODERADO' : ($aiSummary['nivel_seguranca'] ?? 'MODERADO'),
                    'wiki_json' => $bairro ? [] : $wikiResult,
                    'real_estate_json' => $bairro ? null : ($aiSummary['mercado_imobiliario'] ?? null),
                ]);
            }

            $neighborhoodModel = null;
            if ($bairro) {
                $neighborhoodModel = Neighborhood::where('city_id', $cityModel->id)
                    ->where('name', $bairro)->first();

                if (!$neighborhoodModel) {
                    $neighborhoodModel = Neighborhood::create([
                        'city_id' => $cityModel->id,
                        'name' => $bairro,
                        'history_extract' => $aiSummary['historia'] ?? null,
                        'safety_level' => $aiSummary['nivel_seguranca'] ?? 'MODERADO',
                        'safety_description' => $aiSummary['descricao_seguranca'] ?? null,
                        'wiki_json' => $wikiResult,
                        'real_estate_json' => $aiSummary['mercado_imobiliario'] ?? null
                    ]);
                }
            }

            // 5. Concluir o Report
            $report->update([
                'history_extract' => $aiSummary['historia'] ?? null,
                'safety_level' => $aiSummary['nivel_seguranca'] ?? 'MODERADO',
                'safety_description' => $aiSummary['descricao_seguranca'] ?? null,
                'real_estate_json' => $aiSummary['mercado_imobiliario'] ?? null,
                'wiki_json' => $wikiResult,
                'status' => 'completed',
                'error_message' => null
            ]);

            Log::info("TextGenerator Job completed successfully for Report {$this->reportId}");

        } catch (\Exception $e) {
            Log::error("TextGenerator Job failed for CEP {$this->cep}. Error: " . $e->getMessage());
            $report->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 200)
            ]);
            throw $e;
        }
    }

    private function isValidWikipediaPlace(array $data, string $expectedCity = '', string $expectedState = ''): bool
    {
        $type        = $data['type'] ?? '';
        $description = strtolower($data['description'] ?? '');
        $extract     = $data['extract'] ?? '';

        if ($type === 'disambiguation') return false;
        if (empty($extract))           return false;

        $placeKeywords = ['município', 'cidade', 'bairro', 'distrito', 'região', 'localidade', 'capital', 'entidade', 'unidade federativa', 'povoado'];
        $isPlace = false;
        foreach ($placeKeywords as $kw) {
            if (str_contains($description, $kw)) {
                $isPlace = true;
                break;
            }
        }

        $rejectPatterns = [
            '/^o centro, em geometria/i', '/^em geometria/i', '/^em matemática/i', '/^em física/i', '/^na religião/i','/^segundo a bíblia/i'
        ];
        foreach ($rejectPatterns as $pattern) {
            if (preg_match($pattern, $extract)) return false;
        }

        // Validação de contexto (Cidade/Estado) se fornecidos
        if ($expectedCity || $expectedState) {
            $textToSearch = strtolower($description . ' ' . $extract);
            $cityLower  = strtolower($expectedCity);
            $stateLower = strtolower($expectedState);

            if (!str_contains($textToSearch, $cityLower) && !str_contains($textToSearch, $stateLower)) {
                return false; // Rejeitado pq nao bate a cidade
            }
        }

        return $isPlace || !empty($description);
    }

    private function fetchWikipediaInfo(string $bairro, string $city, string $state): ?array
    {
        $headers = ['User-Agent' => 'RaioXNeighborhood/1.0'];
        $base    = 'https://pt.wikipedia.org/api/rest_v1/page/summary/';

        $bairroLower = strtolower($bairro);
        $bairroIsAmbiguous = false;
        foreach (self::AMBIGUOUS_TERMS as $term) {
            if (str_contains($bairroLower, $term)) {
                $bairroIsAmbiguous = true;
                break;
            }
        }

        $candidates = [];
        if ($bairro) {
            $candidates[] = [str_replace(' ', '_', "{$bairro} ({$city})"), 'bairro', true];
            if (!$bairroIsAmbiguous) {
                $candidates[] = [str_replace(' ', '_', $bairro), 'bairro', true];
            }
        }
        $candidates[] = [str_replace(' ', '_', "{$city} ({$state})"), 'cidade', false];
        $candidates[] = [str_replace(' ', '_', $city), 'cidade', true];

        foreach ($candidates as [$term, $source, $shouldValidate]) {
            try {
                $response = Http::withoutVerifying()->timeout(10)->withHeaders($headers)
                    ->get($base . urlencode($term));

                if ($response->successful()) {
                    $data = $response->json();
                    $vCity = $shouldValidate ? $city : '';
                    $vState = $shouldValidate ? $state : '';
                    if (!$this->isValidWikipediaPlace($data, $vCity, $vState)) {
                        continue;
                    }
                    
                    // Busca conteúdo completo 
                    $fullText = $this->fetchWikipediaFullContent($term, $headers);
                    return [
                        'source'      => $source,
                        'term'        => $term,
                        'extract'     => $data['extract'],
                        'full_text'   => $fullText ?: $data['extract'],
                        'image'       => $data['originalimage']['source'] ?? $data['thumbnail']['source'] ?? null,
                        'desktop_url' => $data['content_urls']['desktop']['page'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Wikipedia timeout/error for [{$term}]: " . $e->getMessage());
            }
        }
        return [];
    }

    private function fetchWikipediaFullContent(string $term, array $headers): ?string
    {
        try {
            $response = Http::withoutVerifying()->timeout(15)->withHeaders($headers)
                ->get('https://pt.wikipedia.org/w/api.php', [
                    'action' => 'query', 'prop' => 'extracts', 'exlimit' => 1,
                    'titles' => str_replace('_', ' ', $term), 'explaintext' => 1, 'format' => 'json'
                ]);

            if (!$response->successful()) return null;

            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];
            if (empty($pages)) return null;

            $page = reset($pages);
            if (isset($page['missing'])) return null;

            $raw = $page['extract'] ?? '';
            $raw = preg_replace('/\[\d+\]/', '', $raw);
            $raw = preg_replace('/\s+/', ' ', trim($raw));

            return trim(mb_substr($raw, 0, 15000)) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}

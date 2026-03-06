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
                // Se o Gemini falhou, salvamos o que temos e marcamos como 'completed'
                // para não quebrar a visualização dos dados técnicos (mapa, infra, etc)
                $report->update([
                    'status' => 'completed',
                    'history_extract' => 'A narrativa territorial está temporariamente indisponível devido à alta demanda nos satélites de IA. Os dados técnicos abaixo permanecem precisos.',
                    'error_message' => 'AI_TIMEOUT'
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
                    'population' => $report->populacao,
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
                'aact_log' => $aactContext,
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

        $stateMap = [
            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
            'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
            'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
            'SE' => 'Sergipe', 'TO' => 'Tocantins'
        ];
        $stateFullName = $stateMap[strtoupper($state)] ?? $state;

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
        // Fallbacks por Cidade (Mais robustos agora com nome completo do estado)
        $candidates[] = [str_replace(' ', '_', "{$city} ({$stateFullName})"), 'cidade', false];
        $candidates[] = [str_replace(' ', '_', $city), 'cidade', true];

        foreach ($candidates as [$term, $source, $shouldValidate]) {
            try {
                // urlencode padrão UTF-8
                $url = $base . str_replace('%2F', '/', urlencode($term));
                $response = Http::withoutVerifying()->timeout(10)->withHeaders($headers)->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Para candidatos de cidade, relaxamos a validação (confiamos no título exato se necessário)
                    $vCity = $shouldValidate ? $city : '';
                    $vState = $shouldValidate ? ($source === 'cidade' ? '' : $stateFullName) : '';
                    
                    if (!$this->isValidWikipediaPlace($data, $vCity, $vState)) {
                        continue;
                    }
                    
                    // Busca imagem oficial via API de PageImages (Thumbnail 960px)
                    $officialImage = $this->fetchWikipediaImageViaAPI($term, $headers);

                    // Busca conteúdo completo 
                    $fullText = $this->fetchWikipediaFullContent($term, $headers);
                    return [
                        'source'      => $source,
                        'term'        => $term,
                        'extract'     => $data['extract'],
                        'full_text'   => $fullText ?: $data['extract'],
                        'image'       => $officialImage ?: ($data['originalimage']['source'] ?? $data['thumbnail']['source'] ?? null),
                        'desktop_url' => $data['content_urls']['desktop']['page'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Wikipedia timeout/error for [{$term}]: " . $e->getMessage());
            }
        }
        return [];
    }

    private function fetchWikipediaImageViaAPI(string $term, array $headers): ?string
    {
        try {
            $title = str_replace('_', ' ', $term);
            $response = Http::withoutVerifying()->timeout(10)->withHeaders($headers)
                ->get('https://pt.wikipedia.org/w/api.php', [
                    'action' => 'query',
                    'prop' => 'pageimages',
                    'format' => 'json',
                    'piprop' => 'thumbnail',
                    'pithumbsize' => 960,
                    'titles' => $title
                ]);

            if (!$response->successful()) return null;

            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];
            if (empty($pages)) return null;

            $page = reset($pages);
            return $page['thumbnail']['source'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
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

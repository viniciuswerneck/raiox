<?php

namespace App\Jobs;

use App\Models\City;
use App\Models\LocationReport;
use App\Models\Neighborhood;
use App\Services\LlmManagerService;
use App\Services\TextReviserService;
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
    public function handle(LlmManagerService $llm, \App\Services\AactService $aact, \App\Services\RealEstateTrendService $trendService): void
    {
        // Delay aleatório inicial para escalonamento
        usleep(rand(200000, 1000000)); 
        
        $report = LocationReport::find($this->reportId);
        if (!$report) return;

        try {
            $city = $report->cidade;
            $state = $report->uf;
            $bairro = $report->bairro ?? '';
            
            // Dados da Wikipedia já devem ter sido coletados pela TerritoryEngine ou coletamos agora via WikiAgent
            // Se o report já tem wiki_json, usamos ele.
            $wikiResult = $report->wiki_json ?? [];
            
            $historyRaw = $wikiResult['full_text'] ?? $wikiResult['extract'] ?? "Local em desenvolvimento. Utilize as informações de infraestrutura para análise.";
            
            // Truncar para evitar Payload Too Large (413)
            $historyRaw = mb_substr($historyRaw, 0, 6000);

            // Contexto para a IA
            $aactContext = [
                'categoria' => $report->territorial_classification ?? 'Misto',
                'pois_count' => is_array($report->pois_json) ? count($report->pois_json) : 0,
                'renda' => $report->average_income ?? 1500,
                'populacao' => $report->populacao ?? 0,
                'safety_level' => $report->safety_level ?? 'MODERADO',
                'sanitation_rate' => $report->sanitation_rate ?? 0,
                'walk_score' => $report->walkability_score ?? 'N/A'
            ];

            $locationName = $bairro ? "{$bairro}, {$city}" : $city;

            // Dados recuperados da Memória Territorial (RAG)
            $ragContextString = "";
            if (!empty($this->wikiSearchContext['context_chunks'])) {
                $chunks = array_map(fn($c) => $c['content'], $this->wikiSearchContext['context_chunks']);
                $ragContextString = "\nMemória Territorial (Dados anteriores): " . implode(" | ", $chunks);
            }

            // [NEW] 1a. Análise de Tendência Regional (Mini-Grafo de Adjacência)
            $trendData = $trendService->analyzePotential($report);
            $trendContext = "";
            if (!empty($trendData['nearby_hubs'])) {
                $hubs = collect($trendData['nearby_hubs'])->map(fn($h) => "{$h['name']} ({$h['dist']}km - {$h['classification']})")->implode(', ');
                $trendContext = "\nInteligência de Mercado Local: Este local está próximo de polos de valorização como {$hubs}. "
                    . "Tendência detectada pelo algoritmo: {$trendData['trend']}. "
                    . "Análise estratégica: {$trendData['description']}.";
            }

            // 1. Geração da Narrativa e Dados Estruturados via LlmManager
            $systemPrompt = "Você é um especialista sênior em análise territorial, urbanismo e SEO do sistema Raio-X. "
                . "Sua tarefa é criar uma análise profunda, autoritativa e enciclopédica sobre uma localidade, otimizada para buscadores (SEO). "
                . "A narrativa deve ser rica em detalhes históricos, culturais, arquitetônicos e geográficos. "
                . "Combine os dados históricos e de mercado fornecidos com sua base de conhecimento sobre a evolução urbana local. "
                . "Estrutura OBRIGATÓRIA da narrativa (MÍNIMO DE 4 PARÁGRAFOS extensos e 350 palavras):\n"
                . "1. Contexto Histórico: Origens e evolução.\n"
                . "2. Perfil Cultural: Identidade e estilo de vida.\n"
                . "3. Desenvolvimento Urbano: Infraestrutura e legado regional.\n"
                . "4. Dinâmica Contemporânea e Projeções: O papel do bairro na cidade hoje e seu potencial de valorização futuro.\n"
                . "REGRAS EXTRAS:\n"
                . "- Incorpore inteligentemente a análise de mercado sobre os bairros vizinhos no último parágrafo.\n"
                . "- Separe os parágrafos obrigatoriamente com DUAS quebras de linha (\\n\\n).\n"
                . "- Use um tom profissional e envolvente. Evite clichês.\n"
                . "Você deve retornar APENAS um JSON válido seguindo este formato rigoroso:\n"
                . "{\n"
                . "  \"narrative\": \"Texto completo (mínimo 4 parágrafos, separados por \\n\\n). Sem markdown.\",\n"
                . "  \"safety_analysis\": \"Uma frase curta (máx 150 caracteres) descrevendo a percepção de segurança.\",\n"
                . "  \"real_estate\": {\n"
                . "    \"preco_m2\": \"R$ X.XXX a R$ X.XXX\",\n"
                . "    \"perfil_imoveis\": \"Ex: Residencial horizontal predominante\",\n"
                . "    \"tendencia_valorizacao\": \"ALTA, MÉDIA ou ESTÁVEL\"\n"
                . "  }\n"
                . "}";

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Gere uma análise para {$locationName}.{$ragContextString}{$trendContext}\nDados históricos: {$historyRaw}. Contexto: " . json_encode($aactContext)]
            ];

            Log::info("[Job] Solicitando análise estruturada para {$locationName} via LlmManager");
            
            $response = $llm->chat($messages, 'creative', [
                'agent_name' => 'StructuredAnalysisAgent',
                'agent_version' => '2.1.0'
            ]);

            // 1. Extração e Sanitização do JSON via LlmManager
            $jsonRaw = $response['choices'][0]['message']['content'] ?? null;

            if (!$jsonRaw) {
                throw new \Exception("Falha ao gerar análise via LlmManager");
            }

            $analysis = $this->parseStructuredAnalysis($jsonRaw);

            if ($analysis && isset($analysis['narrative']) && is_array($analysis['narrative'])) {
                $analysis['narrative'] = implode("\n\n", array_map(function($p) {
                    if (is_string($p)) return $p;
                    if (is_array($p)) return $p['texto'] ?? $p['content'] ?? $p['text'] ?? json_encode($p);
                    return (string)$p;
                }, $analysis['narrative']));
            }

            // Normaliza espaçamentos excessivos (evita o "vácuo" entre parágrafos)
            if ($analysis && isset($analysis['narrative'])) {
                $analysis['narrative'] = preg_replace("/(\n\s*){3,}/", "\n\n", $analysis['narrative']);
            }

            if (!$analysis || !isset($analysis['narrative'])) {
                Log::warning("IA não retornou JSON coerente para {$locationName}. Tentando extração via fallback.");
                $analysis = [
                    'narrative' => $this->fallbackNarrativeExtraction($jsonRaw),
                    'safety_analysis' => 'Baseado em infraestrutura local e vigilância monitorada.',
                    'real_estate' => [
                        'preco_m2' => 'Sob consulta',
                        'perfil_imoveis' => 'Misto',
                        'tendencia_valorizacao' => 'ESTÁVEL'
                    ]
                ];
            }

            // 2. Auditoria e Recalibragem via AACT Service
            $auditData = [
                'cep' => $report->cep,
                'bairro' => $report->bairro,
                'localidade' => $report->cidade,
                'lat' => $report->lat,
                'lng' => $report->lng,
                'average_income' => $report->average_income,
                'sanitation_rate' => $report->sanitation_rate,
                'pois_json' => $report->pois_json,
                'real_estate_json' => $analysis['real_estate']
            ];

            $calibrated = $aact->auditAndRecalibrate($auditData);

            // 3. Salvar Resultados
            $report->update([
                'history_extract' => $analysis['narrative'],
                'safety_description' => $analysis['safety_analysis'],
                'real_estate_json' => $calibrated['real_estate_json'],
                'sanitation_rate' => $calibrated['sanitation_rate'],
                'territorial_classification' => $calibrated['territorial_classification'],
                'aact_log' => $calibrated['aact_log'],
                'status' => 'completed',
                'error_message' => null
            ]);

            // Cache em City/Neighborhood
            $this->updateCacheModels($report, $analysis['narrative'], $wikiResult);

        } catch (\Exception $e) {
            Log::error("TextGenerator Job failed: " . $e->getMessage());
            $report->update([
                'status' => 'failed',
                'error_message' => 'AI_FAILURE: ' . $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function updateCacheModels(LocationReport $report, string $content, array $wikiResult): void
    {
        $cityModel = City::updateOrCreate(
            ['name' => $report->cidade, 'uf' => $report->uf],
            [
                'population' => $report->populacao,
                'average_income' => $report->average_income,
                'history_extract' => $report->bairro ? null : $content,
                'wiki_json' => $report->bairro ? [] : $wikiResult,
            ]
        );

        if ($report->bairro) {
            Neighborhood::updateOrCreate(
                ['city_id' => $cityModel->id, 'name' => $report->bairro],
                [
                    'history_extract' => $content,
                    'wiki_json' => $wikiResult,
                ]
            );
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

        $bestResult = null;

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
                    $imageUrl = $officialImage ?: ($data['originalimage']['source'] ?? $data['thumbnail']['source'] ?? null);

                    // Busca conteúdo completo para o resumo
                    $fullText = $this->fetchWikipediaFullContent($term, $headers);
                    
                    $currentResult = [
                        'source'      => $source,
                        'term'        => $term,
                        'extract'     => $data['extract'],
                        'full_text'   => $fullText ?: $data['extract'],
                        'image'       => $imageUrl,
                        'desktop_url' => $data['content_urls']['desktop']['page'] ?? null,
                    ];

                    // Se for o primeiro resultado válido de texto, salvamos
                    if (!$bestResult) {
                        $bestResult = $currentResult;
                    }

                    // ESTRATÉGIA: Se já temos texto E agora encontramos um resultado com IMAGEM,
                    // priorizamos o texto original do bairro mas usamos a imagem da cidade
                    if ($bestResult && !$bestResult['image'] && $currentResult['image']) {
                        $bestResult['image'] = $currentResult['image'];
                    }

                    // Se o melhor resultado já tem imagem, podemos parar
                    if ($bestResult && $bestResult['image']) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Wikipedia timeout/error for [{$term}]: " . $e->getMessage());
            }
        }
        return $bestResult ?: [];
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

    /**
     * Sanitiza e converte a resposta da IA em um array estruturado
     */
    private function parseStructuredAnalysis(string $raw): ?array
    {
        // 1. Tentar extrair apenas o que estiver entre a primeira { e a última }
        if (preg_match('/\{(?:.|\n)*\}/', $raw, $matches)) {
            $raw = $matches[0];
        }

        // 2. Limpar markdown de código
        $json = preg_replace('/```json\s?|```/', '', $raw);
        $json = trim($json);

        // 3. Primeira tentativa direta
        $data = json_decode($json, true);
        if ($data) return $data;

        // 4. Se falhou, pode ser que existam quebras de linha literais dentro das strings
        // Vamos tentar sanitizar newlines dentro de valores de string
        $sanitized = preg_replace_callback('/"(.*?)"/s', function ($matches) {
            return '"' . str_replace(["\n", "\r"], ["\\n", ""], $matches[1]) . '"';
        }, $json);

        $data = json_decode($sanitized, true);
        if ($data) return $data;

        return null;
    }

    /**
     * Se o JSON falhar, tenta extrair pelo menos a narrativa com Regex
     */
    private function fallbackNarrativeExtraction(string $raw): string
    {
        // Tenta pegar o valor do campo "narrative" se existir no texto
        if (preg_match('/"narrative":\s*"(.*?)"/s', $raw, $matches)) {
            $text = $matches[1];
            // Desescapar \n se existirem como literais
            $text = str_replace(['\\n', '\\r'], ["\n", ""], $text);
            $text = preg_replace("/(\n\s*){3,}/", "\n\n", $text);
            return trim($text);
        }

        // Último caso: limpa markdown e retorna o bloco de texto mais relevante
        $clean = preg_replace('/```json\s?|```|\{|\}|"narrative":|"safety_analysis":|"real_estate":|"preco_m2":|"perfil_imoveis":|"tendencia_valorizacao":/i', '', $raw);
        $clean = preg_replace('/:[^,]+,/', '', $clean); // Remove outros campos simples
        $clean = str_replace(['**', '*'], '', $clean);
        
        return trim($clean);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LocationReport;
use App\Models\RegionComparison;
use App\Services\Agents\CompareAgent;
use App\Services\NeighborhoodService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompareController extends Controller
{
    protected $compareAgent;

    protected $llm;

    protected $neighborhoodService;

    public function __construct(
        CompareAgent $compareAgent,
        \App\Services\LlmManagerService $llm,
        NeighborhoodService $neighborhoodService
    ) {
        $this->compareAgent = $compareAgent;
        $this->llm = $llm;
        $this->neighborhoodService = $neighborhoodService;
    }

    public function index()
    {
        $duels = RegionComparison::orderBy('created_at', 'desc')->paginate(15);

        return view('report.duels', compact('duels'));
    }

    public function reprocess($cepA, $cepB)
    {
        $cepA = preg_replace('/\D/', '', $cepA);
        $cepB = preg_replace('/\D/', '', $cepB);

        $comparison = RegionComparison::findPair($cepA, $cepB);

        if ($comparison) {
            $comparison->delete();
            Log::info("CompareController: Duelo entre {$cepA} e {$cepB} foi apagado para reprocessamento.");
        }

        return redirect()->route('report.compare', ['cepA' => $cepA, 'cepB' => $cepB]);
    }

    public function show($cepA, $cepB)
    {
        @set_time_limit(120);
        $cepA = preg_replace('/\D/', '', $cepA);
        $cepB = preg_replace('/\D/', '', $cepB);

        if ($cepA === $cepB) {
            return redirect()->route('report.show', $cepA);
        }

        // 1. PRIORIDADE MÁXIMA: Verificar se já existe uma comparação salva no Banco de Dados
        $comparison = RegionComparison::findPair($cepA, $cepB);

        if ($comparison) {
            Log::info("CompareController: Comparação recuperada do banco para {$cepA} vs {$cepB} (Duelo instantâneo)");
        } else {
            // 2. Se não existe no banco, TRABALHO PESADO: Carregar relatórios e gerar
            // ESTRATÉGIA TURBO: Se algum não existir, processamos em paralelo via NeighborhoodService
            if (! LocationReport::where('cep', $cepA)->exists() || ! LocationReport::where('cep', $cepB)->exists()) {
                Log::info('CompareController: Um ou ambos os CEPs são novos. Processando em paralelo.');

                // Disparamos o processamento (que agora é macro-paralelo internamente)
                // Se o CEP já existir, o NeighborhoodService apenas retorna o cache.
                $reportA = $this->neighborhoodService->getCachedReport($cepA);
                $reportB = $this->neighborhoodService->getCachedReport($cepB);
            } else {
                $reportA = LocationReport::where('cep', $cepA)->first();
                $reportB = LocationReport::where('cep', $cepB)->first();
            }

            if (! $reportA || ! $reportB) {
                return redirect()->route('home')->withErrors(['cep' => 'Não foi possível processar um dos CEPs para o duelo.']);
            }

            // 3. LAZY UPDATE: Garantir que dados locais estejam atualizados (OSM/Scores)
            $this->ensureDataIsFresh($reportA);
            $this->ensureDataIsFresh($reportB);

            // 4. Gerar Análise via IA e Agente de Comparação
            Log::info("CompareController: Gerando nova análise de duelo para {$cepA} vs {$cepB}");

            $results = $this->compareAgent->compare($reportA, $reportB);

            $analysis = $this->llm->chat([
                ['role' => 'system', 'content' => 'Você é o Analista Master de Territórios do Raio-X, um especialista sênior em planejamento urbano e geolocalização. Sua missão é fornecer uma consultoria de alto nível para alguém que está decidindo onde MORAR.
                
                Dadas duas localizações, analise tecnicamente:
                1. O perfil predominante de cada área (ex: polo de serviços vs reduto residencial).
                2. O custo-benefício implícito (renda vs infraestrutura).
                3. A dinâmica de mobilidade (quem depende de carro vs quem pode fazer tudo a pé).
                4. O veredito para perfis específicos: Famílias com crianças, Jovens Profissionais e Idosos.
                
                Seja direto, honesto e evite clichês. Destaque o trade-off real: o que a pessoa ganha e o que ela perde ao escolher uma em detrimento da outra.
                
                IMPORTANTE: 
                - Não use negrito, não use asteriscos, não use markdown.
                - Use apenas parágrafos fluidos.
                - Mantenha um tom profissional, consultivo e inspirador.'],
                ['role' => 'user', 'content' => 'Dados Comparativos: '.json_encode([
                    'localidade_a' => array_merge($this->mapReportToAnalysis($reportA), $results['profiles']['a']),
                    'localidade_b' => array_merge($this->mapReportToAnalysis($reportB), $results['profiles']['b']),
                    'distancia_entre_pontos' => $results['distance_km'].' km',
                    'diferenca_renda' => $results['deltas']['income_diff'],
                ])],
            ], 'creative', [
                'agent_name' => 'CompareAgent',
                'agent_version' => '2.1.0',
            ]);

            $analysisText = $analysis['choices'][0]['message']['content'] ?? 'Comparação técnica concluída. Observe os indicadores de ruído e infraestrutura abaixo para decidir.';

            // 5. Salvar na Pedra (Banco) para nunca mais processar este par
            $comparison = RegionComparison::create([
                'cep_a' => $cepA,
                'cep_b' => $cepB,
                'score_diff' => $results['deltas']['score_diff'],
                'infra_diff' => $results['deltas']['infra_diff'],
                'mobilidade_diff' => $results['deltas']['mobilidade_diff'],
                'lazer_diff' => $results['deltas']['lazer_diff'],
                'comparison_data' => [
                    'metrics_a' => $results['metrics_a'],
                    'metrics_b' => $results['metrics_b'],
                    'distance_km' => $results['distance_km'],
                    'profiles' => $results['profiles'],
                    'location_a' => "{$reportA->bairro}, {$reportA->cidade}",
                    'location_b' => "{$reportB->bairro}, {$reportB->cidade}",
                ],
                'analysis_text' => $analysisText,
            ]);
        }

        // 6. Retornar View com os relatórios populados
        $reportA = LocationReport::where('cep', $cepA)->first();
        $reportB = LocationReport::where('cep', $cepB)->first();

        return view('report.compare', [
            'comparison' => $comparison,
            'reportA' => $reportA,
            'reportB' => $reportB,
        ]);
    }

    /**
     * Garante que o relatório tenha os scores e POIs atualizados (Lazy Update)
     */
    private function ensureDataIsFresh(LocationReport $report)
    {
        $lock = \Illuminate\Support\Facades\Cache::lock("rehydrate_{$report->cep}", 120);
        if (! $lock->get()) {
            return;
        }

        try {
            $needsUpdate = false;
            // 1. Se o relatório for de versão antiga (sem busca adaptativa 5km)
            if ($report->data_version < 3) {
                Log::info("CompareController: Reidratando POIs (V2 -> V3) para o CEP {$report->cep}");
                $poiAgent = app(\App\Services\Agents\POIAgent::class);
                $adaptiveData = $poiAgent->fetchPOIsAdaptive($report->lat, $report->lng);
                $newPois = $adaptiveData['pois'];

                if (! empty($newPois)) {
                    $report->pois_json = $newPois;
                    $report->search_radius = $adaptiveData['radius'];
                    $report->data_version = 3;
                    $needsUpdate = true;
                }
            }

            // 2. Se não tem scores calculados, calcula agora
            if ($report->general_score == 0 && ! empty($report->pois_json)) {
                Log::info("CompareController: Calculando scores ausentes para o CEP {$report->cep}");
                $metrics = $this->compareAgent->getRegionMetrics($report->pois_json);

                $report->infra_score = $metrics['infra'];
                $report->mobility_score = $metrics['mobility'];
                $report->leisure_score = $metrics['leisure'];
                $report->general_score = $metrics['total_score'];
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $report->save();
            }
        } finally {
            $lock->release();
        }
    }

    private function mapReportToAnalysis(LocationReport $report): array
    {
        $realEstate = $report->real_estate_json ?? [];
        $precoM2 = $realEstate['preco_m2'] ?? 'Sob Consulta';

        return [
            'cep' => $report->cep,
            'bairro' => $report->bairro,
            'cidade' => $report->cidade,
            'class' => $report->territorial_classification ?? 'Residencial',
            'income' => $report->average_income,
            'infra' => $report->infra_score,
            'mobility' => $report->mobility_score,
            'leisure' => $report->leisure_score,
            'preco_m2' => $precoM2,
            'lat' => $report->lat,
            'lng' => $report->lng,
        ];
    }
}

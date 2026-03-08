<?php

namespace App\Http\Controllers;

use App\Models\LocationReport;
use App\Models\RegionComparison;
use App\Services\Agents\CompareAgent;
use App\Services\GeminiService;
use App\Services\NeighborhoodService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompareController extends Controller
{
    protected $compareAgent;
    protected $geminiService;
    protected $neighborhoodService;

    public function __construct(
        CompareAgent $compareAgent,
        GeminiService $geminiService,
        NeighborhoodService $neighborhoodService
    ) {
        $this->compareAgent = $compareAgent;
        $this->geminiService = $geminiService;
        $this->neighborhoodService = $neighborhoodService;
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
            $reportA = LocationReport::where('cep', $cepA)->first();
            $reportB = LocationReport::where('cep', $cepB)->first();

            // Se algum não existir, redireciona para gerar o base primeiro
            if (!$reportA) return redirect()->route('report.show', $cepA);
            if (!$reportB) return redirect()->route('report.show', $cepB);

            // 3. LAZY UPDATE: Garantir que dados locais estejam atualizados (OSM/Scores)
            $this->ensureDataIsFresh($reportA);
            $this->ensureDataIsFresh($reportB);

            // 4. Gerar Análise via IA e Agente de Comparação
            Log::info("CompareController: Gerando nova comparação entre {$cepA} e {$cepB} via Gemini");
            
            $results = $this->compareAgent->compare($reportA, $reportB);
            
            $analysis = $this->geminiService->generateComparisonAnalysis(
                $this->mapReportToAnalysis($reportA),
                $this->mapReportToAnalysis($reportB)
            );

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
                    'location_a' => "{$reportA->bairro}, {$reportA->cidade}",
                    'location_b' => "{$reportB->bairro}, {$reportB->cidade}"
                ],
                'analysis_text' => $analysis
            ]);
        }

        // 6. Retornar View com os relatórios populados
        $reportA = LocationReport::where('cep', $cepA)->first();
        $reportB = LocationReport::where('cep', $cepB)->first();

        return view('report.compare', [
            'comparison' => $comparison,
            'reportA' => $reportA,
            'reportB' => $reportB
        ]);
    }

    /**
     * Garante que o relatório tenha os scores e POIs atualizados (Lazy Update)
     */
    private function ensureDataIsFresh(LocationReport $report)
    {
        $lock = \Illuminate\Support\Facades\Cache::lock("rehydrate_{$report->cep}", 120);
        if (!$lock->get()) return;

        try {
            $needsUpdate = false;
            // 1. Se o relatório for de versão antiga (sem busca adaptativa 5km)
            if ($report->data_version < 3) {
                Log::info("CompareController: Reidratando POIs (V2 -> V3) para o CEP {$report->cep}");
                $poiAgent = app(\App\Services\Agents\POIAgent::class);
                $adaptiveData = $poiAgent->fetchPOIsAdaptive($report->lat, $report->lng);
                $newPois = $adaptiveData['pois'];
                
                if (!empty($newPois)) {
                    $report->pois_json = $newPois;
                    $report->search_radius = $adaptiveData['radius'];
                    $report->data_version = 3;
                    $needsUpdate = true;
                }
            }

            // 2. Se não tem scores calculados, calcula agora
            if ($report->general_score == 0 && !empty($report->pois_json)) {
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
            'lng' => $report->lng
        ];
    }
}
